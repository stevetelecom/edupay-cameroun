<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\LogMasking;

class AangaraaPayService
{
    /**
     * Taux reellement preleve par AangaraaPay sur un reversement, mesure le
     * 27/09/2026 sur l'API de production : reponse
     * `{"commission_rate":2.2,"commission_amount":0.02,"total_required":1.02}`
     * pour un virement de 1,00 — soit 2,2 % payes EN PLUS du montant livre.
     *
     * L'ancien code estimait 2 %, ce qui sous-estimait le cout reel et,
     * combine avec max(0, ...), affichait une marge de 0 sur la plupart des
     * montants (a 50 000 FCFA : 800 de frais visibles pour 1 100 preleves).
     * Ce taux sert uniquement a la COMPTABILITE interne : le bareme visible
     * reste fixe par le bareme ci-dessous, c'est une decision commerciale.
     */
    public const TAUX_AANGARAA = 0.022;

    /** Bareme des frais visibles par le payeur (fusion EduPay + AangaraaPay). */
    public const BAREME_FRAIS = [
        10000  => 200,
        25000  => 400,
        50000  => 800,
        100000 => 1500,
    ];

    public const FRAIS_PAR_DEFAUT = 2500;

    private string $apiUrl;
    private string $appKey;

    public function __construct()
    {
        $this->apiUrl = rtrim(config('services.aangaraa.api_url'), '/');
        $this->appKey = config('services.aangaraa.app_key');
    }

