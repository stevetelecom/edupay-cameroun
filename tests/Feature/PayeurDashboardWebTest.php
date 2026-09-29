<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Garde-fou de la régression « ParseError payeur/dashboard.blade.php:615 » :
 * la directive @@json écrite dans un COMMENTAIRE JS était compilée par Blade
 * et produisait un json_encode() cassé (unexpected token ",").
 * Ce test rend la VRAIE page web (avec contrôleur + données) pour garantir
 * qu'aucune directive ne casse plus la vue.
 */
class PayeurDashboardWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_dashboard_payeur_se_rend_sans_erreur_de_syntaxe(): void
    {
        // Établissement de rattachement (champs NOT NULL de la migration)
        $etablissement = Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => 'Lycee Test Payeur',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'dashboard-payeur@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2025-2026',
        ]);

        // Payeur (profil parent) connecté via le guard web.
        // Le middleware role:parent|eleve exige le rôle Spatie « parent ».
        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);
        $payeur = User::factory()->create(['profil' => 'parent']);
        $payeur->assignRole('parent');

        // Un apprenant + frais pour alimenter donuts, histogramme et compteurs
        $apprenant = Apprenant::create([
            'etablissement_id'         => $etablissement->id,
            'nom'                      => 'Njoya',
            'prenom'                   => 'Ibrahim',
            'classe'                   => '6eme',
            'statut_paiement'          => 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
        $apprenant->parents()->attach($payeur->id, ['lien' => 'parent']);

        FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $etablissement->id,
                'nom'              => 'Scolarite',
                'montant_total'    => 50000,
                'annee_scolaire'   => '2025-2026',
            ])->id,
            'montant_total'   => 50000,
            'montant_paye'    => 20000,
            'statut'          => 'partiel',
            'annee_scolaire'  => '2025-2026',
        ]);

        $response = $this->actingAs($payeur)
            ->get(route('payeur.dashboard')) // routes/web.php:97 — groupe /espace
            ->assertOk();

        // Le ParseError initial se manifestait avant tout rendu HTML ; on
        // vérifie que la page contient bien les éléments du dashboard.
        $response->assertSee('chart-situation', false);
        $response->assertSee('chart-enfants', false);
        $response->assertSee('monterGraphiques', false);
    }
}
