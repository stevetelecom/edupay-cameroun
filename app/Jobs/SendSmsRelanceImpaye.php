<?php

namespace App\Jobs;

use App\Models\Echeancier;
use App\Models\FraisApprenant;
use App\Services\SmsService;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Mail\RappelEcheanceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendSmsRelanceImpaye
{
    use Dispatchable;

    public function handle(SmsService $smsService): void
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
                    if (!$parent->telephone) continue;

                    $message = sprintf(
                        "EduPay Cameroun : Rappel echeance dans 5 jours.\n%s %s - %s\nReste a payer : %s FCFA\nDate limite : %s\nPayez via l'app EduPay.",
                        $apprenant->nom,
                        $apprenant->prenom,
                        $categorie->nom,
                        number_format($reste, 0, ',', ' '),
                        $echeance->date_echeance->format('d/m/Y')
                    );

                    $ok = $smsService->envoyerRelance($parent->telephone, $message);
                    $ok ? $nbEnvoyes++ : $nbEchecs++;

                    // F12-B — Email rappel échéance
                    if ($parent->email && $parent->notif_rappel_echeance) {
                        try {
                            Mail::to($parent->email)->send(new RappelEcheanceMail(
                                $apprenant,
                                $categorie->nom,
                                $reste,
                                $echeance->date_echeance->format('d/m/Y')
                            ));
                        } catch (\Exception $e) {
                            Log::channel('admin')->error('Erreur email rappel echeance', ['error' => $e->getMessage()]);
                        }
                    }
                }
            }
        }

        Log::channel('admin')->info("E07 SendSmsRelanceImpaye — date_echeance={$dateJ5} : {$nbEnvoyes} SMS envoyes, {$nbEchecs} echecs.");
    }
}
