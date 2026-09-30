<?php

use App\Models\ParametreSysteme;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $service = app(\App\Services\AangaraaPayService::class);
        $maintenant = now();

        foreach (array_keys(\App\Services\AangaraaPayService::TAUX_COMMISSION_PLANS) as $plan) {
            ParametreSysteme::definir(['taux_commission_'.$plan => $service->tauxCommissionPlan($plan)]);
        }
    }

    public function down(): void
    {
        // Les taux saisis par l'admin sont des donnees de configuration, pas
        // une structure : les retirer ici detruirait un reglage fait en
        // production. Migration sans rollback.
    }
};
