<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AangaraaPayService
{
    private string $apiUrl;
    private string $appKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.aangaraa.api_url'), '/');
        $this->appKey = config('services.aangaraa.app_key');
    }

    /**
     * Calcule les frais de service visibles par le payeur.
     * Barème dégressif — fusionné EduPay + AangaraaPay.
     * Le payeur ne voit qu'une seule ligne "Frais de service EduPay".
     */
    public function calculerFrais(int $montant): array
    {
        // Frais visibles (fusionné EduPay + AangaraaPay)
        $fraisVisibles = match(true) {
            $montant <= 10000  => 200,
            $montant <= 25000  => 400,
            $montant <= 50000  => 800,
            $montant <= 100000 => 1500,
            default            => 2500,
        };

        // Part AangaraaPay estimée à 2% (gérée en backend, invisible au payeur)
        $fraisAangaraa = (int) round($montant * 0.02);

        // Marge EduPay = frais visibles - part AangaraaPay
        $margeEdupay = max(0, $fraisVisibles - $fraisAangaraa);

        return [
            'montant_frais'       => $montant,          // Frais scolaires nets
            'frais_service'       => $fraisVisibles,    // Ce que voit le payeur
            'frais_aangaraa'      => $fraisAangaraa,    // Backend uniquement
            'marge_edupay'        => $margeEdupay,      // Gain EduPay
            'montant_total_paye'  => $montant + $fraisVisibles, // Total débité
        ];
    }

    /**
     * Reverser le net à l'établissement via API withdrawal AangaraaPay.
     * Appelé automatiquement après chaque paiement validé (webhook SUCCESSFUL).
     */
    public function reverserEtablissement(
        string $telephone,
        string $operateur,
        int    $montant,
        string $description
    ): array {
        try {
            $numero = $this->normaliserNumero($telephone);

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/aangaraa-pay/withdrawal', [
                    'phone_number' => $numero,
                    'amount'       => (string) $montant,
                    'description'  => $description,
                    'app_key'      => $this->appKey,
                    'operator'     => $operateur === 'orange' ? 'Orange_Cameroon' : 'MTN_Cameroon',
                ]);

            $data = $response->json();

            Log::info('AangaraaPay withdrawal', [
                'telephone' => $numero,
                'montant'   => $montant,
                'response'  => $data,
            ]);

            return [
                'succes'    => $response->successful(),
                'reference' => $data['data']['transaction_id'] ?? null,
                'message'   => $data['message'] ?? 'Erreur inconnue',
                'raw'       => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('AangaraaPay withdrawal exception', ['error' => $e->getMessage()]);
            return [
                'succes'    => false,
                'reference' => null,
                'message'   => 'Erreur : ' . $e->getMessage(),
                'raw'       => [],
            ];
        }
    }

    public function detecterOperateur(string $telephone): string
    {
        $numero = preg_replace('/\D/', '', $telephone);
        if (str_starts_with($numero, '237')) {
            $numero = substr($numero, 3);
        }
        $prefixe = (int) substr($numero, 0, 3);
        if (($prefixe >= 650 && $prefixe <= 654) || ($prefixe >= 670 && $prefixe <= 683)) {
            return 'MTN_Cameroon';
        }
        if (($prefixe >= 655 && $prefixe <= 659) || ($prefixe >= 690 && $prefixe <= 699)) {
            return 'Orange_Cameroon';
        }
        return 'ALL';
    }

    public function normaliserNumero(string $telephone): string
    {
        $numero = preg_replace('/\D/', '', $telephone);
        if (! str_starts_with($numero, '237')) {
            $numero = '237' . $numero;
        }
        return $numero;
    }

    public function initierPaiement(
        string $telephone,
        int    $montant,
        string $description,
        string $transactionId,
        string $notifyUrl
    ): array {
        $operateur = $this->detecterOperateur($telephone);
        $numero    = $this->normaliserNumero($telephone);

        try {
            $response = Http::timeout(30)
                ->post($this->apiUrl . '/no_redirect/payment', [
                    'phone_number'   => $numero,
                    'amount'         => (string) $montant,
                    'description'    => $description,
                    'app_key'        => $this->appKey,
                    'transaction_id' => $transactionId,
                    'notify_url'     => $notifyUrl,
                    'operator'       => $operateur,
                    'devise_id'      => 'XAF',
                ]);

            $data = $response->json();

            Log::info('AangaraaPay initier', [
                'transaction_id' => $transactionId,
                'operateur'      => $operateur,
                'response'       => $data,
            ]);

            return [
                'succes'    => $response->status() === 201,
                'pay_token' => $data['data']['payToken'] ?? null,
                'statut'    => $data['data']['status']   ?? 'FAILED',
                'operateur' => $operateur,
                'message'   => $data['message']          ?? 'Erreur inconnue',
                'raw'       => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('AangaraaPay initier exception', ['error' => $e->getMessage()]);
            return [
                'succes'    => false,
                'pay_token' => null,
                'statut'    => 'FAILED',
                'operateur' => $operateur,
                'message'   => 'Erreur de connexion : ' . $e->getMessage(),
                'raw'       => [],
            ];
        }
    }

    public function verifierStatut(string $payToken): array
    {
        try {
            $response = Http::timeout(15)
                ->post($this->apiUrl . '/aangaraa_check_status', [
                    'payToken' => $payToken,
                    'app_key'  => $this->appKey,
                ]);

            $data = $response->json();

            Log::info('AangaraaPay check_status', [
                'pay_token' => $payToken,
                'response'  => $data,
            ]);

            return [
                'statut'  => $data['status']  ?? 'FAILED',
                'succes'  => ($data['status'] ?? '') === 'SUCCESSFUL',
                'message' => $data['message'] ?? '',
                'raw'     => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('AangaraaPay check_status exception', ['error' => $e->getMessage()]);
            return [
                'statut'  => 'FAILED',
                'succes'  => false,
                'message' => $e->getMessage(),
                'raw'     => [],
            ];
        }
    }
}
