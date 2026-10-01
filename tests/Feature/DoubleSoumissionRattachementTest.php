<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * BUG OUVERT — double soumission du rattachement.
 *
 * Le pied du modal v3 propose « Confirmer le rattachement » (m-btn-confirmer),
 * dont le onclick est mConfirmerRattachement() :
 *
 *     function mConfirmerRattachement() {
 *         document.getElementById('m-onb-form').submit();
 *     }
 *
 * Le bouton n'est jamais desactive et il n'existe aucune contrainte d'unicite
 * sur la table pivot user_apprenant. Or OnboardingController::store() cree un
 * Apprenant (cas 3) avant de rattacher. Deux POST successifs — ce que produit un
 * double-clic, ou un simple re-envoi du navigateur — creent donc DEUX dossiers
 * apprenants identiques, tous deux affiches au payeur.
 *
 * C'est la meme famille de bug que le double retrait AangaraaPay corrige dans
 * ReverserEtablissementJob (reservation atomique avant l'appel externe).
 *
 * Ce test echoue aujourd'hui : il decrit le comportement attendu.
 */
class DoubleSoumissionRattachementTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        Role::firstOrCreate(['name' => 'parent', 'guard_name' => 'web']);

        $etab = Etablissement::create([
            'nom'                => 'Ecole Duplex',
            'code_etablissement' => 'DUP-1',
            'type'               => 'privee',
            'statut_juridique'   => 'privee',
            'statut'             => 'actif',
            'region'             => 'Douala',
            'ville'              => 'Douala',
            'telephone'          => '650000000',
            'email'              => 'duplex@test.cm',
        ]);

        $user = User::create([
            'nom'       => 'Ngono',
            'prenom'    => 'Paul',
            'telephone' => '690000002',
            'email'     => 'duplex.payeur@test.cm',
            'password'  => Hash::make('secret1234'),
            'profil'    => 'parent',
        ]);
        $user->assignRole('parent');

        return [$user, $etab];
    }

    private function poster(User $user, Etablissement $etab)
    {
        return $this->actingAs($user)->post(route('payeur.onboarding.store'), [
            'etablissement_id' => $etab->id,
            'prenom_apprenant' => 'Njoya',
            'nom_apprenant'    => 'Ibrahim',
            'classe'           => '6eme',
            'lien'             => 'parent',
        ]);
    }

    public function test_un_double_envoi_ne_duplique_pas_le_dossier_apprenant(): void
    {
        [$user, $etab] = $this->contexte();

        $this->poster($user, $etab)
            ->assertStatus(302)
            ->assertLocation(route('payeur.dashboard'));

        $this->assertSame(1, Apprenant::count(), 'un envoi cree un seul dossier apprenant');

        // Second envoi : ce que produit un double-clic sur « Confirmer ».
        $this->poster($user, $etab)->assertStatus(302);

        $this->assertSame(1, Apprenant::count(),
            'un double-clic sur « Confirmer » ne doit pas creer un second dossier apprenant');

        $this->assertSame(1, $user->apprenants()->count(),
            'le meme enfant ne doit pas etre rattache deux fois');
    }
}
