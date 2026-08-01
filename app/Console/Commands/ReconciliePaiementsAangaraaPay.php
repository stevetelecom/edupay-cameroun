<?php

namespace App\Console\Commands;

use App\Http\Controllers\Payeur\PaiementController;
use App\Models\Paiement;
use Illuminate\Console\Command;

class ReconciliePaiementsAangaraaPay extends Command
{
    protected $signature = 'aangaraa:reconcilie';
    protected $description = "Reverifie les paiements en_attente aupres d'AangaraaPay (filet de securite webhook/polling)";

    public function handle(PaiementController $controller): int
    {
        $paiements = Paiement::where('statut', 'en_attente')
            ->whereNotNull('pay_token')
            ->where('created_at', '<=', now()->subMinutes(2))
            ->where('created_at', '>=', now()->subHours(24))
            ->get();

        foreach ($paiements as $paiement) {
            $controller->reconcilierPaiement($paiement);
        }

        $this->info($paiements->count() . ' paiement(s) reverifie(s).');

        return self::SUCCESS;
    }
}
