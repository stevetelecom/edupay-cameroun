<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le logo des etablissements doit s'afficher dans l'ANNUAIRE pendant
 * l'inscription (page onboarding), pas seulement sur les ecrans du payeur
 * deja connecte.
 *
 * Regression : OnboardingController::index() faisait
 *     ->get(['id','nom','ville','type','code_etablissement'])
 * sans la colonne 'logo'. La vue fait @if($etab->logo) ... @else avatar @endif,
 * donc le logo n'apparaitait JAMAIS a l'inscription : seul l'avatar initiale
 * s'affichait, meme pour les etablissements qui en possedent un.
 */
class LogoEtablissementOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function payeur(): User
    {
        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);

        $user = User::create([
            'nom'       => 'Njoya',
            'prenom'    => 'Ibrahim',
            'telephone' => '690000003',
            'email'     => 'logo.payeur@test.cm',
            'password'  => Hash::make('secret1234'),
            'profil'    => 'parent',
        ]);
        $user->assignRole('parent');

        return $user;
    }

    private function etablissement(string $nom, ?string $logo): Etablissement
    {
        return Etablissement::create([
            'nom'                => $nom,
            'code_etablissement' => 'LOGO-' . strtoupper(substr(md5($nom), 0, 4)),
            'type'               => 'privee',
            'statut_juridique'   => 'privee',
            'statut'             => 'actif',
            'region'             => 'Douala',
            'ville'              => 'Douala',
            'telephone'          => '650000000',
            'email'              => md5($nom) . '@test.cm',
            'logo'               => $logo,
        ]);
    }

    public function test_le_logo_est_selectionne_par_le_controleur_onboarding(): void
    {
        $etab = $this->etablissement('Ecole Avec Logo', 'logos/test-logo.svg');

        // Le controleur est la source du bug : on verifie la selection de colonnes.
        $charge = app(\App\Http\Controllers\Payeur\OnboardingController::class);
        $reflexion = new \ReflectionMethod($charge, 'index');
        $source = implode('', array_slice(
            file($reflexion->getFileName()),
            $reflexion->getStartLine() - 1,
            $reflexion->getEndLine() - $reflexion->getStartLine() + 1
        ));

        $this->assertStringContainsString("'logo'", $source,
            'index() doit selectionner la colonne logo, sinon @if($etab->logo) est toujours faux');
        $this->assertNotNull($etab->fresh()->logo, 'fixture : le logo doit etre enregistre');
    }

    public function test_le_logo_apparait_dans_lannuaire_de_linscription(): void
    {
        Storage::fake('public');
        $user = $this->payeur();
        $this->etablissement('Ecole Avec Logo', 'logos/test-logo.svg');

        $html = $this->actingAs($user)
            ->get(route('payeur.onboarding'))
            ->assertOk()
            ->getContent();

        // Le <img> du logo doit etre present : c'est la preuve que $etab->logo
        // a bien ete hydrate, et pas seulement stocke en base.
        $this->assertStringContainsString('storage/logos/test-logo.svg', $html,
            'le logo doit etre rendu dans l annuaire d inscription');
        $this->assertStringContainsString('Ecole Avec Logo', $html);
    }

    public function test_un_etablissement_sans_logo_tombe_sur_lavatar_initiale(): void
    {
        $user = $this->payeur();
        $this->etablissement('Ecole Sans Logo', null);

        $html = $this->actingAs($user)
            ->get(route('payeur.onboarding'))
            ->assertOk()
            ->getContent();

        // Le repli doit rester en place : pas d'image cassee ni de <img> vide.
        $this->assertStringNotContainsString('storage/', $html,
            'sans logo, on ne doit pas produire de src vers le stockage');
        $this->assertStringContainsString('Ecole Sans Logo', $html);
        // Avatar = initiale en majuscule du nom. Blade indente l'expression
        // sur sa propre ligne, donc on cherche la lettre isolee par des
        // espaces plutot que '>E<'.
        $this->assertMatchesRegularExpression('/>\s*E\s*</', $html,
            'le repli doit afficher l initiale du nom de l etablissement');
    }
}