    /**
     * Verifie que l'URL de notification AangaraaPay est exploitable.
     *
     * Le controle ne portait que sur `localhost` / `127.0.0.1` : une coquille
     * de domaine (`gsi2026` ecrit `qsi2026`) passait le controle en silence,
     * et TOUS les callbacks AangaraaPay etaient perdus. Les paiements
     * restaient encaisse grace a la reconciliation, mais avec minutes de
     * retard et sans trace de l'erreur.
     *
     * @return array{ok:bool, niveau:string, raison:?string}
     */
    public function verifierNotifyUrl(?string $notifyUrl): array
    {
        if (! $notifyUrl) {
            return ['ok' => false, 'niveau' => 'erreur', 'raison' => 'notify_url vide'];
        }

        $hote = parse_url($notifyUrl, PHP_URL_HOST);

        if (! $hote) {
            return ['ok' => false, 'niveau' => 'erreur', 'raison' => 'notify_url illisible : '.$notifyUrl];
        }

        // localhost/127.0.0.1 : AangaraaPay n'a aucune chance de joindre ce
        // serveur, et aucun callback ne parviendra jamais.
        if (in_array(strtolower($hote), ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            return ['ok' => false, 'niveau' => 'erreur', 'raison' => sprintf(
                'notify_url (%s) pointe vers la machine locale : aucun callback AangaraaPay ne peut aboutir.',
                $hote
            )];
        }

        $hoteAttendu = parse_url((string) config('app.url'), PHP_URL_HOST);

        // En developpement APP_URL vaut souvent http://localhost:8000 : on ne
        // compare alors que notify_url est lui-meme public, sinon le controle
        // hurlerait a tort sur un poste local.
        $attenduEstPublic = $hoteAttendu
            && ! in_array(strtolower($hoteAttendu), ['localhost', '127.0.0.1', '0.0.0.0'], true);

        if ($attenduEstPublic && strcasecmp($hote, $hoteAttendu) !== 0) {
            return ['ok' => false, 'niveau' => 'erreur', 'raison' => sprintf(
                'notify_url (%s) ne pointe pas vers APP_URL (%s) : tous les callbacks AangaraaPay seront perdus.',
                $hote,
                $hoteAttendu
            )];
        }

        return ['ok' => true, 'niveau' => 'ok', 'raison' => null];
    }

    /**
     * Calcule les frais de service visibles par le payeur.
     * Barème dégressif — fusionné EduPay + AangaraaPay.
     * Le payeur ne voit qu'une seule ligne "Frais de service EduPay".
     */
    public function calculerFrais(int $montant): array
    {
        // Frais visibles (fusion EduPay + AangaraaPay) : barème unique, declare
        // en constante pour ne pas avoir deux définitions qui divergent.
        $fraisVisibles = self::FRAIS_PAR_DEFAUT;

        foreach (self::BAREME_FRAIS as $plafond => $frais) {
            if ($montant <= $plafond) {
                $fraisVisibles = $frais;
                break;
            }
        }

        // Cout reel du reversement, mesuré chez AangaraaPay (2,2 %).
        $fraisAangaraa = (int) round($montant * self::TAUX_AANGARAA);

        // Marge EduPay = frais visibles - part AangaraaPay. Le max(0, ...) est
        // conserve : le bareme visible releve d'une decision commerciale, on ne
        // le change pas ici. En revanche le deficit n'est plus silencieux.
        $margeEdupay = max(0, $fraisVisibles - $fraisAangaraa);

        if ($fraisAangaraa > $fraisVisibles) {
            Log::warning('Frais de service inferieurs au reversement AangaraaPay', [
                'montant'          => $montant,
                'frais_encaisses'  => $fraisVisibles,
                'cout_reversement' => $fraisAangaraa,
                'perte'            => $fraisAangaraa - $fraisVisibles,
            ]);
        }

        return [
            'montant_frais'       => $montant,          // Frais scolaires nets
            'frais_service'       => $fraisVisibles,    // Ce que voit le payeur
            'frais_aangaraa'      => $fraisAangaraa,    // Cout reel du reversement
            'marge_edupay'        => $margeEdupay,      // Gain EduPay
            'montant_total_paye'  => $montant + $fraisVisibles, // Total débité
        ];
    }

    /**
     * Reverser le net à l'établissement via API withdrawal AangaraaPay.
     * Appelé automatiquement après chaque paiement validé (webhook SUCCESSFUL).
     *
     * `outcome` distingue trois situations que l'ancien code confondait en un
     * simple `succes: false`, avec des consquences opposees sur l'argent :
     *   refuse       AangaraaPay a repondu « non » (solde insuffisant, cle
     *                invalide...) : RIEN n'est parti, on peut reessayer.
     *   indetermine  aucune reponse exploitable (timeout, 5xx) : l'argent est
     *                PEUT-ETRE parti, on ne doit surtout pas renvoyer a l'aveugle.
     *   succes       virement enregistre par AangaraaPay.
     */
    public function reverserEtablissement(
        string $telephone,
        string $operateur,
        int    $montant,
        string $description
    ): array {
        try {
            $numero = $this->normaliserNumero($telephone);
            $operateurApi = $operateur === 'orange' ? 'Orange_Cameroon' : 'MTN_Cameroon';

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/aangaraa-pay/withdrawal', [
                    'phone_number' => $numero,
                    'amount'       => (string) $montant,
                    'description'  => $description,
                    'app_key'      => $this->appKey,
                    'operator'     => $operateurApi,
                ]);

            $data = $response->json();
            $reference = $data['data']['transaction_id'] ?? null;

            Log::info('AangaraaPay withdrawal', [
                'telephone'          => LogMasking::telephone($numero),
                'montant'            => $montant,
                'operateur_demande'  => $operateurApi,
                'operateur_repondu'  => $data['data']['operator'] ?? null,
                'http'               => $response->status(),
                'reference'          => $reference,
                'response'           => LogMasking::payloadReduit($data),
            ]);

            // 5xx ou 2xx sans reference : AangaraaPay n'a pas tranche, l'argent
            // peut avoir ete engage ou non. On ne suppose rien.
            if ($response->serverError() || ($response->successful() && ! $reference)) {
                return [
                    'succes'    => false,
                    'outcome'   => 'indetermine',
                    'reference' => $reference,
                    'message'   => $data['message'] ?? 'Réponse AangaraaPay inexploitable (HTTP '.$response->status().')',
                    'raw'       => $data,
                ];
            }

            return [
                'succes'    => $response->successful() && (bool) $reference,
                'outcome'   => $response->successful() && $reference ? 'succes' : 'refuse',
                'reference' => $reference,
                'message'   => $data['message'] ?? 'Erreur inconnue',
                'raw'       => $data,
            ];

        } catch (\Throwable $e) {
            Log::error('AangaraaPay withdrawal exception', ['error' => $e->getMessage()]);

            return [
                'succes'    => false,
                'outcome'   => 'indetermine',
                'reference' => null,
                'message'   => 'Erreur : ' . $e->getMessage(),
                'raw'       => [],
            ];
        }
    }

    public function detecterOperateur(string $telephone): string
    {
        // On passe par normaliserNumero() plutot que de redecouper le numero
        // ici : les deux fonctions doivent tomber sur le meme numero national.
        // Avant, « 00237 650 000 000 » etait normalise correctement pour
        // l'appel API mais detecte ici avec le prefixe « 002 », donc renvoyait
        // ALL et l'operateur n'etait plus reconnu.
        $numero = $this->normaliserNumero($telephone);
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
        $detecte   = $this->detecterOperateur($numero);
        $operateur = $operateurForce ?? $detecte;

        // Le navigateur fait sa propre detection et envoie l'operateur choisi
        // (fonctionne pour Orange comme pour MTN, verifie le 27/09/2026).
        // Aucun forçage n'est applique ici : changer le comportement d'un
        // encaissement deja valide n'a pas lieu d'être decide par une
        // supposition. En revanche un desaccord est signale, car il
        // indique soit une regle de prefixes a corriger, soit une
        // detection cote navigateur a revoir.
        if ($operateurForce !== null && $detecte !== 'ALL' && $operateurForce !== $detecte) {
            Log::warning('AangaraaPay : operateur choisi != operateur detecte', [
                'telephone'        => LogMasking::telephone($numero),
                'operateur_choisi' => $operateurForce,
                'operateur_detecte'=> $detecte,
            ]);
        }

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
