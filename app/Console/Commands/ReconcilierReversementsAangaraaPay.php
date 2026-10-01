<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Services\AangaraaPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Tranche le sort des reversements « a verifier » SANS renvoyer d'argent.
 *
 * Le trou que cette commande comble, constate en production le 01/10/2026 :
 * une commission bloquee en `a_verifier` (reponse AangaraaPay perdue) restait
 * bloquee DEFINITIVEMENT. Ni le scheduler, ni `aangaraa:reversements:rejouer`
 * ne la touchaient — ce dernier refuse volontairement cet etat, et c'est
 * justifie : un rejeu peut payer deux fois.
 *
 * Il existe pourtant une sortie SANS risque : AangaraaPay expose
 * `/check_withdrawal_status`, qui ne fait que LIRE l'etat d'un retrait deja
 * enregistre. Cette reponse-ligne existait dans AangaraaPayService
 * (`verifierStatutRetrait()`) mais n'etait appelee par AUCUN code du projet.
 *
 * Cette commande s'en sert. Elle n'appelle jamais `/withdrawal` :
 *   SUCCESSFUL      -> l'argent est bien parti, la commission passe `prelevee`
 *   FAILED          -> rien n'est parti, la commission repasse `calculee`
 *                      (elle redevient eligible au rejeu normal, sans risque)
 *   PENDING/INCONNU -> on ne touche a rien, nouvel essai au prochain passage
 *
 * Le seul cas non traitable automatiquement est une commission `a_verifier`
 * SANS `reference_reversement` : le timeout a consomme l'identifiant, il n'y a
 * rien contre quoi interroger l'API. Ces lignes-la exigent un humain, et la
 * commande les signale explicitement plutot que de les ignorer.
 */
class ReconcilierReversementsAangaraaPay extends Command
{
    protected $signature = 'aangaraa:reversements:reconcilier
        {--id=* : Ne reconcilier que ces commissions (sinon toutes les « a verifier »)}
        {--dry-run : Afficher les verifications sans appeler AangaraaPay}';

    protected $description = "Tranche les reversements « a verifier » en LIRANT leur etat chez AangaraaPay. N'envoie aucun argent : ne fait qu'evaluer un retrait deja enregistre.";

    public function handle(AangaraaPayService $aangaraa): int
    {
        $requete = Commission::query()
            ->with(['etablissement', 'paiement'])
            ->where('statut', Commission::STATUT_A_VERIFIER);

        if ($ids = $this->option('id')) {
            $requete->whereIn('id', $ids);
        }

        $commissions = $requete->orderBy('id')->get();

        if ($commissions->isEmpty()) {
            $this->info('Aucun reversement « a verifier » a reconcilier.');

            return self::SUCCESS;
        }

        $this->line(sprintf('%d reversement(s) « a verifier ».', $commissions->count()));

        $sansReference = 0;
        $resolus      = 0;
        $enAttente    = 0;

        foreach ($commissions as $commission) {
            $etablissement = $commission->etablissement?->nom ?? '?';
            $montant       = number_format((int) $commission->montant_net_etablissement, 0, ',', ' ');

            $this->line(sprintf('  #%d  %s  %s FCFA  reference : %s',
                $commission->id,
                $etablissement,
                $montant,
                $commission->reference_reversement ?: 'AUCUNE'
            ));

            // ── Cas non traitable : le timeout a consomme l'identifiant ──
            //
            // Aucun appel API n'est possible sans identifiant, et deviner serait
            // pire que ne rien faire : on ne peut pas inventer la reference d'un
            // retrait qu'on ne sait pas nommer. Verification humaine obligatoire.
            if (! $commission->reference_reversement) {
                $sansReference++;
                $this->warn(sprintf(
                    '    #%d sans reference : INTERVENTION HUMAINE. Verifiez le retrait %s FCFA '
                    .'aupres de votre tableau AangaraaPay, puis corrigez le statut manuellement.',
                    $commission->id,
                    $montant
                ));
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line('    dry-run : aucune verification effectuee.');
                continue;
            }

            $operateur = $commission->etablissement?->operateur_momo_reversement ?? 'mtn';

            $resultat = $aangaraa->verifierStatutRetrait(
                $commission->reference_reversement,
                $operateur
            );

            $statut = $resultat['statut'] ?? 'INCONNU';

            if ($statut === 'SUCCESSFUL') {
                $commission->update([
                    'statut'      => Commission::STATUT_PRELEVEE,
                    'reversed_at' => now(),
                    'reversement_erreur' => null,
                ]);

                $resolus++;
                $this->info(sprintf('    #%d CONFIRME : l\'argent est bien parti, passage a « prelevee ».', $commission->id));
                Log::info('Reversement « a verifier » confirme par l\'API AangaraaPay', [
                    'commission_id' => $commission->id,
                    'reference'     => $commission->reference_reversement,
                    'montant'       => (int) $commission->montant_net_etablissement,
                ]);
                continue;
            }

            if ($statut === 'FAILED') {
                // Refus confirme par l'API : rien n'est parti. La commission
                // redevient eligible au rejeu NORMAL, qui ne partira que dans le
                // cadre du filet `calculee` deja existant.
                $commission->update([
                    'statut'             => Commission::STATUT_CALCULEE,
                    'reversement_erreur' => 'Echec confirme par AangaraaPay le ' . now()
                        . ' : aucun virement n\'est parti, reversement replique en attente.',
                ]);

                $resolus++;
                $this->info(sprintf('    #%d REFUSE : rien n\'est parti, retour a « calculee » pour rejeu.', $commission->id));
                Log::info('Reversement « a verifier » refute par l\'API AangaraaPay', [
                    'commission_id' => $commission->id,
                    'reference'     => $commission->reference_reversement,
                ]);
                continue;
            }

            // PENDING, NOT_FOUND, INCONNU : on ne suppose rien. La commission
            // reste `a_verifier` et sera reinterrogee au prochain passage.
            $enAttente++;
            $this->warn(sprintf('    #%d statut « %s » : aucune decision prise, nouvel essai au prochain passage.', $commission->id, $statut));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry-run : aucune modification effectuee.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info(sprintf(
            '%d resolu(s), %d en attente de reponse, %d sans reference (intervention humaine).',
            $resolus,
            $enAttente,
            $sansReference
        ));

        return self::SUCCESS;
    }
}
