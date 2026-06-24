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
