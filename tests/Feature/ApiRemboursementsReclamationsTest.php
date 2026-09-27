<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\Reclamation;
use App\Models\Remboursement;
use App\Models\User;
use App\Support\TexteLibre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Audit L / M / N / O — remboursements, reclamations, categories de frais.
 *
 * L : l'apprenant d'un remboursement etait serialise en texte.
 * M : l'API ecrivait un statut de reclamation hors enum (SQL en MySQL).
 * N : un refus de remboursement pouvait etre enregistre sans motif, et la
 *     raison n'etait pas normalisee.
 * O : la categorie de frais n'etait exposee que sous `categorie` (chaine ou
 *     objet selon l'endpoint) alors que le mobile lit `categorieFrais`.
 */
class ApiRemboursementsReclamationsTest extends TestCase
{
    use RefreshDatabase;

    private const ANNEE = '2026-2027';

    private Etablissement $etablissement;

    private User $directeur;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'directeur']);

        $this->etablissement = Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => 'Ecole Remboursements',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Yaounde',
            'telephone'             => '650000000',
            'email'                 => 'remboursements@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
        ]);

        $this->directeur = User::factory()->create([
            'etablissement_id' => $this->etablissement->id,
        ]);
        $this->directeur->assignRole('directeur');
    }

    private function paiementValide(?Etablissement $etablissement = null, float $montant = 100000): Paiement
    {
        $etablissement ??= $this->etablissement;

        $apprenant = Apprenant::create([
            'etablissement_id'         => $etablissement->id,
            'matricule'                => 'MAT' . random_int(1000, 9999),
            'nom'                      => 'Fono',
            'prenom'                   => 'Chloe',
            'classe'                   => 'CM2',
            'statut_paiement'          => 'partiel',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        $frais = FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $etablissement->id,
                'nom'              => 'Scolarite',
                'montant_total'    => $montant,
                'annee_scolaire'   => self::ANNEE,
            ])->id,
            'montant_total'     => $montant,
            'montant_paye'      => $montant,
            'statut'            => 'regle',
            'annee_scolaire'    => self::ANNEE,
        ]);

        return Paiement::create([
            'user_id'            => $this->directeur->id,
            'apprenant_id'       => $apprenant->id,
            'frais_apprenant_id' => $frais->id,
            'montant'            => $montant,
            'frais_service'      => 1000,
            'montant_total_paye' => $montant + 1000,
            'frais_aangaraa'     => 1000,
            'marge_edupay'       => 0,
            'mode_paiement'      => 'mtn_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'valide',
            'telephone_paiement' => '650000000',
            'date_paiement'      => now(),
        ]);
    }

    private function demande(Remboursement|string $statut = 'en_attente', ?Etablissement $etablissement = null): Remboursement
    {
        return Remboursement::create([
            'paiement_id' => $this->paiementValide($etablissement)->id,
            'montant'     => 20000,
            'motif'       => 'Erreur de saisie',
            'statut'      => $statut,
            'initie_par'  => $this->directeur->id,
        ]);
    }

    // ─────────────────────────────────────────────
    // L — apprenant serialise
    // ─────────────────────────────────────────────

    public function test_le_remboursement_expose_l_apprenant_en_objet()
    {
        $this->demande();

        $ligne = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.remboursements.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertIsArray($ligne['paiement']['apprenant'], 'L\'apprenant doit etre un objet.');
        $this->assertArrayHasKey('id', $ligne['paiement']['apprenant']);
        $this->assertArrayHasKey('matricule', $ligne['paiement']['apprenant']);
        $this->assertArrayHasKey('classe', $ligne['paiement']['apprenant']);
        $this->assertSame('Chloe Fono', $ligne['paiement']['apprenant']['nom_complet']);
        $this->assertSame('Chloe Fono', $ligne['paiement']['apprenant_nom']);
        $this->assertNotSame('', (string) $ligne['paiement']['apprenant']['matricule']);
    }

    public function test_les_routes_acceptent_l_id_de_remboursement_ou_le_paiement_id_documente()
    {
        $remboursement = $this->demande();

        // 1. Identifiant du remboursement (comportement historique).
        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $remboursement->id))
            ->assertOk();

        // 2. Identifiant du paiement, conformement au contrat documente.
        $second = $this->demande();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $second->paiement_id))
            ->assertOk();

        $this->assertSame('approuve', $second->fresh()->statut);
    }

    public function test_une_demande_etrangere_ne_peut_pas_etre_traitee()
    {
        $autre = Etablissement::create([
            'code_etablissement'     => 'EP' . strtoupper(bin2hex(random_bytes(3))),
            'nom'                   => 'Autre Ecole',
            'type'                  => 'lycee_general',
            'statut_juridique'      => 'prive_laic',
            'region'                => 'centre',
            'ville'                 => 'Douala',
            'telephone'             => '650000001',
            'email'                 => 'autre@test.cm',
            'taux_commission'       => 0.05,
            'statut'                => 'actif',
            'annee_scolaire_active' => self::ANNEE,
        ]);

        $demande = $this->demande('en_attente', $autre);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $demande->id))
            ->assertForbidden();

        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    // ─────────────────────────────────────────────
    // P2 — remboursement apres reversement deja effectue
    // ─────────────────────────────────────────────

    public function test_remboursement_signale_le_claw_back_si_le_reversement_est_deja_parti()
    {
        $demande = $this->demande();

        Commission::create([
            'paiement_id'               => $demande->paiement_id,
            'etablissement_id'          => $this->etablissement->id,
            'montant_transaction'       => 100000,
            'taux'                      => 0.05,
            'montant_commission'        => 5000,
            'statut'                    => Commission::STATUT_PRELEVEE,
            'montant_net_etablissement' => 100000,
            'frais_aangaraa'            => 2200,
            'reference_reversement'     => 'REV-2026-77',
            'reversed_at'               => now(),
        ]);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $demande->id))
            ->assertOk();

        // L'argent est sorti de l'edupay ET deja verse a l'etablissement :
        // sans cet avertissement, le remboursement etait approuve en silence
        // et l'etablissement gardait une somme qui appartenait au parent.
        $this->assertNotNull($reponse->json('data.alerte_reversement'));
        $this->assertStringContainsString('deja ete effectue', $reponse->json('data.alerte_reversement'));
        $this->assertStringContainsString('REV-2026-77', $reponse->json('message'));
    }

    public function test_remboursement_sans_reversement_ne_produit_pas_dalerte()
    {
        $demande = $this->demande();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $demande->id))
            ->assertOk();

        $this->assertNull($reponse->json('data.alerte_reversement'));
        $this->assertSame('approuve', $demande->fresh()->statut);
    }

    public function test_remboursement_signale_le_claw_back_meme_si_le_reversement_echoue()
    {
        $demande = $this->demande();

        // Reverse en echec : l'argent n'est pas parti, donc l'edupay detient
        // toujours la somme — le remboursement au parent reste coherent, mais
        // le reversement bloque doit etre traite dans la foulle.
        Commission::create([
            'paiement_id'               => $demande->paiement_id,
            'etablissement_id'          => $this->etablissement->id,
            'montant_transaction'       => 100000,
            'taux'                      => 0.05,
            'montant_commission'        => 5000,
            'statut'                    => Commission::STATUT_ECHEC,
            'montant_net_etablissement' => 100000,
            'frais_aangaraa'            => 2200,
        ]);

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.approuver', $demande->id))
            ->assertOk();

        $this->assertNull($reponse->json('data.alerte_reversement'));
    }

    // ─────────────────────────────────────────────
    // N — motif de refus
    // ─────────────────────────────────────────────

    public function test_un_refus_sans_motif_est_rejete()
    {
        $demande = $this->demande();

        $reponse = $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.refuser', $demande->id), [])
            ->assertStatus(422);

        $this->assertNotEmpty($reponse->json('errors'));
        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    public function test_un_refus_accepte_le_motif_ou_son_alias_reponse_admin()
    {
        $demande = $this->demande();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.refuser', $demande->id), [
                'motif_refus' => "  Paiement  non   rattache\n\n\n  ",
            ])
            ->assertOk();

        $this->assertSame('refuse', $demande->fresh()->statut);
        // Normalise : espaces fusionnes, lignes vides reduites, bordures retirees.
        $this->assertSame('Paiement non rattache', $demande->fresh()->motif_refus);

        $demande2 = $this->demande();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson(route('api.v1.etablissement.remboursements.refuser', $demande2->id), [
                'reponse_admin' => 'Dossier incomplet',
            ])
            ->assertOk();

        $this->assertSame('Dossier incomplet', $demande2->fresh()->motif_refus);
    }

    public function test_le_motif_de_refus_est_expose_sous_les_deux_cles()
    {
        $demande = $this->demande('refuse');
        $demande->update(['motif_refus' => 'Double paiement']);

        $ligne = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.remboursements.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertSame('Double paiement', $ligne['motif_refus']);
        $this->assertSame('Double paiement', $ligne['reponse_admin']);
    }

    // ─────────────────────────────────────────────
    // M — reclamation : statut et normalisation
    // ─────────────────────────────────────────────

    public function test_une_reclamation_mobile_est_creee_avec_un_statut_valide()
    {
        $payeur = User::factory()->create();

        $data = $this->actingAs($payeur, 'sanctum')
            ->postJson(route('api.v1.reclamations.store'), [
                'sujet'       => '  Retard de paiement  ',
                'description' => "Le paiement n'apparait pas.\n\n\nMerci de verifier.",
            ])
            ->assertCreated()
            ->json('data');

        // 'ouverte' n'existe pas dans l'enum : la creation echouait en MySQL.
        $this->assertSame('ouvert', $data['statut']);
        $this->assertContains($data['statut'], ['ouvert', 'en_cours', 'resolu', 'rejete']);
        $this->assertSame('Retard de paiement', $data['sujet']);
        $this->assertSame("Le paiement n'apparait pas.\n\nMerci de verifier.", $data['description']);

        $this->assertDatabaseHas('reclamations', [
            'id'     => $data['id'],
            'statut' => 'ouvert',
        ]);
    }

    public function test_le_normalisateur_de_texte_libre()
    {
        $this->assertNull(TexteLibre::normaliser('   '));
        $this->assertNull(TexteLibre::normaliser(null));
        $this->assertSame('a b', TexteLibre::normaliser("a\t  b"));
        $this->assertSame('a b', TexteLibre::normaliser("a\n b"));
        $this->assertSame("a\n\nb", TexteLibre::normaliser("a\n\n\n\nb", multiligne: true));
        $this->assertSame('a b', TexteLibre::normaliser("a\x00\x07 b"));
        // Le contenu n'est jamais tronque en silence.
        $this->assertSame(str_repeat('a', 2000), TexteLibre::normaliser(str_repeat('a', 2000)));
    }

    // ─────────────────────────────────────────────
    // O — categorieFrais
    // ─────────────────────────────────────────────

    public function test_la_categorie_de_frais_est_exposee_sous_la_cle_canonique()
    {
        $this->demande();

        $ligne = $this->actingAs($this->directeur, 'sanctum')
            ->getJson(route('api.v1.etablissement.remboursements.index'))
            ->assertOk()
            ->json('data.0');

        $this->assertIsArray($ligne['paiement']['categorieFrais']);
        $this->assertSame('Scolarite', $ligne['paiement']['categorieFrais']['nom']);
        $this->assertSame(self::ANNEE, $ligne['paiement']['categorieFrais']['annee_scolaire']);
        // L'ancienne cle reste disponible.
        $this->assertSame('Scolarite', $ligne['paiement']['frais']);
    }

    public function test_le_dashboard_payeur_expose_categorie_frais_en_objet()
    {
        $payeur = User::factory()->create(['profil' => 'parent']);
        $apprenant = Apprenant::create([
            'etablissement_id'         => $this->etablissement->id,
            'nom'                      => 'Njoya',
            'prenom'                   => 'Ibrahim',
            'classe'                   => '6eme',
            'statut_paiement'          => 'impaye',
            'valide_par_etablissement' => true,
            'actif'                    => true,
        ]);

        FraisApprenant::create([
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => CategoriesFrais::create([
                'etablissement_id' => $this->etablissement->id,
                'nom'              => 'Cantine',
                'montant_total'    => 35000,
                'annee_scolaire'   => self::ANNEE,
            ])->id,
            'montant_total'     => 35000,
            'montant_paye'      => 0,
            'statut'            => 'impaye',
            'annee_scolaire'    => self::ANNEE,
        ]);

        $apprenant->parents()->attach($payeur->id, ['lien' => 'parent']);

        $apercu = $this->actingAs($payeur, 'sanctum')
            ->getJson(route('api.v1.dashboard'))
            ->assertOk()
            ->json('data.premier_frais_impaye');

        $this->assertIsArray($apercu['categorieFrais'], 'O : la categorie doit etre un objet.');
        $this->assertSame('Cantine', $apercu['categorieFrais']['nom']);
        $this->assertSame(self::ANNEE, $apercu['categorieFrais']['annee_scolaire']);
        // L'ancienne cle reste une chaine pour les clients existants.
        $this->assertSame('Cantine', $apercu['categorie']);
    }
}
