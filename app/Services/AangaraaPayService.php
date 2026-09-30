<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ParametreSysteme;
use App\Support\LogMasking;

class AangaraaPayService
{
    /**
     * Cout AangaraaPay par defaut, mesure le 27/09/2026 sur l'API de
     * production : reponse
     * `{"commission_rate":2.2,"commission_amount":0.02,"total_required":1.02}`
     * pour un virement de 1,00, soit 2,2 % payes EN PLUS du montant livre.
     *
     * Valeur de repli : le taux reel est un parametre systeme, modifiable
     * depuis Admin > Parametres systeme, pour ne pas redemarrer l'application
     * quand la grille du prestataire change.
     */
    public const TAUX_AANGARAA_DEFAUT = 0.022;

    /**
     * Marge que preleve EduPay sur chaque transaction, par defaut.
     *
     * Modele retenu : le payeur regle « frais de scolarite + frais de
     * paiement », l'etablissement recoit exactement les frais de scolarite
     * (ReverserEtablissementJob reverse `montant_net_etablissement`, qui vaut
     * le montant des frais de scolarite), et la difference reste sur le
     * compte AangaraaPay. Les frais visibles doivent donc couvrir le cout
     * AangaraaPay PLUS cette marge, sinon la plateforme est a perte sur
     * chaque encaissement — ce que faisait le barème figé de 200/400/800/
     * 1500/2500 FCFA.
     */
    public const MARGE_EDUPAY_DEFAUT = 0.001;

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
     * Cout AangaraaPay en vigueur, lu dans les parametres systeme.
     * Repli sur la constante si le parametre est absent ou corrompu.
     */
    public function tauxAangaraa(): float
    {
        $taux = (float) ParametreSysteme::obtenir('taux_aangaraa', self::TAUX_AANGARAA_DEFAUT);

        // Un cout superieur a 100 % rendrait les frais absurdes : on l'ignore
        // plutot que de faire exploser le montant debite au payeur.
        if ($taux < 0 || $taux > 1) {
            Log::warning('Taux AangaraaPay hors bornes, repli sur la valeur par defaut', [
                'taux_parametre' => $taux,
                'taux_repli'     => self::TAUX_AANGARAA_DEFAUT,
            ]);

            return self::TAUX_AANGARAA_DEFAUT;
        }

        return $taux;
    }

    /**
     * Marge EduPay en vigueur, lue dans les parametres systeme.
     * Repli sur la constante si le parametre est absent ou corrompu.
     */
    public function margeEdupay(): float
    {
        $marge = (float) ParametreSysteme::obtenir('marge_edupay', self::MARGE_EDUPAY_DEFAUT);

        if ($marge < 0 || $marge > 1) {
            Log::warning('Marge EduPay hors bornes, repli sur la valeur par defaut', [
                'marge_parametre' => $marge,
                'marge_repli'     => self::MARGE_EDUPAY_DEFAUT,
            ]);

            return self::MARGE_EDUPAY_DEFAUT;
        }

        return $marge;
    }

    /**
     * Taux global des frais de service : cout du prestataire + marge EduPay.
     * C'est ce taux unique, et non un bareme, qui est affiche au payeur.
     */
    public function tauxFraisService(): float
    {
        return $this->tauxAangaraa() + $this->margeEdupay();
    }

