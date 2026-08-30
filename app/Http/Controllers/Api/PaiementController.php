<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitierPaiementRequest;
use App\Http\Resources\PaiementResource;
use App\Jobs\SendConfirmationPaiement;
use App\Jobs\SendNotificationEchecPaiement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Services\AangaraaPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    public function __construct(private AangaraaPayService $aangaraa) {}

    /**
     * Historique des paiements de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', $request->user()->id)
            ->latest('date_paiement')
            ->paginate($request->integer('per_page', 15));

        return PaiementResource::collection($paiements);
    }

    /**
     * Initie un paiement Mobile Money via AangaraaPay.
     */
    public function initier(InitierPaiementRequest $request): JsonResponse
    {
        $user    = $request->user();
        $valid   = $request->validated();

        // Le paiement doit porter sur un frais d'un apprenant rattaché et validé.
        if (! empty($valid['frais_apprenant_id'])) {
            $frais = FraisApprenant::with(['categorieFrais', 'apprenant.etablissement'])
                ->findOrFail($valid['frais_apprenant_id']);

            $lien = $this->autoriserAccesFrais($user, $frais);
            if ($lien instanceof JsonResponse) {
                return $lien;
            }
        } elseif (! empty($valid['apprenant_id'])) {
            $rattache = $user->apprenants()
                ->where('apprenants.id', $valid['apprenant_id'])
                ->first();

            if (! $rattache) {
                return response()->json(['message' => 'Cet apprenant ne vous est pas rattaché.'], 403);
            }
            if (! $rattache->valide_par_etablissement) {
                return response()->json(['message' => 'Rattachement en attente de validation par l\'établissement.'], 403);
            }
        }

        $telephoneNormalise = $this->aangaraa->normaliserNumero($valid['telephone']);
        $numeroLocal        = substr($telephoneNormalise, 3);

        if (! preg_match('/^6[0-9]{8}$/', $numeroLocal)) {
            return response()->json([
                'message' => 'Numéro invalide.',
                'errors'  => ['telephone' => ['Utilisez 9 chiffres (ex. 654862989) ou +237654862989.']],
            ], 422);
        }

        // Déterminer le montant + frais selon la cible du paiement
        if (! empty($valid['frais_apprenant_id'])) {
            $resteAPayer = $frais->montant_total - $frais->montant_paye;
            $type        = $valid['type_paiement'] ?? 'integral';

            $montant = $type === 'tranche'
                ? (int) round($resteAPayer / ($frais->categorieFrais->nb_tranches_max ?? 2))
                : (int) $resteAPayer;

            $apprenantId   = $frais->apprenant_id;
            $fraisId       = $frais->id;
            $description   = 'EduPay — ' . $frais->categorieFrais->nom . ' — ' . ($frais->apprenant->nom ?? '');
        } else {
            $montant     = (int) ($valid['montant'] ?? 0);
            $apprenantId = $valid['apprenant_id'] ?? null;
            $fraisId     = $valid['frais_apprenant_id'] ?? null;
            $type        = 'integral';
            $description = 'EduPay — Paiement direct';
        }

        if ($montant < 50) {
            return response()->json([
                'message' => 'Montant invalide.',
                'errors'  => ['montant' => ['Le montant doit être d\'au moins 50 FCFA.']],
            ], 422);
        }

        $frais = $this->aangaraa->calculerFrais($montant);

        $paiement = Paiement::create([
            'user_id'            => $user->id,
            'apprenant_id'       => $apprenantId,
            'frais_apprenant_id' => $fraisId,
            'montant'            => $montant,
            'frais_service'      => $frais['frais_service'],
            'montant_total_paye' => $frais['montant_total_paye'],
            'frais_aangaraa'     => $frais['frais_aangaraa'],
            'marge_edupay'       => $frais['marge_edupay'],
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

        $operateur = match ($valid['mode_paiement']) {
            'mtn_momo'     => 'MTN_Cameroon',
            'orange_money' => 'Orange_Cameroon',
            default        => null,
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

        if (in_array($paiement->statut, ['valide', 'echoue', 'rembourse'])) {
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

            $paiement->update([
                'statut'          => 'valide',
                'date_validation' => now(),
            ]);

            $frais = $paiement->fraisApprenant;
            if ($frais) {
                $frais->increment('montant_paye', $paiement->montant);
                $frais->refresh();

                $statutApprenant = $frais->montant_paye >= $frais->montant_total ? 'regle'
                                 : ($frais->montant_paye > 0 ? 'partiel' : 'impaye');
                $frais->apprenant->update(['statut_paiement' => $statutApprenant]);
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
                        'taux'                      => $etablissement->taux_commission,
                        'montant_commission'        => $paiement->marge_edupay,
                        'montant_net_etablissement' => $paiement->montant,
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
