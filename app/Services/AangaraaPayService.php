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
     *
     * ─────────────────────────────────────────────────────────────────────
     * A VERIFIER AVEC AANGARAAPAY — le modele de cout est peut-etre incomplet
     * ─────────────────────────────────────────────────────────────────────
     * Les 2,2 % ont ete MESURES sur un retrait, et c'est la seule donnee
     * verifiee dont on dispose. Mais la grille publiee sur
     * aangaraa-pay.com (rubrique Tarifs, consultee le 30/09/2026) annonce
     * DEUX frais, pas un seul :
     *
     *     reseau            Payin (encaissement)   Payout (retrait)
     *     MTN_Cameroon            1,70 %               1,30 %
     *     Orange_Cameroon         1,50 %               2,10 %
     *
     * et les CGU du prestataire confirment : « Les pourcentages de frais
     * applicables aux transactions (payin et payout) sont ceux en vigueur
     * affiches sur la Plateforme ».
     *
     * Or ce modele n'AUDITE QUE LE RETRAIT. Aucun frais d'encaissement
     * (payin) n'est retranche dans les calculs : si AangaraaPay le preleve
     * reellement sur chaque depot, la plateforme est a perte a chaque
     * encaissement, de l'ordre de 0,6 % a 1,2 % du montant collecte
     * (MTN ~ -0,62 %, Orange ~ -1,20 % sur un frais de scolarite de 50 000).
     *
     * Deux points restent a clarifier avec le prestataire, et ils ne se
     * devinent pas depuis la doc publique :
     *   1. le fee payin est-il oui ou non debite du solde a chaque depot ?
     *   2. le fee payout est-il ajoute au cout, ou deduit du montant recu
     *      par l'etablissement ?
     *
     * NE PAS CORRIGER LE TAUX SANS CONFIRMATION : le taux facture au payeur
     * decoule de cette valeur, donc le modifier change ce que paient les
     * familles. Verifier d'abord le taux negocie reel du compte, puis la
     * structure payin + payout.
     */
    public const TAUX_AANGARAA_DEFAUT = 0.022;

    /**
     * Marge que preleve EduPay sur chaque transaction, par defaut.
     *
     * ── Modele RETENU, confirm�� par le metier le 30/09/2026 ──────────────
     * Le payeur regle « frais de scolarite + frais de service ». Ce qui part
     * reellement a l'etablissement est le NET, c'est-a-dire les frais de
     * scolarite MOINS la marge EduPay :
     *
     *     montant_net_etablissement = montant - marge_edupay
     *
     * (ecrit dans Commission par Payeur\PaiementController et
     * Api\PaiementController::traiterPaiementValide, puis reverse par
     * ReverserEtablissementJob — c'est cette colonne, et non
     * `montant_transaction`, qui est le montant du virement.)
     *
     * Attention : une version anterieure de ce fichier affirmait que
     * l'etablissement recevait « exactement les frais de scolarite ». Ce
     * n'est plus le modele en vigueur, et ce texte a deja induit en erreur.
     * Ne pas « corriger » le calcul du net sans decision metier explicite :
     * cela changerait le montant de tous les virements aux etablissements.
     *
     * Les frais visibles doivent rester >= cout AangaraaPay + marge, sinon la
     * plateforme est a perte sur chaque encaissement — ce que faisait le bareme
     * fige de 200/400/800/1500/2500 FCFA.
     */
    public const MARGE_EDUPAY_DEFAUT = 0.001;

    /**
     * Taux de commission par profil d'abonnement (CDC S0 #3 : « Configuration
     * du taux de commission preleve par transaction selon le profil
     * d'abonnement »).
     *
     * Chaque plan porte son propre taux dans `parametres_systeme` sous la
     * cle `taux_commission_<plan>`, ce qui rend le taux configurable depuis
     * l'admin sans redemarrage. Ces constantes ne sont que le repli quand la
     * cle est absente : un nouveau plan ajoute ici reste configurable meme si
     * personne n'a encore saisi son taux.
     *
     * Garde-fou applique partout : le taux d'un plan ne peut pas descendre
     * sous le cout AangaraaPay, sinon chaque reversement fait perdre de
     * l'argent a la plateforme.
     */
    public const TAUX_COMMISSION_PLANS = [
        'basique'  => 0.023,
        'standard' => 0.023,
        'premium'  => 0.023,
    ];

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
     *
     * Taux de REPLI, utilise quand aucun plan n'est connu (paiement sans
     * etablissement resolu, appel de test, preview). Le taux effectif d'un
     * etablissement donne est celui de son plan : voir tauxCommissionPlan().
     */
    public function tauxFraisService(): float
    {
        return $this->tauxAangaraa() + $this->margeEdupay();
    }

    /**
     * Taux de commission applicable au plan donne (CDC S0 #3).
     *
     * Le taux est configurable par plan dans `parametres_systeme`. Deux
     * garde-fous :
     *   - jamais sous le cout AangaraaPay, sinon la plateforme est a perte a
     *     chaque transaction (le reversement coute plus que ce qui est encaisse) ;
     *   - jamais au-dela de 100 %, sinon les frais Absurdes.
     * Un taux hors bornes est signale : c'est une erreur de configuration,
     * pas une valeur a appliquer silencieusement.
     */
    public function tauxCommissionPlan(?string $plan): float
    {
        $tauxDefaut = $this->tauxFraisService();

        if (! $plan || ! array_key_exists($plan, self::TAUX_COMMISSION_PLANS)) {
            return $tauxDefaut;
        }

        $taux = (float) ParametreSysteme::obtenir(
            'taux_commission_'.$plan,
            self::TAUX_COMMISSION_PLANS[$plan]
        );

        $plancher = $this->tauxAangaraa();
        $borneHaute = 1.0;

        if ($taux < $plancher || $taux > $borneHaute) {
            Log::warning('Taux de commission de plan hors bornes, repli sur le taux global', [
                'plan'              => $plan,
                'taux_parametre'    => $taux,
                'taux_repli'        => $tauxDefaut,
                'plancher_aangaraa' => $plancher,
            ]);

            return $tauxDefaut;
        }

        return $taux;
    }

    /**
     * Taux de commission de l'etablissement, deduit de son abonnement courant.
     *
     * Resout le plan sans charger la relation pour rien : `abonnementCourant`
     * trie sur date_debut puis id, la colonne denormalisee `plan_abonnement`
     * n'est pas fiee (elle peut differer de l'abonnement le plus recent).
     */
    public function tauxCommissionEtablissement($etablissement): float
    {
        if (! $etablissement) {
            return $this->tauxFraisService();
        }

        $plan = null;

        if (method_exists($etablissement, 'abonnementCourant')) {
            $plan = $etablissement->abonnementCourant()?->plan;
        }

        return $this->tauxCommissionPlan($plan);
    }

    /**
     * Taux de commission des trois plans, pour l'ecran de configuration.
     * Exposes sous forme de tableau pret a afficher.
     */
    public function tauxParPlan(): array
    {
        $taux = [];

        foreach (array_keys(self::TAUX_COMMISSION_PLANS) as $plan) {
            $taux[$plan] = $this->tauxCommissionPlan($plan);
        }

        return $taux;
    }

    /**
     * Calcule les frais de service visibles par le payeur.
     *
     * Modele : le payeur regle « frais de scolarite + frais de paiement », le
     * total debite entre sur le compte AangaraaPay, et l'etablissement est
     * reverse du NET (frais de scolarite - marge EduPay). Les frais visibles
     * doivent donc couvrir le cout du reversement PLUS la marge EduPay.
     * Voir la note de modele sur MARGE_EDUPAY_DEFAUT.
     *
     * AangaraaPay preleve 2,2 % sur le RETRAIT (proche du montant des frais
     * de scolarite), pas sur le depot : le cout porte donc bien sur $montant.
     * Comme le net vire est legerement inferieur au montant, le cout reel
     * du retrait est legerement inferieur a `frais_aangaraa` : la marge
     * reelle reste donc positive, ce que controle le garde-fou plus bas.
     *
     * Arrondis : frais arrondis au franc SUPERIEUR et cout arrondi au franc
     * INFERIEUR, pour que l'arrondi ne puisse jamais faire passer la marge
     * sous zero. Le controle ci-dessous reste une garde-fou.
     */
    public function calculerFrais(int $montant, $etablissement = null): array
    {
        $tauxAangaraa = $this->tauxAangaraa();

        // Le taux depend du profil d'abonnement de l'etablissement (CDC S0 #3).
        // Sans etablissement resolu, on retombe sur le taux global.
        $tauxGlobal = $etablissement
            ? $this->tauxCommissionEtablissement($etablissement)
            : $this->tauxFraisService();

        // Frais visibles = taux de commission du plan.
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
            'taux_frais'          => $tauxGlobal,                                // Taux du plan applique
            'taux_aangaraa'       => $tauxAangaraa,
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

            // Contrat reel de l'API (corrige le 30/09/2026) : /withdrawal attend
            // `payment_method` (operateur) et `username` (libelle), PAS `operator`
            // ni `description`. Avec les mauvais noms, l'API repondait par un
            // message generique 201 sans `status` ni `reference_id` et TOUT les
            // retraits partaient en 'indetermine', meme reussis.
            $response = Http::timeout(30)
                ->post($this->apiUrl . '/aangaraa-pay/withdrawal', [
                    'app_key'        => $this->appKey,
                    'phone_number'   => $numero,
                    'amount'         => (string) $montant,
                    'payment_method' => $operateurApi,
                    'username'       => $description,
                ]);

            $data      = $response->json() ?? [];
            $statutApi = $this->normaliserStatutWithdrawal($data['data']['status'] ?? $data['status'] ?? null);
            $reference = $this->extraireReferenceWithdrawal($data);

            Log::info('AangaraaPay withdrawal', [
                'telephone'         => LogMasking::telephone($numero),
                'montant'           => $montant,
                'operateur_demande' => $operateurApi,
                'http'              => $response->status(),
                'statut_api'        => $statutApi,
                'reference'         => $reference,
                'response'          => LogMasking::payloadReduit($data),
                // Les NOMS de tous les champs recus, y compris ceux que le
                // masquage retire. AangaraaPay a repondu 201 avec un format
                // non documente : sans cette liste, impossible de savoir si la
                // reference etait presente sous un nom inconnu. C'est ce que
                // ce dernier test doit reveler.
                'cles_reponse'      => LogMasking::clesReponse($data),
            ]);

            // SUCCESSFUL + reference : retrait confirme, on enregistre.
            // On tolere 'SUCCESS' : la doc officielle utilise 'SUCCESSFUL'
            // mais certaines reponses utilisees en phase de test emploient 'SUCCESS'.
            if (str_starts_with($statutApi, 'SUCCESS') && $reference) {
                return [
                    'succes'    => true,
                    'outcome'   => 'succes',
                    'reference' => $reference,
                    'message'   => $data['message'] ?? 'Retrait effectue avec succes',
                    'raw'       => $data,
                ];
            }

            // PENDING : accepte mais pas encore confirme. On ne rejoue JAMAIS
            // automatiquement : seule verifierStatutRetrait() (/check_withdrawal_status)
            // pourra trancher plus tard sans risque de double virement.
            if ($statutApi === 'PENDING') {
                return [
                    'succes'    => false,
                    'outcome'   => 'indetermine',
                    'reference' => $reference,
                    'message'   => "Retrait en cours de traitement par l'operateur (PENDING).",
                    'raw'       => $data,
                ];
            }

            if ($statutApi === 'FAILED') {
                return [
                    'succes'    => false,
                    'outcome'   => 'refuse',
                    'reference' => $reference,
                    'message'   => $data['data']['details']['reason'] ?? ($data['message'] ?? 'Retrait refuse'),
                    'raw'       => $data,
                ];
            }

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

            // 2xx sans champ `status` exploitable.
            //
            // BUG CORRIGE 30/09/2026 : le vrai endpoint /withdrawal repond
            // HTTP 201 avec {"message":"Payment request successful","data":{...}}
            // SANS champ `status` ni `reference_id`. Cette reponse etait
            // classee `refuse`, ce qui est doublement faux :
            //   1. l'argent est REELLEMENT engage par l'operateur ;
            //   2. `refuse` declenche un nouvel essai du job, donc un
            //      DEUXIEME virement reel a l'etablissement.
            // Constat en test reel : commission 8, deux virements de 49 CFA
            // envoyes a 07:07:52 et 07:08:32, tous deux rejetes a tort, et le
            // statut `calculee` faisait repartir une 3e tentative.
            //
            // On ne peut pas affirmer le succes (aucune preuve de confirmation)
            // mais on ne doit SURTOUT pas pretendre que l'argent n'est pas
            // parti. `indetermine` place la commission en `a_verifier` : aucun
            // rejeu automatique, verification humaine obligatoire. C'est le
            // meme traitement que les 5xx et les timeouts, pour la meme raison.
            //
            // Seule une erreur documentee (statusCode / message d'erreur explicite
            // d'AangaraaPay) reste un refus.
            $messageApi = (string) ($data['message'] ?? '');

            // Un 4xx est un refus definitif d'AangaraaPay (solde insuffisant,
            // operateur inconnu, montant hors limites...). Aucun argent n'a
            // pu partir : la commission doit rester rejouable apres delai.
            //
            // 01/10/2026 : plus AUCUN 4xx n'est traite comme un refus net.
            //
            // La doc AangaraaPay precise que /withdrawal ne deduit le solde
            // QUE si le retrait aboutit, mais un 4xx ne parle que du SOLDE de
            // notre compte, pas du sort du retrait courant. Consequence
            // observee en production : 4 retraits SUCCESSFUL, puis le solde
            //vide renvoie 400 « Insufficient balance ». Traiter ce 400 comme
            // « rien n'est parti » a relance le job et produit 5 retraits
            // pour un seul paiement.
            //
            // On ne peut pas distinguer « 4xx avant execution » de « 4xx apres
            // execution » : la seule donnee qui tranche est le statut reel du
            // retrait, qu'on obtient via verifierStatutRetrait(). Donc tout
            // 4xx part en 'indetermine' : commission en 'a_verifier', aucun
            // renvoi automatique, et la reconciliation tranche sur la preuve.
            if ($response->clientError() || $this->estErreurDocumenteeSignalee($messageApi, $statutApi)) {
                return [
                    'succes'    => false,
                    'outcome'   => 'indetermine',
                    'reference' => $reference,
                    'message'   => 'Refus HTTP ' . $response->status() . ' d\'AangaraaPay : '
                        . ($this->extraireMessageErreur($data, 'FAILED') ?: 'raison inconnue')
                        . '. Ce code signale un probleme de solde ou de configuration, PAS l\'echec du'
                        . ' retrait : la reponse ne dit rien du sort de l\'argent. Verification du'
                        . ' statut reel obligatoire, aucun renvoi automatique.',
                    'raw'       => $data,
                ];
            }

            return [
                'succes'    => false,
                'outcome'   => 'indetermine',
                'reference' => $reference,
                'message'   => 'AangaraaPay a accepte la demande (HTTP ' . $response->status() . ') sans confirmation exploitable. Verification manuelle obligatoire : le virement peut deja etre parti.',
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

    /**
     * Interroge le statut REEL d'un retrait deja initie (transaction_id =
     * reference_id retourne par /withdrawal). Seule source fiable pour lever
     * une commission 'a_verifier' sans jamais renvoyer l'argent a l'aveugle.
     */
    public function verifierStatutRetrait(string $transactionId, string $operateur): array
    {
        $operateurApi = $operateur === 'orange' ? 'Orange_Cameroon' : 'MTN_Cameroon';

        try {
            $response = Http::timeout(20)->get(
                $this->apiUrl . '/check_withdrawal_status/' . urlencode($transactionId),
                ['payment_method' => $operateurApi]
            );

            $data   = $response->json() ?? [];
            $statut = strtoupper((string) ($data['status'] ?? ''));

            Log::info('AangaraaPay check_withdrawal_status', [
                'transaction_id' => $transactionId,
                'operateur'      => $operateurApi,
                'http'           => $response->status(),
                'statut'         => $statut,
                'response'       => LogMasking::payloadReduit($data),
            ]);

            // SUCCESSFUL | PENDING | FAILED | NOT_FOUND | INCONNU
            return ['statut' => $statut ?: 'INCONNU', 'raw' => $data];
        } catch (\Throwable $e) {
            Log::error('AangaraaPay check_withdrawal_status exception', ['error' => $e->getMessage()]);
            return ['statut' => 'INCONNU', 'raw' => []];
        }
    }

    /**
     * Normalise le champ `status` de /withdrawal, qui n'est PAS une chaine.
     *
     * Reponse reelle capturee en production le 01/10/2026 (retrait 50 FCFA,
     * commission 2 puis 3) :
     *
     *   "data": {"status": true, "message": "Transfer request accepted
     *            and is being processed", "reference_id": "5c487ca1-..."}
     *
     * `status` y est un BOOLEEN. Le code faisait `(string) $statutApi`, ce qui
     * donnait « 1 » — et le test `str_starts_with($statutApi, 'SUCCESS')`
     * ne pouvait donc JAMAIS passer. Consequence : les deux retraits ont ete
     * reellement executes par AangaraaPay, et le code les a classes
     * « indetermine » puis « a_verifier » malgre une reponse HTTP 200
     * parfaitement exploitable. Le back office affichait « a verifier » sur des
     * virements deja payes.
     *
     * On accepte donc les trois formes rencontrees : booleen, chaine
     * « SUCCESSFUL » / « SUCCESS » (doc officielle) et chaines d'echec.
     * Un booléen `true` est une confirmation explicite d'AangaraaPay, pas un
     * statut inconnu.
     */
    private function normaliserStatutWithdrawal($statutApi): string
    {
        if (is_bool($statutApi)) {
            return $statutApi ? 'SUCCESSFUL' : 'FAILED';
        }

        if ($statutApi === null) {
            return '';
        }

        return strtoupper(trim((string) $statutApi));
    }

    private function estErreurDocumenteeSignalee(string $messageApi, string $statutApi): bool
    {
        $m = strtolower($messageApi);

        // Ne PAS inclure 'insufficient/insuffisant' ici : traité comme ambigu
        if (preg_match('/insufficient|insuffisant/i', $m) === 1) {
            return false;
        }

        return $statutApi !== ''
            || $messageApi === ''
            || preg_match('/invalid|erreur|error|failed|rejected|refus|excede|limite/i', $m) === 1;
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

        // MTN MoMo — plan national de numerotation (ART).
        // 680-683 restent exclus : Nexttel/Viettel, pas MTN.
        if (($prefixe >= 650 && $prefixe <= 654) || ($prefixe >= 670 && $prefixe <= 679)) {
            return 'MTN_Cameroon';
        }

        // Orange Money — 6 plages au total. Les deux premieres sont celles que
        // la doc AangaraaPay cite, et les seules qui aient ete confirmees par
        // un encaissement reussi. Les suivantes sont Orange selon le plan
        // national de numerotation de l'ART (86-87 et 88 pour la 88) et la
        // base de reference phoneverify-cameroon (640, 686-689), mais AangaraaPay
        // ne les a jamais documentees : elles sont acceptees ici pour ne pas
        // laisser un vrai client Orange sur une detection qui ne se fait pas,
        // et un refus eventuel vient alors de l'API, message compris.
        if ($prefixe === 640
            || ($prefixe >= 655 && $prefixe <= 659)
            || ($prefixe >= 686 && $prefixe <= 689)
            || ($prefixe >= 690 && $prefixe <= 699)
        ) {
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
            $data['data']['reference_id']    ?? null,
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

    /**
     * Statut initial d'une initiation, normalise.
     *
     * AangaraaPay ne renvoie pas toujours `data.status` a l'initiation. La doc
     * officielle montre deux formes reellement rencontrees :
     *
     *   MTN  : "message": "PENDING", "data": {"payToken": "...", "status": "PENDING"}
     *   Orange (variante doc) : "message": "PENDING", "data": {"payToken": "..."}
     *
     * Le code faisait `$data['data']['status'] ?? 'FAILED'`. Sur la seconde
     * forme il obtenait FAILED, donc `succes` false, donc le paiement passe en
     * `echoue` ALORS QUE le push operateur vient d partir et que le payeur peut
     * encore confirmer. L'argent etait alors debite pour un paiement marque
     * echoue : le pire ecart possible entre notre comptabilite et la realite.
     *
     * Regle appliquee : un jeton de transaction ET un HTTP 201 prouvent que la
     * transaction existe chez le prestataire. L'absence de `data.status` n'est
     * donc jamais interpretee comme un echec ; on lit le statut racine
     * (`message`) s'il est connu, sinon on assume PENDING et on laisse le
     * polling trancher. Un echec n'est retenu que si le prestataire dit
     * explicitement que la transaction a echoue.
     */
    private function normaliserStatutInitiation(array $data): string
    {
        // 1. Champ explicite, prioritaire.
        $brut = $data['data']['status'] ?? null;

        if (is_string($brut) && trim($brut) !== '') {
            return strtoupper(trim($brut));
        }

        if (is_bool($brut)) {
            return $brut ? 'SUCCESSFUL' : 'FAILED';
        }

        // 2. Variante sans `data.status` : le statut est au niveau racine.
        $racine = trim((string) ($data['message'] ?? ''));
        if ($racine !== '' && strtoupper($racine) === 'PENDING') {
            return 'PENDING';
        }

        // 3. Un jeton de transaction sur HTTP 201 (verifie plus bas par
        //    l'appelant) signifie que la transaction existe : on ne declare
        //    PAS un echec par defaut, le polling fera l'arbitrage.
        if (! empty($data['data']['payToken'])) {
            return 'PENDING';
        }

        // 4. Aucun jeton : rien n'a ete cree, c'est un echec reel.
        return $racine !== '' ? strtoupper($racine) : 'FAILED';
    }

    public function initierPaiement(
        string $telephone,
        int    $montant,
        string $description,
        string $transactionId,
        string $notifyUrl,
        ?string $operateurForce = null,
        ?string $returnUrl = null
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

            // `return_url` est le SEUL champ de l'exemple de la doc que
            // nous n'envoyions pas. Il est ajoute pour Orange uniquement, a
            // titre d'experience : la doc affirme que le client recoit un
            // prompt sur son telephone, ce qui n'a jamais ete observe en prod
            // sur Orange (la transaction EP2026-QL7RA a recu un SMS
            // AangaraaPay indiquant de composer #150*50#, puis a expiree).
            //
            // MTN n'est PAS touche : son prompt USSD (*126#) fonctionne et
            // fonctionne depuis toujours, on ne le met pas en risque pour
            // tester une hypothese sur l'autre operateur.
            //
            // Verdict a lire apres un essai Orange :
            //  - si Orange passe enfin par un prompt USSD -> generaliser a
            //    tous les operateurs et le documenter ;
            //  - si Orange reste en SMS #150*50# -> la decision est cote
            //    AangaraaPay/Orange, pas dans notre payload, et ce bloc
            //    devient inutile (a supprimer). Dans ce cas la question
            //    precise a poser au prestataire est : « Orange_Cameroon
            //    supporte-t-il le push USSD sur /no_redirect/payment, ou
            //    uniquement la confirmation par SMS ? ».
            if ($returnUrl !== null && $returnUrl !== '') {
                $payload['return_url'] = $returnUrl;
            }

            $response = Http::timeout(30)
                ->post($this->apiUrl . '/no_redirect/payment', $payload);

            $data      = is_array($response->json()) ? $response->json() : [];
            $payToken  = $data['data']['payToken'] ?? null;
            $statutApi = $this->normaliserStatutInitiation($data);

            $message   = $this->extraireMessageErreur($data, $statutApi);


            Log::info('AangaraaPay initier', [
                'transaction_id' => $transactionId,
                'telephone'      => LogMasking::telephone($numero),
                'operateur'      => $operateur,
                'notify_url'     => $notifyUrl,
                'statut_relu'    => $statutApi,
                'statut_brut'    => $data['data']['status'] ?? $data['message'] ?? null,
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

            // Deux grandeurs de nature differente, a ne jamais melanger :
            //
            // - `solde` = `balance_in_db`, la seule somme reellement retirable
            //   du compte.
            // - `cumul*` = `balance_details.*.amount`, le MONTANT CUMULE
            //   encaisse sur les transactions au statut SUCCESSFUL (doc
            //   AangaraaPay : « Montant et nombre de transactions MTN
            //   reussies »). Ce n'est PAS un solde par operateur.
            //
            // Exemple reel du 02/10/2026 : balance_in_db = 2 alors que
            // mtn_cameroon.amount = 774 sur 11 transactions. Les 774 sont
            // le volume encaisse depuis toujours, dont 772 deja reverses ; il
            // restait 2 XAF. L'admin affichait « MTN : 774 » sous le titre
            // « Solde reel », ce qui se lisait « 2 disponibles, 774 chez MTN »
            // et laissait croire a un ecart de 772 XAF qui n'existait pas.
            $mtn    = $details['mtn_cameroon']    ?? [];
            $orange = $details['orange_cameroon'] ?? [];
            $total  = $details['total']            ?? [];

            return [
                'ok'           => true,
                'solde'        => (float) ($data['data']['balance_in_db'] ?? 0),
                'service_id'   => $data['data']['service_id'] ?? null,
                'service_name' => $data['data']['service_name'] ?? null,
                // Cumules encaisses, par operateur et au total.
                'cumul'        => [
                    'mtn'    => (float) ($mtn['amount']    ?? 0),
                    'orange' => (float) ($orange['amount'] ?? 0),
                    'total'  => (float) ($total['amount']  ?? ($mtn['amount'] ?? 0) + ($orange['amount'] ?? 0)),
                ],
                'nbTransactions' => [
                    'mtn'    => $mtn['transactions_count']    ?? null,
                    'orange' => $orange['transactions_count'] ?? null,
                    'total'  => $total['transactions_count']  ?? null,
                ],
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
