<?php

namespace App\Jobs;

use App\Models\Echeancier;
use App\Models\FraisApprenant;
use App\Mail\RappelEcheanceMail;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSmsRelanceImpaye
{
    use Dispatchable;

    public function handle(): void
    {
        $dateJ5 = now()->addDays(5)->toDateString();

        $echeances = Echeancier::with(['categorieFrais.fraisApprenants.apprenant.parents'])
            ->whereDate('date_echeance', $dateJ5)
            ->get();

        $nbEnvoyes = 0;
        $nbEchecs  = 0;

        foreach ($echeances as $echeance) {
            $categorie = $echeance->categorieFrais;
            if (!$categorie) continue;

            $fraisNonRegles = FraisApprenant::with('apprenant.parents')
                ->where('categorie_frais_id', $categorie->id)
                ->where('statut', '!=', 'regle')
                ->get();

            foreach ($fraisNonRegles as $frais) {
                $apprenant = $frais->apprenant;
                if (!$apprenant) continue;

                $reste = $frais->montant_total - $frais->montant_paye;
                if ($reste <= 0) continue;

                foreach ($apprenant->parents as $parent) {
                    if (!$parent->notif_rappel_echeance) continue;
                    if (!$parent->email) { $nbEchecs++; continue; }

                    try {
                        Mail::to($parent->email)->send(new RappelEcheanceMail(
                            $apprenant,
                            $categorie->nom,
                            $reste,
                            $echeance->date_echeance->format('d/m/Y')
                        ));
                        $nbEnvoyes++;
                    } catch (\Exception $e) {
                        $nbEchecs++;
                        Log::channel('admin')->error('Erreur email rappel echeance', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        Log::channel('admin')->info("E07 SendSmsRelanceImpaye — date_echeance={$dateJ5} : {$nbEnvoyes} emails envoyés, {$nbEchecs} échecs.");
    }
}
