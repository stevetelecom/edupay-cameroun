<?php

namespace App\Jobs;

use App\Models\Commission;
use App\Services\AangaraaPayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Reverse le net à l'établissement via AangaraaPay.
 *
 * Sécurité (E-02 audit) : cet appel HTTP (timeout 30s) était auparavant
 * exécuté À L'INTÉRIEUR de la transaction DB verrouillée de
 * traiterPaiementValide(), ce qui pouvait maintenir un lockForUpdate()
 * sur la ligne paiement jusqu'à ~45s en cas de lenteur AangaraaPay —
 * risque de deadlock et saturation du pool de connexions sous charge
 * (CDC §6.4 exige 500 tx/min). Il est maintenant dispatché en job,
 * après le commit de la transaction, avec retry automatique.
 */
class ReverserEtablissementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 120, 300]; // 30s, 2min, 5min entre tentatives

    public function __construct(public int $commissionId)
    {
    }

    public function handle(AangaraaPayService $aangaraa): void
    {
        $commission = Commission::find($this->commissionId);

        if (! $commission || $commission->statut === 'prelevee') {
            return; // Déjà traité ou supprimé — idempotent
        }

        $etablissement = $commission->etablissement;

        if (! $etablissement) {
            Log::critical('ReverserEtablissementJob : établissement introuvable, reversement annulé', [
                'commission_id' => $commission->id,
            ]);
            return;
        }

        if (! $etablissement->numero_momo_reversement) {
            Log::critical('ReverserEtablissementJob : établissement sans numéro de reversement, reversement ANNULE — configurer numero_momo_reversement', [
                'commission_id'     => $commission->id,
                'etablissement_id'  => $etablissement->id,
                'paiement_id'       => $commission->paiement_id,
                'montant_bloque'    => $commission->montant_net_etablissement,
            ]);
            return;
        }

        $numeroReversement = $etablissement->numero_momo_reversement;
        $operateurRevers   = $etablissement->operateur_momo_reversement ?? 'mtn';

        // Garde de sécurité : on ne reverse jamais vers un numéro invalide.
        if (! preg_match('/^6\d{8}$/', preg_replace('/\D/', '', $numeroReversement))) {
            Log::critical('ReverserEtablissementJob : numéro de reversement invalide, reversement ANNULE', [
                'commission_id'    => $commission->id,
                'etablissement_id' => $etablissement->id,
                'numero_configure' => $numeroReversement,
            ]);
            return;
        }

        $resultat = $aangaraa->reverserEtablissement(
            telephone:   $numeroReversement,
            operateur:   $operateurRevers,
            montant:     $commission->montant_net_etablissement,
            description: 'Reversement EduPay — paiement #' . $commission->paiement_id
        );

        if ($resultat['succes']) {
            $commission->update([
                'statut'                => 'prelevee',
                'reference_reversement' => $resultat['reference'],
                'reversed_at'           => now(),
            ]);

            Log::info('Reversement établissement réussi', [
                'commission_id' => $commission->id,
                'reference'     => $resultat['reference'],
            ]);
        } else {
            Log::error('Échec reversement établissement', [
                'commission_id' => $commission->id,
                'message'       => $resultat['message'] ?? null,
                'tentative'     => $this->attempts(),
            ]);

            // Laisse le job échouer pour déclencher le retry automatique
            // (sauf à la dernière tentative où Laravel le marquera failed)
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 300);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('ReverserEtablissementJob définitivement échoué après 3 tentatives', [
            'commission_id' => $this->commissionId,
            'erreur'        => $exception->getMessage(),
        ]);
        // TODO : alerter l'admin (email) pour reversement manuel
    }
}
