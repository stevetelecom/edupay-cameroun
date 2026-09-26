<?php

namespace App\Support;

use App\Models\Echeancier;
use App\Models\FraisApprenant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Source de verite unique du montant reellement debite lors d'un paiement
 * (audit D — risque financier).
 *
 * AVANT : le meme calcul errone etait duplique dans deux controleurs :
 *   - app/Http/Controllers/Api/PaiementController.php   (application mobile)
 *   - app/Http/Controllers/Payeur/PaiementController.php (tunnel web)
 *
 *     $montant = $type === 'tranche'
 *         ? (int) round($resteAPayer / ($frais->categorieFrais->nb_tranches_max ?? 2))
 *         : (int) $resteAPayer;
 *
 * Consequence : le montant affiche par le mobile (tranche de 25 000 FCFA
 * lue dans le calendrier de l'etablissement) etait ignore par le serveur,
 * qui debitait le reste du (100 000 FCFA). Le client ne pouvait choisir que
 * "integral" ou "tranche", jamais le montant, et l'API acceptait un
 * `echeancier_id` qu'elle ignorait completement.
 *
 * Regles non negociables (CDC §6.1 F09 et §6.2 E03) :
 *   1. le montant vient TOUJOURS du serveur, jamais du client ;
 *   2. une tranche vaut le montant de l'echeance definie par l'etablissement
 *      (calendrier de paiement), plafonne au reste du ;
 *   3. le montant du ne peut jamais depasser le reste du (pas de
 *      sur-facturation, meme si le bareme a change depuis l'affichage) ;
 *   4. une echeance deja soldee par des paiements valides est refusee.
 *
 * Si l'etablissement n'a defini aucun calendrier de tranches, on replie
 * sur un fractionnement equitable du reste du sur le nombre de tranches
 * restantes (le flux web a toujours propose "tranche" sans verifier).
 */
final class MontantPaiement
{
    /** Codes d'erreur stables, traduits par l'appelant (FR/EN). */
    public const ERREUR_DEJA_SOLDE            = 'deja_solde';
    public const ERREUR_ECHEANCE_HORS_PERIMETRE = 'echeance_hors_perimetre';
    public const ERREUR_ECHEANCE_DEJA_PAYEE   = 'echeance_deja_payee';
    public const ERREUR_ECHEANCE_INTROUVABLE  = 'echeance_introuvable';
    public const ERREUR_MONTANT_INVALIDE      = 'montant_invalide';

    /** Montant minimum accepte par l'operateur (FCFA). */
    public const MONTANT_MINIMUM = 50;

    /**
     * Reste du sur un frais, jamais negatif.
     */
    public function resteDu(FraisApprenant $frais): int
    {
        return max(0, (int) $frais->montant_total - (int) $frais->montant_paye);
    }

    /**
     * Montant deja regle sur une echeance. Seuls les paiements valides
     * comptent : un paiement en_attente, echoue ou annule ne solde rien.
     */
    public function montantPayeSurEcheance(Echeancier $echeance): int
    {
        return (int) $echeance->paiements()
            ->where('statut', 'valide')
            ->sum('montant');
    }

    /**
     * Echeances du frais encore partiellement ou totally dues, de la plus
     * ancienne a la plus recente. Une echeance est soldee quand les
     * paiements valides covering son montant nominal.
     */
    public function echeancesDues(FraisApprenant $frais): Collection
    {
        return $frais->categorieFrais
            ->echeanciers()
            ->orderBy('numero_tranche')
            ->get()
            ->filter(fn (Echeancier $e) => $this->montantPayeSurEcheance($e) < (int) $e->montant)
            ->values();
    }

    /**
     * Prochaine echeance a payer : la plus basse non soldee. Null si le
     * calendrier est-epuise ou absent.
     */
    public function prochaineEcheance(FraisApprenant $frais): ?Echeancier
    {
        return $this->echeancesDues($frais)->first();
    }

    /**
     * Montant a debiter pour un paiement sur ce frais.
     *
     * @param  string      $type     'integral' ou 'tranche'
     * @param  Echeancier|null $echeance  echeance ciblee (id envoye par le client)
     * @return array{
     *     montant:int, numero_tranche:?int, echeancier_id:?int,
     *     type:string, erreur:?string
     * }
     */
    public function calculer(
        FraisApprenant $frais,
        string $type = 'integral',
        ?Echeancier $echeance = null
    ): array {
        $reste = $this->resteDu($frais);

        if ($reste <= 0) {
            return $this->echec(self::ERREUR_DEJA_SOLDE, 0);
        }

        if ($type !== 'tranche') {
            return $this->reussi($reste, null, null, 'integral');
        }

        // Le client ne peut cibler qu'une echeance de LA meme categorie de
        // frais : sinon il pourrait rattacher l'echeance d'un autre etablissement
        // (ou d'un autre bareme) a son propre paiement.
        if ($echeance !== null) {
            if ((int) $echeance->categorie_frais_id !== (int) $frais->categorie_frais_id) {
                return $this->echec(self::ERREUR_ECHEANCE_HORS_PERIMETRE, 0);
            }

            if ($this->montantPayeSurEcheance($echeance) >= (int) $echeance->montant) {
                return $this->echec(self::ERREUR_ECHEANCE_DEJA_PAYEE, 0);
            }

            // Plafonnement par le reste du : le bareme a pu changer entre
            // l'affichage du montant et la validation du paiement.
            return $this->reussi(
                min((int) $echeance->montant, $reste),
                (int) $echeance->numero_tranche,
                (int) $echeance->id,
                'tranche'
            );
        }

        // Pas d'echeance ciblee : on prend la prochaine echeance du calendrier.
        $prochaine = $this->prochaineEcheance($frais);

        if ($prochaine !== null) {
            return $this->reussi(
                min((int) $prochaine->montant, $reste),
                (int) $prochaine->numero_tranche,
                (int) $prochaine->id,
                'tranche'
            );
        }

        // Aucun calendrier exploitable : fractionnement equitable du reste du
        // sur les tranches restantes (jamais au-dela du reste du).
        $nbTranchesRestantes = max(1, (int) ($frais->categorieFrais->nb_tranches_max ?? 2) - $this->tranchesDejaPayees($frais));

        return $this->reussi(
            (int) ceil($reste / $nbTranchesRestantes),
            null,
            null,
            'tranche'
        );
    }

    /**
     * Nombre de tranches deja soldee (paiements valides portant un numero de
     * tranche), pour eviter de re-proposer la meme tranche.
     */
    public function tranchesDejaPayees(FraisApprenant $frais): int
    {
        return (int) $frais->paiements()
            ->where('statut', 'valide')
            ->whereNotNull('numero_tranche')
            ->distinct()
            ->count('numero_tranche');
    }

    /**
     * Total du encore du pour un apprenant (toutes categories, annee en cours).
     */
    public function resteDuApprenant(int $apprenantId): int
    {
        return (int) \App\Models\FraisApprenant::where('apprenant_id', $apprenantId)
            ->get()
            ->sum(fn (FraisApprenant $f) => $this->resteDu($f));
    }

    /**
     * Message utilisateur traduit (FR par defaut, EN si la locale de
     * l'API/le navigateur est en anglais). Centralise ici pour que le tunnel
     * web et l'API mobile parlent exactement la meme langue.
     */
    public function message(string $codeErreur): string
    {
        return match ($codeErreur) {
            self::ERREUR_DEJA_SOLDE              => __('payeur.montant_deja_solde'),
            self::ERREUR_ECHEANCE_HORS_PERIMETRE => __('payeur.montant_echeance_hors_perimetre'),
            self::ERREUR_ECHEANCE_DEJA_PAYEE     => __('payeur.montant_echeance_deja_payee'),
            self::ERREUR_ECHEANCE_INTROUVABLE    => __('payeur.montant_echeance_introuvable'),
            default                              => __('payeur.montant_invalide'),
        };
    }

    /**
     * @return array{montant:int, numero_tranche:?int, echeancier_id:?int, type:string, erreur:?string}
     */
    private function reussi(int $montant, ?int $numeroTranche, ?int $echeancierId, string $type): array
    {
        return [
            'montant'          => $montant,
            'numero_tranche'   => $numeroTranche,
            'echeancier_id'    => $echeancierId,
            'type'             => $type,
            'erreur'           => null,
        ];
    }

    private function echec(string $codeErreur, int $montant): array
    {
        return [
            'montant'        => $montant,
            'numero_tranche' => null,
            'echeancier_id'  => null,
            'type'           => 'integral',
            'erreur'         => $codeErreur,
        ];
    }
}
