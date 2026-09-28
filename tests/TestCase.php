<?php

namespace Tests;

use App\Models\Abonnement;
use App\Models\Etablissement;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Abonnement en cours, pour les tests qui atteignent le back-office
     * etablissement.
     *
     * Le web (`routes/web.php:140`) fait passer `check.abonnement` sur tout
     * le groupe `etablissement`, l'API (`routes/api.php`) aussi depuis le
     * correctif de parite. Une fixture qui cree un etablissement `actif`
     * SANS abonnement obtient donc un 402 avant meme d'atteindre le
     * controleur : le test echouait sur 402 au lieu du code teste.
     */
    protected function abonnerEtablissement(Etablissement $etablissement, string $plan = 'premium', int $dureeMois = 12): Abonnement
    {
        return Abonnement::create([
            'etablissement_id' => $etablissement->id,
            'plan'             => $plan,
            'montant_mensuel'  => 10000,
            'date_debut'       => now()->subMonth()->toDateString(),
            'date_fin'         => now()->addMonths($dureeMois)->toDateString(),
            'grace_period_fin' => now()->addMonths($dureeMois + 1)->toDateString(),
            'statut'           => 'actif',
        ]);
    }
}
