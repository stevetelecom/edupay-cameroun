<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\LogMasking;

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
                'telephone' => LogMasking::telephone($numero),
                'montant'   => $montant,
                'response'  => LogMasking::payloadReduit($data),
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
        // 680-683 appartiennent a Nexttel/Viettel, pas a MTN — ne pas les inclure.
        if (($prefixe >= 650 && $prefixe <= 654) || ($prefixe >= 670 && $prefixe <= 679)) {
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

        if (str_starts_with($numero, '237')) {
            $numero = substr($numero, 3);
        }

        $numero = ltrim($numero, '0');

        if (strlen($numero) > 9) {
            $numero = substr($numero, -9);
        }

        return '237' . $numero;
    }

    private function extraireMessageErreur(array $data, string $statutApi): string
    {
        $detail = $data['data']['description']
            ?? $data['data']['message']
            ?? $data['message']
            ?? null;

        if ($detail) {
            return (string) $detail;
        }

        return $statutApi === 'FAILED'
            ? 'Le paiement a été refusé par l\'opérateur Mobile Money.'
            : 'Erreur inconnue';
    }

    public function initierPaiement(
        string $telephone,
        int    $montant,
        string $description,
        string $transactionId,
        string $notifyUrl,
        ?string $operateurForce = null
    ): array {
        if ($this->appKey === '') {
            Log::error('AangaraaPay : AANGARAA_APP_KEY manquante');

            return [
                'succes'    => false,
                'pay_token' => null,
                'statut'    => 'FAILED',
                'operateur' => $operateurForce ?? 'ALL',
                'message'   => 'Configuration paiement incomplète (clé API manquante).',
                'raw'       => [],
            ];
        }

        $numero    = $this->normaliserNumero($telephone);
        $operateur = $operateurForce ?? $this->detecterOperateur($numero);

        try {
            $payload = [
                'phone_number'   => $numero,
                'amount'         => (string) $montant,
                'description'    => $description,
                'app_key'        => $this->appKey,
                'transaction_id' => $transactionId,
                'notify_url'     => $notifyUrl,
                'operator'       => $operateur,
                'devise_id'      => 'XAF',
            ];

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/no_redirect/payment', $payload);

            $data      = is_array($response->json()) ? $response->json() : [];
            $statutApi = $data['data']['status'] ?? 'FAILED';
            $payToken  = $data['data']['payToken'] ?? null;
            $message   = $this->extraireMessageErreur($data, $statutApi);


            Log::info('AangaraaPay initier', [
                'transaction_id' => $transactionId,
                'telephone'      => LogMasking::telephone($numero),
                'operateur'      => $operateur,
                'notify_url'     => $notifyUrl,
                'response'       => LogMasking::payloadReduit($data),
            ]);

            $succes = $response->status() === 201
                && $statutApi === 'PENDING'
                && ! empty($payToken);

            return [
                'succes'    => $succes,
                'pay_token' => $payToken,
                'statut'    => $statutApi,
                'operateur' => $operateur,
                'message'   => $message,
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
                'response'  => LogMasking::payloadReduit($data),
            ]);

            $reason  = $data['details']['reason'] ?? null;
            $message = match($reason) {
                'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED'
                    => 'Solde insuffisant ou limite atteinte. Rechargez votre compte Mobile Money.',
                'PAYER_NOT_FOUND'
                    => 'Numéro Mobile Money introuvable. Vérifiez votre numéro.',
                'EXPIRED'
                    => 'La demande a expiré. Veuillez réessayer.',
                'CANCELLED'
                    => 'Paiement annulé depuis votre téléphone.',
                default => $data['message'] ?? 'Paiement refusé par l\'opérateur.',
            };

            return [
                'statut'  => $data['status']  ?? 'FAILED',
                'succes'  => ($data['status'] ?? '') === 'SUCCESSFUL',
                'message' => $message,
                'reason'  => $reason,
                'raw'     => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('AangaraaPay check_status exception', ['error' => $e->getMessage()]);

            // Securite (E-03 audit) : une exception ici (timeout reseau, DNS,
            // JSON malforme...) ne signifie PAS que l'operateur a refuse le
            // paiement — on ne sait simplement pas. Retourner 'FAILED' faisait
            // marquer le paiement echoue a tort alors que l'argent pouvait avoir
            // ete debite cote MTN/Orange. On retourne 'INCONNU' : le code appelant
            // ne doit JAMAIS marquer echoue sur ce statut, seulement reessayer.
            return [
                'statut'  => 'INCONNU',
                'succes'  => false,
                'message' => 'Vérification impossible pour le moment — nouvelle tentative automatique.',
                'reason'  => 'ERREUR_TECHNIQUE',
                'raw'     => [],
            ];
        }
    }
}
