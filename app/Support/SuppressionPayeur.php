<?php

namespace App\Support;

use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Regle metier de suppression reelle d'un compte payeur (back-office super admin).
 *
 * Le bouton « Supprimer » ne faisait qu'un SOFT DELETE : `$payeur->delete()`
 * pose un `deleted_at` et laisse la ligne dans `users`. Rien n'etait efface de la
 * base, et surtout tout le module « Comptes payeurs » (queries Eloquent sur
 * `User`) le faisait disparaitre de la liste, ce qui donnait l'illusion d'une
 * suppression reelle.
 *
 * Une VRAIE suppression (`DELETE FROM users`) exige de statuer sur la situation
 * financiere du compte, car les paiements, notifications et demandes de
 * remboursement y sont rattaches :
 *
 *   1. aucun frais attribue  -> suppression autorisee. Le compte n'a rien
 *      engage : il n'y a ni echeance, ni recu, ni reversement. Rien a
 *      deconnecter de la comptabilite de l'etablissement.
 *   2. tous les frais deja regles -> suppression autorisee. La dette est
 *      soldee, le compte n'a plus d'obligation envers l'etablissement. Les
 *      paiements restent en base et sont simplement detaches (`user_id` mis a
 *      NULL par la migration `2026_09_30_100000_...`) : le releve financier
 *      survit, comme l'exige le CDC §3.2 (« tracabilite complete et
 *      infalsifiable de toutes les transactions financieres ») et le §8.3
 *      (« logs conserves 12 mois min »).
 *   3. au moins un frais impaye ou partiel -> SUPPRESSION REFUSEE. Effacer le
 *      compte ferait disparaitre ledebiteur de l'etablissement alors qu'il doit
 *      encore de l'argent : la creance resterait orpheline, l'ecole ne pourrait
 *      plus la relancer et le super-admin perdrait le lien avec l'impaye. Le
 *      compte doit d'abord etre solde, puis supprime — ou suspendu s'il doit
 *      rester trace du dossier.
 *
 * Cas 4, supplementaire : un paiement encore en attente de confirmation AangaraaPay
 * est traite comme un frais impaye. Supprimer le compte au milieu d'une
 * transaction PSP en cours risquerait de valider un debit sans contrepartie
 * comptabilisable cote plateforme.
 */
final class SuppressionPayeur
{
    /**
     * Analyse la situation financiere d'un payeur et statue sur la suppression.
     *
     * @return array{
     *     autorise: bool,
     *     frais_attribues: int,
     *     frais_impayes: int,
     *     montant_impaye: float,
     *     paiements_en_attente: int,
     *     motif: string|null
     * }
     */
    public static function analyser(User $payeur): array
    {
        // Un seul `frais_apprenant` par couple (apprenant, categorie, annee) :
        // la regle « tous les frais sont payes » se lit donc sur le statut de
        // chaque ligne, pas sur un total, et separe bien « aucun frais » de
        // « des frais, tous soldes ».
        $frais = FraisApprenant::whereIn('apprenant_id', static::apprenantIds($payeur))->get();

        $fraisAttribues = $frais->count();

        $fraisImpayes = $frais->filter(
            static fn (FraisApprenant $f) => $f->statut !== 'regle'
                || $f->montant_paye < $f->montant_total
        );

        $montantImpaye = (float) $fraisImpayes->sum(
            static fn (FraisApprenant $f) => max(0, (float) $f->montant_total - (float) $f->montant_paye)
        );

        $paiementsEnAttente = Paiement::where('user_id', $payeur->id)
            ->where('statut', 'en_attente')
            ->count();

        $autorise = ($fraisAttribues === 0 || $fraisImpayes->isEmpty()) && $paiementsEnAttente === 0;

        return [
            'autorise'            => $autorise,
            'frais_attribues'     => $fraisAttribues,
            'frais_impayes'       => $fraisImpayes->count(),
            'montant_impaye'      => $montantImpaye,
            'paiements_en_attente' => $paiementsEnAttente,
            'motif'               => $autorise ? null : static::motifRefus(
                $fraisAttribues,
                $fraisImpayes->count(),
                $montantImpaye,
                $paiementsEnAttente
            ),
        ];
    }

    /**
     * Identifiants des apprenants rattaches au payeur.
     *
     * Le lien passe par la table pivot `user_apprenant` : il n'existe pas de
     * colonne `parent_id` sur `apprenants`.
     *
     * @return array<int, int>
     */
    public static function apprenantIds(User $payeur): array
    {
        return Apprenant::whereHas('parents', fn ($q) => $q->where('users.id', $payeur->id))
            ->pluck('id')
            ->all();
    }

    /**
     * Message de refus, explicite sur le montant restant du.
     */
    private static function motifRefus(
        int $fraisAttribues,
        int $fraisImpayes,
        float $montantImpaye,
        int $paiementsEnAttente
    ): string {
        if ($paiementsEnAttente > 0) {
            return __('admin.suppr_refus_attente', [
                'nombre' => $paiementsEnAttente,
            ]);
        }

        return __('admin.suppr_refus_impaye', [
            'nombre'   => $fraisImpayes,
            'montant'  => number_format($montantImpaye, 0, ',', ' '),
        ]);
    }

    /**
     * Analyse en lot : la suppression groupee ne doit pas s'appliquer en
     * aveugle a une selection mixte (un payeur solde, un payeur endette).
     *
     * @param  Collection<int, User>  $payeurs
     * @return array{
     *     supprimer: Collection<int, User>,
     *     refuses: Collection<int, User>,
     *     motifs: array<int, string>
     * }
     */
    public static function analyserLot(Collection $payeurs): array
    {
        $supprimer = collect();
        $refuses   = collect();
        $motifs    = [];

        // Une seule analyse par compte : `analyser()` compte les frais et les
        // paiements en base, le repeter trois fois par payeur triplerait les
        // requetes d'un traitement groupé.
        foreach ($payeurs as $payeur) {
            $analyse = static::analyser($payeur);

            if ($analyse['autorise']) {
                $supprimer->push($payeur);

                continue;
            }

            $refuses->push($payeur);
            $motifs[$payeur->id] = $analyse['motif'];
        }

        return [
            'supprimer' => $supprimer->values(),
            'refuses'   => $refuses->values(),
            'motifs'    => $motifs,
        ];
    }}
