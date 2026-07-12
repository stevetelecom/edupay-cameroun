<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Services\AangaraaPayService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Jobs\SendConfirmationPaiement;

class PaiementController extends Controller
{
    public function __construct(private AangaraaPayService $aangaraa) {}

    // ─────────────────────────────────────────────
    // Page paiement
    // ─────────────────────────────────────────────
    public function show(FraisApprenant $fraisApprenant)
    {
        $this->autoriserAcces($fraisApprenant);
        $fraisApprenant->load(['apprenant.etablissement', 'categorieFrais']);
        return view('payeur.paiement', compact('fraisApprenant'));
    }

    // ─────────────────────────────────────────────
    // Initier le paiement → appel AangaraaPay
    // ─────────────────────────────────────────────
    public function initier(Request $request, FraisApprenant $fraisApprenant)
    {
        $this->autoriserAcces($fraisApprenant);

        // Empêche un double paiement : si un paiement en_attente existe déjà
        // pour ce frais depuis moins de 5 minutes, on redirige vers sa page d'attente
        // au lieu d'en créer un nouveau.
        $paiementRecent = Paiement::where('frais_apprenant_id', $fraisApprenant->id)
            ->where('user_id', Auth::id())
            ->where('statut', 'en_attente')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest()
            ->first();

        if ($paiementRecent) {
            return redirect()
                ->route('payeur.paiement.attente', $paiementRecent)
                ->with('info', 'Un paiement est déjà en cours pour ce frais. Confirmez-le ou attendez son expiration avant de recommencer.');
        }

        $validated = $request->validate([
            'type_paiement'      => ['required', Rule::in(['integral', 'tranche'])],
            'mode_paiement'      => ['required', Rule::in(['mtn_momo', 'orange_money'])],
            'telephone_paiement' => ['required', 'string', 'max:20'],
        ]);

        $resteAPayer = $fraisApprenant->montant_total - $fraisApprenant->montant_paye;

        $montant = $validated['type_paiement'] === 'tranche'
            ? (int) round($resteAPayer / ($fraisApprenant->categorieFrais->nb_tranches_max ?? 2))
            : (int) $resteAPayer;

        // Calculer les frais de service (visibles payeur = EduPay + AangaraaPay fusionnés)
        $frais = $this->aangaraa->calculerFrais($montant);

        // Créer le paiement en base avec statut en_attente
        $paiement = Paiement::create([
            'user_id'            => Auth::id(),
            'apprenant_id'       => $fraisApprenant->apprenant_id,
            'frais_apprenant_id' => $fraisApprenant->id,
            'montant'            => $montant,
            'frais_service'      => $frais['frais_service'],
            'montant_total_paye' => $frais['montant_total_paye'],
            'frais_aangaraa'     => $frais['frais_aangaraa'],
            'marge_edupay'       => $frais['marge_edupay'],
            'mode_paiement'      => $validated['mode_paiement'],
            'type_paiement'      => $validated['type_paiement'],
            'statut'             => 'en_attente',
            'telephone_paiement' => $validated['telephone_paiement'] ?? null,
            'date_paiement'      => now(),
        ]);

        // Mobile Money → appel AangaraaPay
        $telephone  = $validated['telephone_paiement'];
        $notifyUrl  = route('payeur.paiement.webhook');
        $description = 'EduPay — ' . $fraisApprenant->categorieFrais->nom . ' — ' . $fraisApprenant->apprenant->nom;

        $resultat = $this->aangaraa->initierPaiement(
            telephone:     $telephone,
            montant:       $paiement->montant_total_paye, // montant + frais service
            description:   $description,
            transactionId: $paiement->reference,
            notifyUrl:     $notifyUrl
        );

        if (! $resultat['succes']) {
            $paiement->update(['statut' => 'echoue']);

            return back()->with('error',
                'Échec de l\'initialisation du paiement : ' . $resultat['message']
            );
        }

        // Sauvegarder le payToken pour vérifier le statut plus tard
        $paiement->update([
            'pay_token'             => $resultat['pay_token'],
            'aangaraa_transaction_id' => $paiement->reference,
            'operateur'             => $resultat['operateur'],
        ]);

        return redirect()
            ->route('payeur.paiement.attente', $paiement)
            ->with('info', 'Confirmez le paiement sur votre téléphone ' . $telephone);
    }

    // ─────────────────────────────────────────────
    // Page d'attente — polling statut
    // ─────────────────────────────────────────────
    public function attente(Paiement $paiement)
    {
        // Sécurité : seul le payeur propriétaire accède
        if ($paiement->user_id !== Auth::id()) {
            abort(403);
        }

        return view('payeur.paiement_attente', compact('paiement'));
    }

