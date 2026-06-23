<?php

namespace App\Jobs;

use App\Models\Echeancier;
use App\Models\FraisApprenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAlerteImpayeJournaliere implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Chercher tous les frais impayés/partiels dont l'échéance est passée (depuis 1 jour)
        $dateHier = now()->subDay()->toDateString();

        $echeances = Echeancier::with(['categorieFrais.fraisApprenants.apprenant.parents'])
            ->whereDate('date_echeance', '<=', $dateHier)
            ->get();

        $nbAlertes = 0;

        foreach ($echeances as $echeance) {
            $categorie = $echeance->categorieFrais;
            if (!$categorie) continue;

            // Frais non entièrement réglés
            $fraisNonRegles = FraisApprenant::with('apprenant.parents')
                ->where('categorie_frais_id', $categorie->id)
                ->where('statut', '!=', 'regle')
                ->get();

            foreach ($fraisNonRegles as $frais) {
                $apprenant = $frais->apprenant;
                if (!$apprenant) continue;

                $reste = $frais->montant_total - $frais->montant_paye;
                if ($reste <= 0) continue;

                // Vérifier si une alerte a déjà été envoyée aujourd'hui (optionnel : utiliser un flag)
                // Pour l'instant, on envoie à chaque fois

                foreach ($apprenant->parents as $parent) {
                    if (!$parent->notif_email && !$parent->notif_sms) continue;

                    // Dispatcher le job d'alerte
                    dispatch(new SendAlerteImpaye(
                        $apprenant,
                        $categorie->nom,
                        $reste,
                        $echeance->date_echeance->format('d/m/Y')
                    ));

                    $nbAlertes++;
                }
            }
        }

        Log::channel('admin')->info(
            "F12 SendAlerteImpayeJournaliere : {$nbAlertes} alertes d'impayé envoyées.",
            ['date' => now()->toDateString()]
        );
    }
}
