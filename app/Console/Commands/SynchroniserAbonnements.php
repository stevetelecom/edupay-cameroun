<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use App\Models\Etablissement;
use Illuminate\Console\Command;

/**
 * Recalcule le statut de chaque abonnement à partir de SES DATES.
 *
 * Le statut est une valeur dérivée (actif / grace_period / expire) : il
 * dépend uniquement de date_fin et grace_period_fin. Or il n'était
 * recalculé que par le middleware CheckAbonnement, c'est-à-dire au passage
 * d'un utilisateur de l'établissement sur une page.
 *
 * Conséquence observée le 27/09/2026 : un établissement dont la période
 * s'était terminée la veille restait affiché « actif » dans le back office
 * tant qu'aucun de ses utilisateurs ne s'était connecté, et le compteur
 * « abonnements actifs » incluait des périodes échues.
 *
 * Cette commande rend l'état lisible partout (back office, API, KPI) sans
 * dépendre d'une connexion. Elle ne modifie AUCUNE date : uniquement le
 * statut, et les colonnes dénormalisées de l'établissement.
 */
class SynchroniserAbonnements extends Command
{
    protected $signature = 'abonnements:synchroniser
                            {--dry-run : Afficher les changements sans les écrire}';

    protected $description = 'Recalcule le statut des abonnements et des établissements à partir des dates réelles';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $aCorriger   = 0;
        $etabCorriges = 0;

        // On ne traite que les lignes non expirées PUIS expirées : les
        // abonnements dont l'etablissement a été supprimé (FK nulle) sont
        // ignorés, ils n'ont aucun effet sur l'affichage.
        Abonnement::query()
            ->orderBy('id')
            ->chunkById(200, function ($abonnements) use ($dryRun, &$aCorriger, &$etabCorriges) {
                foreach ($abonnements as $abonnement) {
                    $etatReel = $abonnement->etat();

                    if ($abonnement->statutDesynchronise()) {
                        $aCorriger++;

                        $this->line(sprintf(
                            '  abonnement #%d (étab. %s) : statut %s devient %s  [fin %s, grâce %s]',
                            $abonnement->id,
                            $abonnement->etablissement_id ?? '?',
                            $abonnement->statut,
                            $etatReel,
                            $abonnement->date_fin?->format('d/m/Y') ?? '—',
                            $abonnement->grace_period_fin?->format('d/m/Y') ?? '—',
                        ));

                        if (! $dryRun) {
                            $abonnement->update(['statut' => $etatReel]);
                        }
                    }

                    if (! $abonnement->etablissement) {
                        continue;
                    }

                    // Colonnes dénormalisées : on ne touche que l'abonnement
                    // le plus récent de l'établissement, sinon une ancienne
                    // ligne renverrait le plan à « aucun ».
                    $estCourant = $abonnement->etablissement->abonnementCourant()?->id === $abonnement->id;

                    if (! $estCourant) {
                        continue;
                    }

                    $etablissement = $abonnement->etablissement;
                    $planAttendu  = $etatReel === 'expire' ? 'aucun' : $abonnement->plan;
                    $expireAttendu = $abonnement->date_fin?->toDateString();

                    $planActuel    = $etablissement->plan_abonnement ?: 'aucun';
                    $expireActuel  = $etablissement->abonnement_expire_le?->toDateString();

                    if ($planActuel !== $planAttendu || $expireActuel !== $expireAttendu) {
                        $etabCorriges++;

                        $this->line(sprintf(
                            '    etablissement #%d : plan %s devient %s, expire_le %s devient %s',
                            $etablissement->id,
                            $planActuel,
                            $planAttendu,
                            $expireActuel ?? 'NULL',
                            $expireAttendu ?? 'NULL',
                        ));

                        if (! $dryRun) {
                            $etablissement->update([
                                'plan_abonnement'      => $planAttendu,
                                'abonnement_expire_le' => $expireAttendu,
                            ]);
                        }
                    }
                }
            });

        $prefixe = $dryRun ? '[DRY-RUN] ' : '';

        $this->newLine();
        $this->info($prefixe . $aCorriger . ' abonnement(s) resynchronisé(s), ' . $etabCorriges . ' établissement(s) aligné(s).');

        $this->signalerDoublons($dryRun);

        return self::SUCCESS;
    }

    /**
     * Détecte les établissements possédant plusieurs abonnements en cours qui
     * se recouvrent (période identique ou seulement partielle).
     *
     * On ne supprime rien : ce sont des données financières, l'arbitrage
     * appartient à l'administrateur. L'alerte existe parce que
     * Admin\AbonnementController::store() désactive l'abonnement précédent
     * avant d'en créer un nouveau — un doublon actif ne peut donc pas être
     * le produit du formulaire, et cela gonfle mécaniquement le KPI
     * « revenus du mois » ainsi que le compteur « actifs ».
     */
    private function signalerDoublons(bool $dryRun): void
    {
        $signales = 0;

        Etablissement::query()
            ->whereHas('abonnements', fn ($q) => $q->whereIn('statut', ['actif', 'grace_period']))
            ->get()
            ->each(function (Etablissement $etablissement) use (&$signales) {
                $enCours = $etablissement->abonnements
                    ->filter(fn (Abonnement $a) => $a->etat() !== 'expire')
                    ->sortBy(fn (Abonnement $a) => $a->date_debut)
                    ->values();

                if ($enCours->count() < 2) {
                    return;
                }

                // 1) Périodes strictement identiques : le cas des abonnements
                //    #6 et #7 de l'établissement 1, insérés hors application.
                $identiques = $enCours
                    ->groupBy(fn (Abonnement $a) => $a->periodeAttendue())
                    ->filter(fn ($groupe) => $groupe->count() > 1);

                // 2) Recouvrement partiel : deux périodes distinctes dont les
                //    intervalles se chevauchent.
                $chevauchements = [];
                for ($i = 0; $i < $enCours->count() - 1; $i++) {
                    $a = $enCours[$i];
                    $b = $enCours[$i + 1];

                    if ($b->date_debut && $a->date_fin && $b->date_debut->lte($a->date_fin)) {
                        $chevauchements[] = ['#' . $a->id . ' et #' . $b->id, $a, $b];
                    }
                }

                if ($identiques->isEmpty() && $chevauchements === []) {
                    return;
                }

                $signales++;

                $this->newLine();
                $this->warn('ATTENTION : ' . $etablissement->nom . ' (étab. #' . $etablissement->id . ') : ' . $enCours->count() . ' abonnements en cours qui se recouvrent');

                foreach ($identiques as $periode => $groupe) {
                    $ids = $groupe->map(fn (Abonnement $a) => '#' . $a->id)->implode(', ');
                    $this->warn('   période identique ' . $periode . ' pour ' . $ids
                        . ' (' . $this->cumul($groupe) . ' FCFA/' . $groupe->first()->dureeEnMois() . ' mois)');
                }

                foreach ($chevauchements as [$libelle, $a, $b]) {
                    $this->warn('   recouvrement entre ' . $libelle . ' : '
                        . $a->periodeAttendue() . ' et ' . $b->periodeAttendue());
                }
            });

        if ($signales === 0) {
            return;
        }

        $this->warn('   Aucune suppression effectuée (données financières). À arbitrer manuellement.');
    }

    /** Cumul évité si les deux lignes étaient chacune facturées. */
    private function cumul($groupe): string
    {
        return number_format($groupe->sum('montant_mensuel'), 0, ',', ' ');
    }
}
