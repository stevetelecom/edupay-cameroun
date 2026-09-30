<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use App\Services\AangaraaPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Garde-fous du parcours de paiement COTE API (le web les avait, l'API non).
 *
 * Deux manques corriges ici, tous deux verifies par lecture de code :
 *
 * 1. Le double debit. Le web (Payeur\PaiementController::initier:82-100)
 *    refuse d'initier un second paiement quand un `en_attente` de moins de
 *    5 minutes existe. L'API n'avait aucun controle : deux POST /paiements/
 *    initier sur la meme ligne de frais creaient deux Paiement distincts
 *    (chaque `reference` est unique, donc rien ne bloquait), le payeur etait
 *    debite deux fois et `montant_paye` etait incremente deux fois.
 *
 * 2. Le mode "carte" fantome. InitierPaiementRequest acceptait 'carte' alors
 *    que le web refusait. Aucun canal AangaraaPay ne gere la carte : le
 *    `match` operateur tombait dans son `default` (null), le prestataire
 *    deduisait MTN du numero et debitait en Mobile Money, le paiement etait
 *    ensuite comptabilise "Carte" dans les statistiques.
 */
class ApiPaiementGardeFousTest extends TestCase
{
    use RefreshDatabase;

    private User $payeur;

    private FraisApprenant $frais;

    private string $statutVerif = 'PENDING';

    private bool $aangaraaSimule = false;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'parent']);

        $etab = Etablissement::create([
            'code_etablissement' => 'ETABAPI01',
            'nom'               => 'Ecole Garde-Fous',
            'type'              => 'lycee_general',
            'statut_juridique'  => 'prive_laic',
            'region'            => 'centre',
            'ville'             => 'Yaounde',
            'telephone'         => '650000000',
            'email'             => 'gardefous@test.cm',
            'taux_commission'   => 0.05,
            'statut'            => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id' => $etab->id,
            'nom'              => 'Eleve',
            'prenom'           => 'Garde',
            'classe'           => '1ere',
            'statut_paiement'  => 'impaye',
            'actif'            => true,
            'valide_par_etablissement' => true,
        ]);

        $this->payeur = User::factory()->create();
        $this->payeur->assignRole('parent');
        $this->payeur->apprenants()->attach($apprenant->id, ['lien' => 'parent']);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etab->id,
            'nom'              => 'Scolarite',
            'montant_total'    => 50000,
            'fractionnable'    => true,
            'nb_tranches_max'  => 1,
            'annee_scolaire'   => '2026-2027',
            'actif'            => true,
        ]);

        $this->frais = FraisApprenant::create([
            'apprenant_id'     => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'    => 50000,
            'montant_paye'     => 0,
            'statut'           => 'impaye',
            'annee_scolaire'   => '2026-2027',
        ]);
    }

    /**
     * AangaraaPay simule : on verifie l'orchestration, pas le prestataire.
     *
     * Un SEUL mock est installe pour tout le test. Laravel met en cache
     * l'instance de controleur resolue sur la route, donc rebinder le
     * service entre deux requetes n'aurait aucun effet et le test passerait
     * a cote du comportement reel. `verifierStatut` lit donc l'etat du test.
     */
    private function simulerAangaraa(string $statutVerif = 'PENDING'): void
    {
        $this->statutVerif = $statutVerif;

        if ($this->aangaraaSimule) {
            return;
        }

        $mock = Mockery::mock(AangaraaPayService::class);

        // Memoire fidele de AangaraaPayService::normaliserNumero (ligne 487) :
        // le service renvoie toujours un 237XXXXXXXX, sans le « + ».
        $mock->shouldReceive('normaliserNumero')->andReturnUsing(function ($n) {
            $numero = preg_replace('/\D/', '', $n);
            if (str_starts_with($numero, '237')) {
                $numero = substr($numero, 3);
            }
            $numero = ltrim($numero, '0');

            return '237' . (strlen($numero) > 9 ? substr($numero, -9) : $numero);
        });

        $mock->shouldReceive('calculerFrais')->andReturnUsing(fn ($montant) => [
            'montant_frais'      => $montant,
            'frais_service'      => (int) ceil($montant * 0.023),
            'frais_aangaraa'     => (int) floor($montant * 0.022),
            'marge_edupay'       => 1,
            'taux_frais'         => 0.023,
            'taux_aangaraa'      => 0.022,
            'montant_total_paye' => $montant + (int) ceil($montant * 0.023),
        ]);

        // Forme fidele de AangaraaPayService::initierPaiement (ligne 618).
        $mock->shouldReceive('initierPaiement')->andReturnUsing(fn () => [
            'succes'    => true,
            'pay_token' => 'TOKEN_'.uniqid(),
            'statut'    => 'PENDING',
            'operateur' => 'MTN',
            'message'   => 'Paiement en attente',
            'raw'       => [],
        ]);

        // Utilise par traiterPaiementValide() pour figer le taux de
        // commission sur la Commission creee.
        $mock->shouldReceive('tauxCommissionEtablissement')->andReturn(0.023);

        $mock->shouldReceive('verifierStatut')->andReturnUsing(fn () => [
            'statut'  => $this->statutVerif,
            'succes'  => $this->statutVerif === 'SUCCESSFUL',
            'message' => $this->statutVerif === 'FAILED' ? 'Refus du prestataire' : 'en attente',
        ]);

        $this->app->instance(AangaraaPayService::class, $mock);
        $this->aangaraaSimule = true;
    }

    private function initier(array $surplus = [])
    {
        return $this->actingAs($this->payeur, 'sanctum')
            ->postJson(route('api.v1.paiements.initier'), array_merge([
                'frais_apprenant_id' => $this->frais->id,
                'telephone'          => '654862989',
                'mode_paiement'      => 'mtn_momo',
            ], $surplus));
    }

    public function test_un_deuxieme_paiement_en_attente_est_refuse_avec_le_paiement_bloquant(): void
    {
        $this->simulerAangaraa();

        $premier = $this->initier();
        $premier->assertCreated();

        $second = $this->initier();

        $second->assertStatus(409)
            ->assertJsonPath('code', 'paiement_en_cours')
            ->assertJsonPath('data.paiement_id', Paiement::first()->id);

        // Le heart du garde-fou : un seul Paiement en base.
        $this->assertSame(1, Paiement::count());
    }

    public function test_le_paiement_bloquant_mort_libere_le_champ(): void
    {
        $this->simulerAangaraa();

        $this->initier()->assertCreated();
        $this->assertSame(1, Paiement::count());

        // Le prestataire repond FAILED : le paiement doit etre solde et un
        // nouveau doit pouvoir partir, sinon le payeur reste bloque 5 min
        // pour un retrait qui a deja echoue.
        $this->simulerAangaraa('FAILED');

        $second = $this->initier();
        $second->assertCreated();

        $this->assertSame(2, Paiement::count());
        $this->assertSame(
            'echoue',
            Paiement::orderBy('id')->first()->statut,
            'Le paiement en attente qui a echoue doit etre marque comme tel.'
        );
    }

    public function test_un_paiement_confirme_chez_le_prestataire_ne_laisse_pas_de_second_debit(): void
    {
        $this->simulerAangaraa();

        $this->initier()->assertCreated();
        $this->assertSame(1, Paiement::count());

        // Le prestataire a en fait abouti entre-temps : le garde-fou doit le
        // solder (etape 1 : on observe le statut, l'etape 2 est le job).
        $this->simulerAangaraa('SUCCESSFUL');
        $this->initier()->assertStatus(409);

        $this->assertSame(1, Paiement::count(), 'Aucun second debit ne doit partir.');
    }

    public function test_un_paiement_annule_manuellement_ne_bloque_pas_le_suivant(): void
    {
        $this->simulerAangaraa();

        $this->initier()->assertCreated();

        Paiement::first()->update(['annule_manuellement' => true]);

        // Un paiement que le payeur a annule ne doit pas bloquer la reprise.
        $this->initier()->assertCreated();

        $this->assertSame(2, Paiement::count());
    }

    public function test_un_paiement_ancien_de_plus_de_cinq_minutes_ne_bloque_plus(): void
    {
        $this->simulerAangaraa();

        $this->initier()->assertCreated();

        // Hors fenetre de garde : le payeur a pu perdre son telephone,
        // il doit pouvoir relancer sans attendre.
        // `created_at` n'est pas fillable sur Paiement : un update() Eloquent
        // serait ignore en silence. On ecrit donc en base.
        DB::table('paiements')
            ->where('id', Paiement::first()->id)
            ->update(['created_at' => now()->subMinutes(6)]);

        $this->initier()->assertCreated();

        $this->assertSame(2, Paiement::count());
    }

    /**
     * Le polling du client ne doit surtout pas se retrouver bloque par le
     * throttle, sinon le payeur voit son paiement se figer.
     *
     * Ce qui compte n'est pas le volume total mais le debit INSTANTANE. Le
     * client reel interroge toutes les 5 s (paiement_attente.blade.php:
     * 144-146), donc 12 polls/min par paiement, 156 polls repartis sur 20 min.
     *
     * Le pire cas realiste n'est donc pas 156 polls d'un coup, c'est un
     * parent qui paie plusieurs enfants en meme temps : on modelise 6
     * paiements paralleles, soit 6 x 12 = 72 polls dans LA MEME minute, et on
     * verifie qu'aucun n'est refuse. Le throttle est a 120/min, la marge
     * reste donc confortable sans etre un trou.
     */
    public function test_le_polling_reel_du_payeur_n_est_pas_bloque_par_le_throttle(): void
    {
        $this->simulerAangaraa();

        // 6 paiements distincts = 6 enfants payes en parallele.
        $paiements = collect(range(1, 6))->map(function ($i) {
            $categorie = CategoriesFrais::create([
                'etablissement_id' => Etablissement::first()->id,
                'nom'              => 'Frais ' . $i,
                'montant_total'    => 10000,
                'fractionnable'    => false,
                'nb_tranches_max'  => 1,
                'annee_scolaire'   => '2026-2027',
                'actif'            => true,
            ]);
            $frais = FraisApprenant::create([
                'apprenant_id'       => $this->frais->apprenant_id,
                'categorie_frais_id' => $categorie->id,
                'montant_total'      => 10000,
                'montant_paye'       => 0,
                'statut'             => 'impaye',
                'annee_scolaire'     => '2026-2027',
            ]);

            $this->initier(['frais_apprenant_id' => $frais->id])->assertCreated();

            return $frais->id;
        });

        $this->assertSame(6, Paiement::where('statut', 'en_attente')->count());

        // 12 polls par paiement dans la meme minute, comme le client reel.
        $bloque = 0;
        for ($tour = 0; $tour < 12; $tour++) {
            foreach ($paiements as $fraisId) {
                $paiement = Paiement::where('frais_apprenant_id', $fraisId)->first();
                $r = $this->actingAs($this->payeur, 'sanctum')
                    ->postJson(route('api.v1.paiements.verifier', $paiement));

                if ($r->status() === 429) {
                    $bloque++;
                }
            }
        }

        $this->assertSame(0, $bloque, 'Le polling normal d\'un payeur ne doit jamais etre throttle.');
    }

    public function test_le_mode_carte_est_refuse_par_l_api(): void
    {
        $this->simulerAangaraa();

        $this->initier(['mode_paiement' => 'carte'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mode_paiement');

        // Surtout : aucun Paiement ne doit avoir ete cree avant le rejet.
        $this->assertSame(0, Paiement::count());
    }

    public function test_les_deux_modes_mobiles_reellement_supportes_passent(): void
    {
        foreach (['mtn_momo', 'orange_money'] as $mode) {
            // Le paiement precedent est solde, sinon le garde-fou le refuse a
            // juste titre : c'est exactement le comportement attendu.
            Paiement::query()->update(['statut' => 'echoue']);
            $this->frais->update(['montant_paye' => 0, 'statut' => 'impaye']);

            $this->initier(['mode_paiement' => $mode])->assertCreated();
        }

        $this->assertSame(2, Paiement::count());
    }
}
