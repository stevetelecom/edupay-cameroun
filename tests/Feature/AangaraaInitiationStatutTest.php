<?php

namespace Tests\Feature;

use App\Services\AangaraaPayService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Normalisation du statut renvoye par POST /no_redirect/payment.
 *
 * Le bug : la reponse reelle d'AangaraaPay pour Orange Money est
 *
 *   HTTP 201
 *   {
 *     "message": "PENDING",
 *     "data": {
 *       "payToken": "APT-...",
 *       "customerName": null,
 *       "amount": 500,
 *       "currency": "XAF"
 *     },
 *     "statusCode": 201,
 *     "payToken": "APT-..."
 *   }
 *
 * Autrement dit le statut est a la RACINE (`message`), pas dans `data.status`
 * comme le suppose la majorite du code. Sans `data.status`, le fallback
 * « pas de statut => FAILED » declarait le paiement mort alors que la
 * transaction venait d'etre creee chez l'operateur. L'utilisateur etait
 * renvoye vers la page d'echec pendant que son argent partait reellement.
 *
 * Regle fixee ici : un jeton de transaction sur HTTP 201 vaut au moins
 * PENDING. Seule l'absence de jeton signifie un echec reel.
 */
class AangaraaInitiationStatutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.aangaraa.api_url' => 'https://api.aangaraapay.test',
            'services.aangaraa.app_key' => 'cle-de-test',
        ]);
    }

    private function initier(array $reponse, int $codeHttp, string $operateur = 'Orange_Cameroon', ?string $returnUrl = null): array
    {
        Http::fake([
            'https://api.aangaraapay.test/no_redirect/payment' => Http::response($reponse, $codeHttp),
        ]);

        return (new AangaraaPayService())->initierPaiement(
            '691234567',
            500,
            'Test EduPay',
            'EP-TEST-0001',
            'https://edupay.test/paiement/webhook',
            $operateur,
            $returnUrl,
        );
    }

    public function test_statut_a_la_racine_avec_jeton_est_pending_et_non_failed(): void
    {
        $r = $this->initier([
            'message'    => 'PENDING',
            'data'       => [
                'payToken'     => 'APT-ORANGE-1',
                'customerName' => null,
                'amount'       => 500,
                'currency'     => 'XAF',
            ],
            'statusCode' => 201,
            'payToken'   => 'APT-ORANGE-1',
        ], 201);

        $this->assertTrue($r['succes'], 'Un 201 + payToken doit etre accepte.');
        $this->assertSame('PENDING', $r['statut']);
        $this->assertSame('APT-ORANGE-1', $r['pay_token']);
        $this->assertSame('Orange_Cameroon', $r['operateur']);
    }

    public function test_data_status_explicite_reste_prioritaire(): void
    {
        $r = $this->initier([
            'message'    => 'SUCCESSFUL',
            'data'       => [
                'status'   => 'SUCCESSFUL',
                'payToken' => 'APT-MTN-1',
            ],
            'statusCode' => 201,
        ], 201);

        $this->assertSame('SUCCESSFUL', $r['statut']);
        $this->assertSame('APT-MTN-1', $r['pay_token']);
        // Un SUCCESSFUL a l'initiation n'est pas un echec, mais la succes
        // d'initiation reste reservee au PENDING : la validation du paiement
        // passe par le polling/webhook, pas par la reponse de creation.
        $this->assertFalse($r['succes']);
    }

    public function test_echec_reel_sans_jeton_reste_echec(): void
    {
        $r = $this->initier([
            'message'    => 'FAILED',
            'data'       => ['payToken' => null],
            'statusCode' => 400,
        ], 400);

        $this->assertFalse($r['succes']);
        $this->assertSame('FAILED', $r['statut']);
        $this->assertNull($r['pay_token']);
    }

    public function test_reponse_sans_statut_ni_jeton_est_un_echec(): void
    {
        $r = $this->initier([
            'message' => 'Invalid phone number',
            'data'    => [],
        ], 400);

        $this->assertFalse($r['succes']);
        $this->assertSame('INVALID PHONE NUMBER', $r['statut']);
        $this->assertNull($r['pay_token']);
    }

    /**
     * Le payload doit reproduire l'exemple de la doc AangaraaPay champ par
     * champ. Seul `return_url` en manquait.
     */
    public function test_le_payload_reproduit_l_exemple_de_la_doc(): void
    {
        $this->initier(['message' => 'PENDING', 'data' => ['payToken' => 'APT-1']], 201, 'Orange_Cameroon');

        Http::assertSent(function ($request) {
            $b = $request->data();

            $this->assertSame('237691234567', $b['phone_number']);
            $this->assertSame('500', $b['amount']);
            $this->assertSame('XAF', $b['devise_id']);
            $this->assertSame('Orange_Cameroon', $b['operator']);
            $this->assertSame('cle-de-test', $b['app_key']);
            $this->assertSame('EP-TEST-0001', $b['transaction_id']);
            $this->assertSame('https://edupay.test/paiement/webhook', $b['notify_url']);

            return true;
        });
    }

    public function test_return_url_est_envoye_pour_orange_seulement(): void
    {
        // Orange : le champ de la doc est present, on teste si le push USSD
        // se met en place.
        $this->initier(
            ['message' => 'PENDING', 'data' => ['payToken' => 'APT-1']],
            201,
            'Orange_Cameroon',
            'https://edupay.test/espace/paiement/1/attente'
        );

        Http::assertSent(fn ($r) => ($r->data()['return_url'] ?? null)
            === 'https://edupay.test/espace/paiement/1/attente');

        // MTN : payload inchange, son prompt USSD *126# fonctionne deja.
        $this->initier(['message' => 'PENDING', 'data' => ['payToken' => 'APT-2']], 201, 'MTN_Cameroon');

        Http::assertSent(fn ($r) => ! array_key_exists('return_url', $r->data()));
    }
}