    /**
     * Calcule les frais de service visibles par le payeur.
     *
     * Modele : le payeur regle « frais de scolarite + frais de paiement », le
     * total debite entre sur le compte AangaraaPay, l'etablissement recoit
     * exactement les frais de scolarite (ReverserEtablissementJob) et la
     * difference reste sur le compte. Les frais doivent donc couvrir le cout
     * du reversement PLUS la marge EduPay.
     *
     * AangaraaPay preleve 2,2 % sur le RETRAIT (le reversement vaut les frais
     * de scolarite), pas sur le depot : le cout porte donc bien sur $montant.
     *
     * Arrondis : frais arrondis au franc SUPERIEUR et cout arrondi au franc
     * INFERIEUR, pour que l'arrondi ne puisse jamais faire passer la marge
     * sous zero. Le controle ci-dessous reste une garde-fou.
     */
    public function calculerFrais(int $montant): array
    {
        $tauxAangaraa = $this->tauxAangaraa();
        $margeEdupay   = $this->margeEdupay();
        $tauxGlobal    = $tauxAangaraa + $margeEdupay;

        // Frais visibles = part AangaraaPay + part EduPay.
        $fraisVisibles = (int) ceil($montant * $tauxGlobal);

        // Cout reel du reversement, mesure chez AangaraaPay (2,2 % du retrait).
        $fraisAangaraa = (int) floor($montant * $tauxAangaraa);

        $margeReelle = $fraisVisibles - $fraisAangaraa;

        if ($margeReelle < 0) {
            Log::warning('Marge EduPay negative sur ce montant', [
                'montant'          => $montant,
                'frais_encaisses'  => $fraisVisibles,
                'cout_reversement' => $fraisAangaraa,
                'perte'            => -$margeReelle,
                'taux_aangaraa'    => $tauxAangaraa,
                'marge_edupay'     => $margeEdupay,
            ]);
        }

        return [
            'montant_frais'       => $montant,                                   // Frais scolaires nets
            'frais_service'       => $fraisVisibles,                             // Ce que voit le payeur
            'frais_aangaraa'      => $fraisAangaraa,                             // Cout reel du reversement
            'marge_edupay'        => $margeReelle,                               // Gain EduPay
            'taux_frais'          => $tauxGlobal,                                // Taux global applique
            'taux_aangaraa'       => $tauxAangaraa,
            'taux_marge_edupay'   => $margeEdupay,
            'montant_total_paye'  => $montant + $fraisVisibles,                  // Total debite
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
            $reference = $this->extraireReferenceWithdrawal($data);

            Log::info('AangaraaPay withdrawal', [
                'telephone'          => LogMasking::telephone($numero),
                'montant'            => $montant,
                'operateur_demande'  => $operateurApi,
                'operateur_repondu'  => $data['data']['operator'] ?? null,
                'http'               => $response->status(),
                'reference'          => $reference,
                'response'           => LogMasking::payloadReduit($data),
            ]);

            // 5xx : AangaraaPay n'a pas tranche, l'argent peut avoir ete engage
            // ou non. On ne suppose rien, verification humaine.
            if ($response->serverError()) {
                return [
                    'succes'    => false,
                    'outcome'   => 'indetermine',
                    'reference' => $reference,
                    'message'   => $data['message'] ?? 'Réponse AangaraaPay inexploitable (HTTP '.$response->status().')',
                    'raw'       => $data,
                ];
            }

            // 2xx sans reference n'est plus un cas d'indetermine. AangaraaPay
            // repond 201 {"message":"Payment request successful","data":{"message":...}}
            // SANS transaction_id sur ses retraits : exiger la reference
            // classait TOUT reversement reussi en "a_verifier" et bloquait les
            // fonds alors que l'argent etait bien parti.
            if ($response->successful()) {
                return [
                    'succes'    => true,
                    'outcome'   => 'succes',
                    // Reference synthetique : elle n'existe pas chez
                    // AangaraaPay, mais la commission en a besoin pour etre
                    // tracee. Le prefixe la rend identifiable comme generate.
                    'reference' => $reference ?? 'WITHDRAWAL-SANS-REF-'.now()->format('YmdHis'),
                    'message'   => $data['message'] ?? 'Retrait enregistré par AangaraaPay',
                    'raw'       => $data,
                ];
            }

            return [
                'succes'    => false,
                'outcome'   => 'refuse',
                'reference' => $reference,
                'message'   => $this->extraireMessageErreur($data, 'FAILED'),
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

    /**
     * AangaraaPay n'est pas homogene sur la forme de ses reponses de retrait :
     * certains appels renvoient data.transaction_id, d'autres (201 "Payment
     * request successful") ne renvoient qu'un message. On essaie les formes
     * connues plutot que d'en supposer une seule.
     */
    private function extraireReferenceWithdrawal(array $data): ?string
    {
        $candidats = [
            $data['data']['transaction_id']  ?? null,
            $data['data']['transactionId']   ?? null,
            $data['data']['reference']       ?? null,
            $data['transaction_id']          ?? null,
            $data['reference']               ?? null,
        ];

        foreach ($candidats as $candidat) {
            if (is_scalar($candidat) && trim((string) $candidat) !== '') {
                return (string) $candidat;
            }
        }

        return null;
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

    /**
     * Solde reel du service chez AangaraaPay.
     *
     * C'est le SEUL chiffre qui dit combien d'argent la plateforme detient
     * reellement. Le reste (marge_edupay) est une comptabilite interne qui
     * suppose les frais AangaraaPay connus a l'avance ; si le prestataire
     * preleve ailleurs, l'ecart se voit ici et nulle part ailleurs.
     *
     * Endpoint documente : GET /service/balance/{app_key}. Lecture seule,
     * aucun argent bouge.
     */
    public function solde(): array
    {
        try {
            $response = Http::timeout(20)
                ->get($this->apiUrl . '/service/balance/'.$this->appKey);

            $data = $response->json();

            if (! $response->successful()) {
                Log::warning('AangaraaPay balance indisponible', [
                    'http'      => $response->status(),
                    'response'  => LogMasking::payloadReduit($data),
                ]);

                return [
                    'ok'        => false,
                    'solde'     => null,
                    'message'   => $data['message'] ?? 'Solde indisponible (HTTP '.$response->status().')',
                ];
            }

            $details = $data['data']['balance_details'] ?? [];

            return [
                'ok'           => true,
                'solde'        => (float) ($data['data']['balance_in_db'] ?? 0),
                'service_id'   => $data['data']['service_id'] ?? null,
                'service_name' => $data['data']['service_name'] ?? null,
                'parOperateur' => [
                    'mtn'    => (float) ($details['mtn_cameroon']['amount']    ?? 0),
                    'orange' => (float) ($details['orange_cameroon']['amount'] ?? 0),
                ],
                'nbTransactions' => $details['total']['transactions_count'] ?? null,
                'message'     => null,
            ];
        } catch (\Throwable $e) {
            Log::error('AangaraaPay balance exception', ['error' => $e->getMessage()]);

            return [
                'ok'      => false,
                'solde'   => null,
                'message' => 'Erreur : '.$e->getMessage(),
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
