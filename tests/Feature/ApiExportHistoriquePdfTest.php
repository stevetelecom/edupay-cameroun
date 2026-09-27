<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'export PDF de l'historique n'existait que sur le web
 * (`payeur.historique?export=pdf`), une route par cookie de session :
 * impossible à consommer depuis l'app mobile, qui ne peut télécharger
 * qu'avec un jeton. Aucun endpoint API ne rendait ce document.
 */
class ApiExportHistoriquePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;

    private User $autreParent;

    private Apprenant $apprenant;

    protected function setUp(): void
    {
        parent::setUp();

        $etablissement = $this->creerEtablissement('Ecole Export', 'EXP-2026');

        $this->parent = User::factory()->create([
            'email'          => 'parent.export@test.cm',
            'etablissement_id' => $etablissement->id,
        ]);
        $this->parent->apprenants()->attach($this->apprenant = $this->creerApprenant($etablissement, 'EXP-0001'), ['lien' => 'parent']);

        $this->autreParent = User::factory()->create([
            'email'          => 'parent.autre@test.cm',
            'etablissement_id' => $etablissement->id,
        ]);
        $this->autreParent->apprenants()->attach($this->creerApprenant($etablissement, 'EXP-0002'), ['lien' => 'parent']);
    }

    private function creerEtablissement(string $nom, string $code): Etablissement
    {
        return Etablissement::create([
            'code_etablissement'     => $code,
            'nom'                   => $nom,
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000003',
            'email'                 => 'export@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => '2026-2027',
        ]);
    }

    private function creerApprenant(Etablissement $etablissement, string $matricule): Apprenant
    {
        return Apprenant::create([
            'etablissement_id'         => $etablissement->id,
            'matricule'                => $matricule,
            'nom'                      => 'Nkoa',
            'prenom'                   => 'Irene',
            'classe'                   => '3eme',
            'statut_paiement'          => 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);
    }

    private function creerPaiement(User $user, Apprenant $apprenant, string $reference, string $date, int $montant = 25000): Paiement
    {
        $categorie = CategoriesFrais::create([
            'etablissement_id' => $apprenant->etablissement_id,
            'nom'              => 'Scolarite '.$reference,
            'montant_total'    => $montant,
            'actif'            => true,
            'annee_scolaire'   => '2026-2027',
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'       => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'annee_scolaire'     => '2026-2027',
            'montant_total'      => $montant,
            'montant_paye'       => $montant,
            'statut'             => 'regle',
        ]);

        return Paiement::create([
            'reference'          => $reference,
            'user_id'            => $user->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => $montant,
            'mode_paiement'      => 'mobile_money',
            'statut'             => 'valide',
            'date_paiement'      => $date,
        ]);
    }

    public function test_lexport_renvoie_un_pdf_telechargeable()
    {
        $this->creerPaiement($this->parent, $this->apprenant, 'PAY-EXPORT-1', '2026-09-01 10:00:00');

        $reponse = $this->actingAs($this->parent, 'sanctum')
            ->get(route('api.v1.paiements.export'));

        $reponse->assertOk();
        $reponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', (string) $reponse->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }

    public function test_lexport_refuse_les_visiteurs()
    {
        $this->getJson(route('api.v1.paiements.export'))->assertUnauthorized();
    }

    public function test_lexport_ne_contient_que_les_paiements_du_payeur()
    {
        $this->creerPaiement($this->parent, $this->apprenant, 'PAY-MIEN-1', '2026-09-01 10:00:00');
        $this->creerPaiement($this->autreParent, $this->autreParent->apprenants()->first(), 'PAY-AUTRE-1', '2026-09-02 10:00:00');

        $reponse = $this->actingAs($this->parent, 'sanctum')
            ->get(route('api.v1.paiements.export'));

        $reponse->assertOk();
        $this->assertSame(
            1,
            Paiement::where('user_id', $this->parent->id)->count(),
            'le paiement de lautre payeur ne doit pas etre exporte'
        );
    }

    public function test_lexport_accepte_une_periode()
    {
        $this->creerPaiement($this->parent, $this->apprenant, 'PAY-AVANT', '2026-01-05 10:00:00');
        $this->creerPaiement($this->parent, $this->apprenant, 'PAY-APRES', '2026-09-20 10:00:00');

        $reponse = $this->actingAs($this->parent, 'sanctum')
            ->get(route('api.v1.paiements.export').'?du=2026-09-01&au=2026-09-30');

        $reponse->assertOk();
        $this->assertStringContainsString('2026-09-01', (string) $reponse->headers->get('content-disposition'));
    }

    public function test_une_periode_invalide_est_refusee()
    {
        $this->actingAs($this->parent, 'sanctum')
            ->getJson(route('api.v1.paiements.export').'?du=pas-une-date')
            ->assertStatus(422)
            ->assertJsonValidationErrors('du');
    }

    public function test_une_periode_incoherente_est_refusee()
    {
        $this->actingAs($this->parent, 'sanctum')
            ->getJson(route('api.v1.paiements.export').'?du=2026-09-30&au=2026-09-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors('au');
    }

    public function test_lexport_sans_paiement_reste_un_pdf_valide()
    {
        $reponse = $this->actingAs($this->parent, 'sanctum')
            ->get(route('api.v1.paiements.export'));

        $reponse->assertOk();
        $this->assertStringStartsWith('%PDF', $reponse->getContent());
    }
}