    // ─────────────────────────────────────────────
    // Vérification AJAX du statut (appelée par la page d'attente)
    // ─────────────────────────────────────────────
    public function verifierStatut(Paiement $paiement)
    {
        if ($paiement->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $paiement->pay_token) {
            return response()->json(['statut' => $paiement->statut]);
        }

        // Si déjà validé ou échoué, retourner directement (avant même de verrouiller)
        if (in_array($paiement->statut, ['valide', 'echoue', 'rembourse'])) {
            return response()->json(['statut' => $paiement->statut]);
        }

        $resultat = $this->aangaraa->verifierStatut($paiement->pay_token);

        if ($resultat['statut'] === 'SUCCESSFUL') {
            $traite = $this->traiterPaiementValide($paiement->id);
            return response()->json(['statut' => $traite ? 'valide' : $paiement->fresh()->statut]);
        }

        if ($resultat['statut'] === 'FAILED') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($paiement) {
                $p = Paiement::whereKey($paiement->id)->lockForUpdate()->first();
                if ($p && $p->statut === 'en_attente') {
                    $p->update(['statut' => 'echoue']);
                }
            });
            return response()->json(['statut' => 'echoue']);
        }

        return response()->json(['statut' => 'en_attente']);
    }

    /**
     * Traite un paiement confirmé SUCCESSFUL de manière atomique.
     * Protège contre les race conditions webhook / polling AJAX simultanés :
     * verrou pessimiste + re-check du statut à l'intérieur de la transaction.
     * Retourne true si ce traitement est celui qui a réellement validé le paiement
     * (false si un autre process l'avait déjà fait entre-temps).
     */
    private function traiterPaiementValide(int $paiementId): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($paiementId) {
            // Verrou pessimiste : bloque toute autre transaction qui tenterait
            // de lire/modifier cette même ligne tant qu'on n'a pas fini.
            $paiement = Paiement::whereKey($paiementId)->lockForUpdate()->first();

            if (! $paiement || $paiement->statut === 'valide') {
                // Déjà traité par un autre process (webhook ou polling) → on ne refait rien
                return false;
            }

            $paiement->update([
                'statut'          => 'valide',
                'date_validation' => now(),
            ]);

            $frais = $paiement->fraisApprenant;
            $frais->increment('montant_paye', $paiement->montant);
            $frais->refresh();

            $statutApprenant = $frais->montant_paye >= $frais->montant_total ? 'regle'
                             : ($frais->montant_paye > 0 ? 'partiel' : 'impaye');
            $frais->apprenant->update(['statut_paiement' => $statutApprenant]);

            SendConfirmationPaiement::dispatch($paiement);

            // Commission — protégée aussi par la contrainte UNIQUE en base (paiement_id)
            $etablissement = $frais->apprenant->etablissement;

            try {
                \App\Models\Commission::create([
                    'paiement_id'               => $paiement->id,
                    'etablissement_id'          => $etablissement->id,
                    'montant_transaction'       => $paiement->montant,
                    'taux'                      => $etablissement->taux_commission,
                    'montant_commission'        => $paiement->marge_edupay,
                    'montant_net_etablissement' => $paiement->montant,
                    'frais_aangaraa'            => $paiement->frais_aangaraa,
                    'statut'                    => 'calculee',
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Violation de contrainte unique = une commission existe déjà pour ce paiement.
                // Filet de sécurité ultime : on arrête là, pas de double reversement.
                \Illuminate\Support\Facades\Log::warning('Commission déjà existante pour ce paiement, reversement ignoré', [
                    'paiement_id' => $paiement->id,
                ]);
                return true; // le paiement est bien validé, juste pas de 2e commission/reversement
            }

            if ($etablissement->numero_momo_reversement) {
                $resultatReversement = $this->aangaraa->reverserEtablissement(
                    telephone:   $etablissement->numero_momo_reversement,
                    operateur:   $etablissement->operateur_momo_reversement ?? 'mtn',
                    montant:     $paiement->montant,
                    description: 'Reversement EduPay — ' . $paiement->reference
                );

                if ($resultatReversement['succes']) {
                    \App\Models\Commission::where('paiement_id', $paiement->id)
                        ->update([
                            'statut'                => 'prelevee',
                            'reference_reversement' => $resultatReversement['reference'],
                            'reversed_at'           => now(),
                        ]);
                }
            }

            return true;
        });
    }

    // ─────────────────────────────────────────────
    // Webhook AangaraaPay (POST public, sans CSRF)
    // ─────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload = $request->all();

        Log::info('AangaraaPay webhook reçu (non fiable, à revérifier)', $payload);

        $reference = $payload['transaction_id'] ?? null;

        if (! $reference) {
            return response()->json(['ok' => false], 400);
        }

        $paiement = Paiement::where('reference', $reference)->first();

        if (! $paiement) {
            Log::warning('Webhook AangaraaPay : paiement introuvable', ['reference' => $reference]);
            return response()->json(['ok' => false], 404);
        }

        // Idempotence : déjà traité, ne rien refaire
        if (in_array($paiement->statut, ['valide', 'echoue', 'rembourse'])) {
            return response()->json(['ok' => true, 'deja_traite' => true]);
        }

        if (! $paiement->pay_token) {
            Log::warning('Webhook AangaraaPay : paiement sans pay_token, impossible de revérifier', ['reference' => $reference]);
            return response()->json(['ok' => false], 422);
        }

        // 🔒 SÉCURITÉ CRITIQUE : on ne fait JAMAIS confiance au statut envoyé dans le webhook.
        // On revérifie systématiquement via un appel serveur-à-serveur authentifié par notre app_key.
        // Ainsi, un webhook forgé (POST direct sans vraie transaction) ne peut jamais valider un paiement.
        $verification = $this->aangaraa->verifierStatut($paiement->pay_token);

        if (($payload['status'] ?? null) !== $verification['statut']) {
            Log::warning('Webhook AangaraaPay : statut annoncé non confirmé par revérification API', [
                'reference'          => $reference,
                'statut_webhook'     => $payload['status'] ?? null,
                'statut_revérifié'   => $verification['statut'],
                'ip'                 => $request->ip(),
            ]);

            // Alerte email au Super Admin — discordance entre webhook et vérification API
            try {
                \Illuminate\Support\Facades\Mail::to('moffosteve2@gmail.com')
                    ->send(new \App\Mail\AlerteWebhookSuspectMail(
                        reference:      $reference,
                        statutAnnonce:  $payload['status'] ?? null,
                        statutReel:     $verification['statut'],
                        ip:             $request->ip(),
                        payloadComplet: $payload,
                    ));
            } catch (\Throwable $e) {
                Log::error('Échec envoi alerte webhook suspect : ' . $e->getMessage());
            }
        }

        $statut = $verification['statut'];

        if ($statut === 'SUCCESSFUL') {
            $this->traiterPaiementValide($paiement->id);
        }

        if ($statut === 'FAILED') {
            \Illuminate\Support\Facades\DB::transaction(function () use ($paiement) {
                $p = Paiement::whereKey($paiement->id)->lockForUpdate()->first();
                if ($p && $p->statut === 'en_attente') {
                    $p->update(['statut' => 'echoue']);
                }
            });
        }

        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────
    // Historique
    // ─────────────────────────────────────────────
    public function historique(Request $request)
    {
        $query = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais', 'remboursements' => function ($q) {
                $q->where('statut', 'valide');
            }])
            ->where('user_id', Auth::id())
            ->latest('date_paiement');

        if ($request->get('export') === 'pdf') {
            $paiements = $query->get();
            $user      = Auth::user();
            $pdf = Pdf::loadView('pdf.historique_paiements', compact('paiements', 'user'))
                      ->setPaper('a4', 'portrait');
            return $pdf->download('historique_edupay_' . now()->format('Ymd') . '.pdf');
        }

        $paiements = $query->paginate(15);
        return view('payeur.historique', compact('paiements'));
    }

    private function autoriserAcces(FraisApprenant $fraisApprenant): void
    {
        $estParent = Auth::user()
            ->apprenants()
            ->where('apprenants.id', $fraisApprenant->apprenant_id)
            ->exists();

        if (! $estParent) {
            abort(403, 'Vous n\'êtes pas autorisé à accéder à ce dossier de paiement.');
        }
    }

    // ─────────────────────────────────────────────
    // F05 — Vue frais détaillés par apprenant
    // ─────────────────────────────────────────────
    public function fraisApprenant(\App\Models\Apprenant $apprenant)
    {
        $estRattache = Auth::user()
            ->apprenants()
            ->where('apprenant_id', $apprenant->id)
            ->exists();

        abort_unless($estRattache, 403);

        $apprenant->load([
            'etablissement',
            'frais.categorieFrais.echeanciers',
            'frais.paiements' => fn($q) => $q->where('statut', 'valide')->latest(),
        ]);

        return view('payeur.frais_apprenant', compact('apprenant'));
    }

}
