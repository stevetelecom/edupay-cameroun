<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitierPaiementRequest;
use App\Http\Resources\PaiementResource;
use App\Jobs\SendConfirmationPaiement;
use App\Jobs\SendNotificationEchecPaiement;
use App\Models\Echeancier;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Services\AangaraaPayService;
use App\Support\MontantPaiement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    public function __construct(
        private AangaraaPayService $aangaraa,
        private MontantPaiement $montantPaiement,
    ) {}

    /**
     * Historique des paiements de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', $request->user()->id)
            ->latest('date_paiement')
            ->paginate($request->integer('per_page', 15));

        return PaiementResource::collection($paiements)->response();
    }

    /**
     * Initie un paiement Mobile Money via AangaraaPay.
     */
    public function initier(InitierPaiementRequest $request): JsonResponse
    {
        $user    = $request->user();
        $valid   = $request->validated();

        // Le paiement porte toujours sur une ligne de frais d'un apprenant
        // rattache a l'utilisateur ET valide par l'etablissement.
        $frais = FraisApprenant::with(['categorieFrais', 'apprenant.etablissement'])
            ->findOrFail($valid['frais_apprenant_id']);

        $lien = $this->autoriserAccesFrais($user, $frais);
        if ($lien instanceof JsonResponse) {
            return $lien;
        }

        // Garde-fou anti-double-paiement : MEME garde que le web
        // (Payeur\PaiementController::initier:82-100). Elle manquait ici, et
        // deux appels « initier » sur les memes frais creaient deux Paiement
        // distincts (chaque `reference` est unique) : le payeur etait debite
        // deux fois, et `traiterPaiementValide` incrementait `montant_paye`
        // deux fois sur les memes lignes de frais.
        $paiementBloquant = Paiement::where('frais_apprenant_id', $frais->id)
            ->where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->where('annule_manuellement', false)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest()
            ->first();

        // Si le paiement en attente est mort (delai depasse, FAILED chez
        // AangaraaPay) on le solde et on autorise le suivant ; s'il est
        // vivant, on refuse et on renvoie l'identifiant pour que le mobile
        // interroge /paiements/{id}/verifier.
        if ($paiementBloquant && ! $this->synchroniserPaiementEnAttente($paiementBloquant)) {
            return response()->json([
                'message' => __('api.paiement_en_cours'),
                'code'    => 'paiement_en_cours',
                'data'    => [
                    'paiement_id' => $paiementBloquant->id,
                    'reference'   => $paiementBloquant->reference,
                ],
            ], 409);
        }

        $telephoneNormalise = $this->aangaraa->normaliserNumero($valid['telephone']);
        $numeroLocal        = substr($telephoneNormalise, 3);

        if (! preg_match('/^6[0-9]{8}$/', $numeroLocal)) {
            return response()->json([
                'message' => 'Numéro invalide.',
                'errors'  => ['telephone' => ['Utilisez 9 chiffres (ex. 654862989) ou +237654862989.']],
            ], 422);
        }

        // Audit D : le montant debite est decide par le serveur
        // (App\Support\MontantPaiement). Le `montant` et le `type_paiement`
        // envoyes par le mobile ne determinent plus la somme : le mobile
        // affichait une tranche de 25 000 FCFA lue dans le calendrier de
        // l'etablissement, le serveur debitait le reste du (100 000 FCFA).
        $echeance = null;

        if (! empty($valid['echeancier_id'])) {
            $echeance = Echeancier::find($valid['echeancier_id']);

            if (! $echeance) {
                return $this->erreurMontant(MontantPaiement::ERREUR_ECHEANCE_INTROUVABLE);
            }
        }

        $calcul = $this->montantPaiement->calculer(
            $frais,
            $valid['type_paiement'] ?? 'integral',
            $echeance
        );

        if ($calcul['erreur'] !== null) {
            return $this->erreurMontant($calcul['erreur']);
        }

        $montant       = $calcul['montant'];
        $type          = $calcul['type'];
        $echeancierId  = $calcul['echeancier_id'];
        $numeroTranche = $calcul['numero_tranche'];

        $apprenantId   = $frais->apprenant_id;
        $fraisId       = $frais->id;
        $description   = 'EduPay — ' . $frais->categorieFrais->nom . ' — ' . ($frais->apprenant->nom ?? '');

        // renamed: le detail des frais de service ne doit pas ecraser la
        // variable $frais qui contient le modele FraisApprenant.
        // L'etablissement est passe pour que le taux de commission soit celui
        // du profil d'abonnement (CDC S0 #3) et non le taux global.
        $detailFrais = $this->aangaraa->calculerFrais(
            $montant,
            $frais->apprenant?->etablissement
        );

        $paiement = Paiement::create([
            'user_id'            => $user->id,
            'apprenant_id'       => $apprenantId,
            'frais_apprenant_id' => $fraisId,
            'echeancier_id'      => $echeancierId,
            'numero_tranche'     => $numeroTranche,
            'montant'            => $montant,
            'frais_service'      => $detailFrais['frais_service'],
            'montant_total_paye' => $detailFrais['montant_total_paye'],
            'frais_aangaraa'     => $detailFrais['frais_aangaraa'],
            'marge_edupay'       => $detailFrais['marge_edupay'],
            'mode_paiement'      => $valid['mode_paiement'],
            'type_paiement'      => $type,
            'statut'             => 'en_attente',
            'telephone_paiement' => $telephoneNormalise,
            'date_paiement'      => now(),
        ]);

        $notifyUrl = config('services.aangaraa.notify_url')
            ?: route('payeur.paiement.webhook');

        if (str_contains($notifyUrl, 'localhost') || str_contains($notifyUrl, '127.0.0.1')) {
            Log::warning('API paiement initier — notify_url pointe vers localhost', ['notify_url' => $notifyUrl]);
        }

        // La validation n'accepte que ces deux modes. Le `default` leve une
        // exception au lieu de retomber a `null` : avec `null`, AangaraaPay
        // deduit l'operateur du numero et debite quand meme en Mobile Money,
        // ce qui faisait diverger le mouvement reel et la comptabilisation.
        $operateur = match ($valid['mode_paiement']) {
            'mtn_momo'     => 'MTN_Cameroon',
            'orange_money' => 'Orange_Cameroon',
            default        => throw new \InvalidArgumentException('Mode de paiement non supporte : ' . $valid['mode_paiement']),
        };

        $resultat = $this->aangaraa->initierPaiement(
            telephone:      $telephoneNormalise,
            montant:        $paiement->montant_total_paye,
            description:    $description,
            transactionId:  $paiement->reference,
            notifyUrl:      $notifyUrl,
            operateurForce: $operateur,
        );

        if (! $resultat['succes']) {
            $paiement->update(['statut' => 'echoue']);
            SendNotificationEchecPaiement::dispatch($paiement->fresh(), $resultat['message'] ?? null);

            return response()->json([
                'message' => $resultat['message'] ?? 'Échec du paiement.',
                'statut'  => 'echoue',
            ], 422);
        }

        $paiement->update([
            'pay_token'               => $resultat['pay_token'],
            'aangaraa_transaction_id' => $paiement->reference,
            'operateur'               => $resultat['operateur'],
        ]);

        return response()->json([
            'message'     => 'Confirmez le paiement sur votre téléphone ' . $telephoneNormalise,
            'statut'      => 'en_attente',
            'paiement_id' => $paiement->id,
            'paiement'    => new PaiementResource($paiement->load(['apprenant', 'fraisApprenant.categorieFrais'])),
        ], 201);
    }

    /**
     * Erreur métier de calcul du montant. Le client mobile n'affiche que le
     * champ `message` : il doit donc être en français (ou dans la locale de
     * l'API), et la réponse rester un 422 lisible.
     */
    private function erreurMontant(string $codeErreur): JsonResponse
    {
        $message = $this->montantPaiement->message($codeErreur);

        return response()->json([
            'message' => $message,
            'errors'  => ['montant' => [$message]],
        ], 422);
    }

    /**
     * Vérifie le statut d'un paiement en attente (polling).
     */
    public function verifier(Paiement $paiement): JsonResponse
    {
        $user = auth()->user();

        if ($paiement->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à ce paiement.'], 403);
        }

        if (! $paiement->pay_token) {
            return response()->json(['statut' => $paiement->statut]);
        }

        if (in_array($paiement->statut, Paiement::STATUTS_TERMINAUX, true)) {
            return response()->json(['statut' => $paiement->statut]);
        }

        $resultat = $this->aangaraa->verifierStatut($paiement->pay_token);

        if ($resultat['statut'] === 'SUCCESSFUL') {
            $this->traiterPaiementValide($paiement->id);
            return response()->json([
                'statut'  => 'valide',
                'message' => 'Paiement confirmé. Merci !',
            ]);
        }

        if ($resultat['statut'] === 'FAILED') {
            $this->marquerEchoue($paiement, $resultat['message'] ?? null);

            return response()->json([
                'statut'  => 'echoue',
                'message' => $resultat['message'] ?? 'Paiement refusé par l\'opérateur.',
                'reason'  => $resultat['reason'] ?? null,
            ]);
        }

        return response()->json(['statut' => 'en_attente']);
    }

    /**
     * Annule définitivement un paiement encore en attente (audit C).
     * Le statut passe a 'annule' : le client peut l'afficher tel quel et le
     * paiement ne peut plus etre encaisse (traitement du webhook/polling
     * refuse tout paiement annule).
     */
    public function annuler(Paiement $paiement): JsonResponse
    {
        $user = auth()->user();

        if ($paiement->user_id !== $user->id) {
            return response()->json(['message' => 'Accès non autorisé à ce paiement.'], 403);
        }

        if ($paiement->statut !== Paiement::STATUT_EN_ATTENTE || $paiement->estAnnule()) {
            return response()->json([
                'message' => 'Ce paiement ne peut plus être annulé.',
            ], 422);
        }

        $paiement->update([
            'statut'              => Paiement::STATUT_ANNULE,
            'annule_manuellement' => true,
        ]);

        return response()->json([
            'message' => 'Paiement annulé. Si votre opérateur a malgré tout débité votre compte, contactez notre support avec la référence ' . $paiement->reference . '.',
            'reference'           => $paiement->reference,
            'statut'              => Paiement::STATUT_ANNULE,
            'annule_manuellement' => true,
        ]);
    }

    private function autoriserAccesFrais($user, FraisApprenant $frais): ?JsonResponse
    {
        $estParent = $user->apprenants()
            ->where('apprenants.id', $frais->apprenant_id)
            ->exists();

        if (! $estParent) {
            return response()->json(['message' => 'Vous n\'êtes pas autorisé à accéder à ce dossier de paiement.'], 403);
        }

        $apprenant = $frais->apprenant;
        if ($apprenant && ! $apprenant->valide_par_etablissement) {
            return response()->json([
                'message' => 'Ce rattachement est en attente de validation par l\'établissement.',
            ], 403);
        }

        return null;
    }

    /**
     * Traite un paiement confirmé SUCCESSFUL de manière atomique
     * (verrou pessimiste + re-check, identique à la logique web).
     */
    private function traiterPaiementValide(int $paiementId): bool
    {
        $commissionId = null;

        $resultat = DB::transaction(function () use ($paiementId, &$commissionId) {
            $paiement = Paiement::whereKey($paiementId)->lockForUpdate()->first();

            if (! $paiement || $paiement->statut === 'valide') {
                return false;
            }

            // Annulation définitive (audit C) : un paiement annulé ne peut
            // JAMAIS encaisser, même si l'opérateur renvoie SUCCESSFUL en retard.
            if ($paiement->estAnnule()) {
                Log::warning('Paiement annulé confirmé par l\'opérateur — aucun encaissement (action manuelle requise)', [
                    'paiement_id' => $paiement->id,
                    'reference'   => $paiement->reference,
                    'montant'     => $paiement->montant,
                ]);

                return false;
            }

            $paiement->update([
                'statut'          => 'valide',
                'date_validation' => now(),
            ]);

            $frais = $paiement->fraisApprenant;
            if ($frais) {
                $frais->increment('montant_paye', $paiement->montant);
                $frais->refresh();

                // Le statut du FRAIS doit suivre le montant, pas seulement celui
                // de l'apprenant. La liste des impayes filtre sur
                // FraisApprenant.statut != 'regle' : sans cette ecriture, un
                // apprenant entierement solde restait liste comme impaye avec
                // 0 FCFA restant du.
                $statutFrais = $frais->montant_paye >= $frais->montant_total ? 'regle'
                              : ($frais->montant_paye > 0 ? 'partiel' : 'impaye');

                $frais->update(['statut' => $statutFrais]);
                $frais->apprenant->update(['statut_paiement' => $statutFrais]);
            } else {
                $frais = null;
            }

            SendConfirmationPaiement::dispatch($paiement);

            if ($frais && $frais->apprenant && $frais->apprenant->etablissement) {
                try {
                    $etablissement = $frais->apprenant->etablissement;
                    $commission = \App\Models\Commission::create([
                        'paiement_id'               => $paiement->id,
                        'etablissement_id'          => $etablissement->id,
                        'montant_transaction'       => $paiement->montant,
                        // Taux REELLEMENT preleve sur cette transaction : celui
                        // du profil d'abonnement (CDC S0 #3). On copiait
                        // etablissements.taux_commission, une valeur qui
                        // n'entrait dans aucun calcul et affichait 0,5 % au
                        // lieu des 2,3 % reels. La commission est figee a la
                        // creation : un changement de taux ulterieur ne doit
                        // pas reecrire l'historique (CDC 3.2).
                        'taux'                      => app(\App\Services\AangaraaPayService::class)
                                                        ->tauxCommissionEtablissement($etablissement),
                        'montant_commission'        => $paiement->marge_edupay,
                        // Le net reverse est le montant MOINS la commission
                        // EduPay. Mettre montant - commission ici eviterait de
                        // reverser 100 sur un paiement de 100 et de perdre la
                        // commission : c'etait le cas avant.
                        'montant_net_etablissement' => max(0, $paiement->montant - $paiement->marge_edupay),
                        'frais_aangaraa'            => $paiement->frais_aangaraa,
                        'statut'                    => 'calculee',
                    ]);
                    $commissionId = $commission->id;
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::warning('Commission déjà existante pour ce paiement API, reversement ignoré', ['paiement_id' => $paiement->id]);
                }
            }

            return true;
        });

        if ($resultat && $commissionId) {
            \App\Jobs\ReverserEtablissementJob::dispatch($commissionId);
        }

        return $resultat;
    }

    /**
     * Synchronise un paiement en_attente avec AangaraaPay.
     * Retourne true si un nouveau paiement peut etre lance.
     *
     * Memoire de la version web (Payeur\PaiementController:591-614), passee
     * en API : c'est la seule facon de distinguer un paiement « mort » d'un
     * paiement « vivant » avant d'en creer un second.
     */
    private function synchroniserPaiementEnAttente(Paiement $paiement): bool
    {
        if ($paiement->created_at->lt(now()->subMinutes(5))) {
            $this->marquerEchoue($paiement, 'Délai d\'attente dépassé (5 minutes).');

            return true;
        }

        if (! $paiement->pay_token) {
            return false;
        }

        $check = $this->aangaraa->verifierStatut($paiement->pay_token);

        if ($check['statut'] === 'FAILED') {
            $this->marquerEchoue($paiement, $check['message'] ?? null);

            return true;
        }

        if ($check['statut'] === 'SUCCESSFUL') {
            $this->traiterPaiementValide($paiement->id);
        }

        return false;
    }

    private function marquerEchoue(Paiement $paiement, ?string $raison = null): bool
    {
        $vientDetreMarque = DB::transaction(function () use ($paiement) {
            $p = Paiement::whereKey($paiement->id)->lockForUpdate()->first();
            if ($p && $p->statut === 'en_attente') {
                $p->update(['statut' => 'echoue']);
                return true;
            }
            return false;
        });

        if ($vientDetreMarque) {
            SendNotificationEchecPaiement::dispatch($paiement->fresh(), $raison);
        }

        return $vientDetreMarque;
    }
}
