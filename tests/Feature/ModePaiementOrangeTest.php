<?php

namespace Tests\Feature;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Le mode de paiement choisi doit etre celui REELLEMENT envoye au serveur.
 *
 * Constat en production le 01/10/2026 : paiement 76, tel 237693723200
 * (Orange Money d'apres le prefixe 693) enregistre avec
 * `mode_paiement = mtn_momo` et `operateur = MTN_Cameroon`.
 *
 * La cause n'etait pas la detection de l'operateur, qui faisait correctement
 * son travail : `selPay()` repeignait l'ecran (bordure, libelle, pastille)
 * mais ne cochait pas le `<input type="mode_paiement">` correspondant. Le radio
 * MTN portait `checked` en dur dans le HTML, donc tout formulaire partait en
 * `mtn_momo`, y compris apres une detection Orange.
 *
 * Consequencefinanciere : AangaraaPay recusait un numero Orange avec
 * `operator: MTN_Cameroon`, et le payeur se retrouvait sur la page d'attente MTN
 * avec les consignes USSD de MTN, alors que son numero etait Orange.
 */
class ModePaiementOrangeTest extends TestCase
{
    use RefreshDatabase;

    private User $payeur;

    private FraisApprenant $frais;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'parent']);

        $etab = Etablissement::create([
            'code_etablissement' => 'ETABORANGE',
            'nom'               => 'Ecole Orange',
            'type'              => 'lycee_general',
            'statut_juridique'  => 'prive_laic',
            'region'            => 'centre',
            'ville'             => 'Yaounde',
            'telephone'         => '650000000',
            'email'             => 'orange@test.cm',
            'taux_commission'   => 0.05,
            'statut'            => 'actif',
        ]);

        $apprenant = Apprenant::create([
            'etablissement_id'         => $etab->id,
            'nom'                      => 'Eleve',
            'prenom'                   => 'Orange',
            'classe'                   => '1ere',
            'statut_paiement'          => 'impaye',
            'actif'                    => true,
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
            'apprenant_id'      => $apprenant->id,
            'categorie_frais_id' => $categorie->id,
            'montant_total'     => 50000,
            'montant_paye'      => 0,
            'statut'            => 'impaye',
            'annee_scolaire'    => '2026-2027',
        ]);
    }

    /**
     * La fonction de detection est evaluee par Node : c'est du JS de vue, pas
     * du PHP, et c'est exactement la que le bug se trouvait.
     */
    private function evaluerDetection(string $numero): string
    {
        $vue = file_get_contents(resource_path('views/payeur/paiement.blade.php'));

        preg_match('/function detecterOperateurLocal\(valeur\) \{(.*?)\n\}/s', $vue, $m);
        $corps = $m[1] ?? null;
        $this->assertNotNull($corps, 'detecterOperateurLocal introuvable dans la vue.');

        preg_match('/function selPay\(n\) \{(.*?)\n\}/s', $vue, $m2);
        $selPay = $m2[1] ?? null;
        $this->assertNotNull($selPay, 'selPay introuvable dans la vue.');

        $script = <<<JS
        function detecterOperateurLocal(valeur) {
        {$corps}
        }
        const PAYEUR_L10N = {numero_mtn:'MTN',ph_mtn:'MTN',op_mtn:'MTN',numero_orange:'Orange',ph_orange:'Orange',op_orange:'Orange'};
        const elements = {
            pm1: { style:{} }, pm2: { style:{} },
            'pay-momo-lbl': { textContent:'' },
            'pay-momo-input': { placeholder:'' },
            'pay-op-dot': { style:{} },
            'pay-op-label': { textContent:'' },
        };
        // Un vrai groupe de radios HTML decoche automatiquement les freres :
        // il faut reproduire cette semantique, sinon le test passerait/failait
        // pour une mauvaise raison. Un simple objet `{ checked:true }` laissait
        // les deux radios a `true` en meme temps.
        let mtnChecked = true, orangeChecked = false;
        const radioMtn = { get checked(){ return mtnChecked; },
                           set checked(v){ mtnChecked = v; if (v) orangeChecked = false; } };
        const radioOrange = { get checked(){ return orangeChecked; },
                              set checked(v){ orangeChecked = v; if (v) mtnChecked = false; } };
        const radios = { mtn_momo: radioMtn, orange_money: radioOrange };
        globalThis.document = {
            getElementById: (id) => elements[id] || null,
            querySelector: (sel) => {
                const m = sel.match(/value="([^"]+)"/);
                return m ? radios[m[1]] : null;
            },
        };
        function selPay(n) {
        {$selPay}
        }
        const r = detecterOperateurLocal(process.argv[2]);
        if (r === 'mtn') selPay(1);
        else if (r === 'orange') selPay(2);
        console.log(JSON.stringify({
            detection: r,
            radio: radios.mtn_momo.checked ? 'mtn_momo' : 'orange_money',
            coherent: !(radios.mtn_momo.checked && radios.orange_money.checked),
        }));
        JS;

        $fichier = tempnam(sys_get_temp_dir(), 'det').'.js';
        file_put_contents($fichier, $script);

        exec('node ' . escapeshellarg($fichier) . ' ' . escapeshellarg($numero) . ' 2>&1', $out, $code);
        unlink($fichier);

        $this->assertSame(0, $code, 'Erreur Node : ' . implode("\n", $out));
        $this->assertNotEmpty($out, 'Node n a rien renvoye.');

        $decode = json_decode(trim($out[0]), true);
        $this->assertIsArray($decode, 'Sortie JSON invalide : ' . $out[0]);
        $this->assertTrue($decode['coherent'], 'Les deux radios ne peuvent pas etre coches en meme temps.');

        return $decode['radio'];
    }

    public static function numerosEtModesAttendus(): array
    {
        return [
            'Orange 693'   => ['693723200', 'orange_money'],
            'Orange 695'   => ['695000001', 'orange_money'],
            'Orange 688'   => ['688681618', 'orange_money'],
            'Orange 657'   => ['657134202', 'orange_money'],
            'Orange 640'   => ['640123456', 'orange_money'],
            'MTN 674'      => ['674000712', 'mtn_momo'],
            'MTN 650'      => ['650000000', 'mtn_momo'],
            'MTN 654'      => ['654862989', 'mtn_momo'],
        ];
    }

    #[DataProvider('numerosEtModesAttendus')]
    public function test_le_radio_suit_la_detection_du_prefixe(string $numero, string $modeAttendu): void
    {
        $this->assertSame(
            $modeAttendu,
            $this->evaluerDetection($numero),
            "Le mode POSTe doit suivre la detection pour le numero {$numero}."
        );
    }

    public function test_la_detection_ne_laisse_pas_le_radio_mtn_par_defaut(): void
    {
        // Le cas exact du paiement 76 : un 693 (Orange) qui partait en MTN.
        $mode = $this->evaluerDetection('693723200');

        $this->assertSame('orange_money', $mode);
        $this->assertNotSame('mtn_momo', $mode, 'Un numero Orange ne doit jamais partir en mtn_momo.');
    }

    public function test_loperateur_affiche_replie_sur_mode_paiement_quand_la_colonne_est_vide(): void
    {
        // Paiement 74 en production : mode orange_money, operateur NULL, parce
        // que la colonne n'est ecrite qu'APRES la reponse de l'API.
        $paiement = Paiement::create([
            'user_id'            => $this->payeur->id,
            'apprenant_id'       => $this->frais->apprenant_id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => 50000,
            'frais_service'      => 1150,
            'montant_total_paye' => 51150,
            'mode_paiement'      => 'orange_money',
            'type_paiement'      => 'integral',
            'statut'             => 'echoue',
            'operateur'          => null,
            'telephone_paiement' => '237657134202',
            'date_paiement'      => now(),
        ]);

        $this->assertSame('Orange_Cameroon', $paiement->operateurAffiche());
    }

    public function test_la_colonne_operateur_lorsqu_elle_est_remplie_prime(): void
    {
        $paiement = Paiement::create([
            'user_id'            => $this->payeur->id,
            'apprenant_id'       => $this->frais->apprenant_id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => 50000,
            'frais_service'      => 1150,
            'montant_total_paye' => 51150,
            'mode_paiement'      => 'orange_momo',
            'type_paiement'      => 'integral',
            'statut'             => 'en_attente',
            'operateur'          => 'MTN_Cameroon',
            'telephone_paiement' => '237654862989',
            'pay_token'          => 'uuid-mtn',
            'date_paiement'      => now(),
        ]);

        // Ce que l'API a reellement confirme prime sur le mode saisi.
        $this->assertSame('MTN_Cameroon', $paiement->operateurAffiche());
    }

    public function test_la_page_d_attente_affiche_orange_meme_si_operateur_est_vide(): void
    {
        $paiement = Paiement::create([
            'user_id'            => $this->payeur->id,
            'apprenant_id'       => $this->frais->apprenant_id,
            'frais_apprenant_id' => $this->frais->id,
            'montant'            => 50000,
            'frais_service'      => 1150,
            'montant_total_paye' => 51150,
            'mode_paiement'      => 'orange_money',
            'type_paiement'      => 'integral',
            'statut'             => 'echoue',
            'operateur'          => null,
            'telephone_paiement' => '237657134202',
            'date_paiement'      => now(),
        ]);

        $vue = $this->actingAs($this->payeur)
            ->get(route('payeur.paiement.attente', $paiement))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Orange Money', $vue);
        $this->assertMatchesRegularExpression(
            '/id="msg-attente-detail-mtn"[^>]*display:none;/',
            $vue,
            'Sans colonne operateur, un paiement orange_money ne doit pas retomber sur les consignes MTN.'
        );
    }
}
