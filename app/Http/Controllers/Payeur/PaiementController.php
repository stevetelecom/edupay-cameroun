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

        // Si déjà validé ou échoué, retourner directement
        if (in_array($paiement->statut, ['valide', 'echoue', 'rembourse'])) {
            return response()->json(['statut' => $paiement->statut]);
        }

        $resultat = $this->aangaraa->verifierStatut($paiement->pay_token);

        if ($resultat['statut'] === 'SUCCESSFUL') {
            $paiement->update([
                'statut'          => 'valide',
                'date_validation' => now(),
            ]);

            // Mettre à jour montant_paye sur FraisApprenant
            $frais = $paiement->fraisApprenant;
            $frais->increment('montant_paye', $paiement->montant);

            // Mettre à jour statut_paiement sur Apprenant
            $frais->refresh();
            $statut = $frais->montant_paye >= $frais->montant_total ? 'regle'
                    : ($frais->montant_paye > 0 ? 'partiel' : 'impaye');
            $frais->apprenant->update(['statut_paiement' => $statut]);

            // F12-A — Envoyer email + SMS de confirmation
            SendConfirmationPaiement::dispatch($paiement);

            return response()->json(['statut' => 'valide']);
        }

        if ($resultat['statut'] === 'FAILED') {
            $paiement->update(['statut' => 'echoue']);
            return response()->json(['statut' => 'echoue']);
        }

        return response()->json(['statut' => 'en_attente']);
    }

    // ─────────────────────────────────────────────
    // Webhook AangaraaPay (POST public, sans CSRF)
    // ─────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload = $request->all();

        Log::info('AangaraaPay webhook reçu', $payload);

        $reference = $payload['transaction_id'] ?? null;
        $statut    = $payload['status']         ?? null;

        if (! $reference || ! $statut) {
            return response()->json(['ok' => false], 400);
        }

        $paiement = Paiement::where('reference', $reference)->first();

        if (! $paiement) {
            Log::warning('Webhook AangaraaPay : paiement introuvable', ['reference' => $reference]);
            return response()->json(['ok' => false], 404);
        }

        if ($statut === 'SUCCESSFUL' && $paiement->statut !== 'valide') {
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

            // F12-A — Envoyer email + SMS de confirmation
            SendConfirmationPaiement::dispatch($paiement);

            // Enregistrer la commission EduPay en base
            $fraisApprenant = $paiement->fraisApprenant;
            $etablissement  = $fraisApprenant->apprenant->etablissement;

            \App\Models\Commission::create([
                'paiement_id'             => $paiement->id,
                'etablissement_id'        => $etablissement->id,
                'montant_transaction'     => $paiement->montant,
                'taux'                    => $etablissement->taux_commission,
                'montant_commission'      => $paiement->marge_edupay,
                'montant_net_etablissement' => $paiement->montant,
                'frais_aangaraa'          => $paiement->frais_aangaraa,
                'statut'                  => 'calculee',
            ]);

            // Reversement automatique vers l'établissement
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
                            'statut'               => 'prelevee',
                            'reference_reversement' => $resultatReversement['reference'],
                            'reversed_at'          => now(),
                        ]);
                }
            }
        }

        if ($statut === 'FAILED' && $paiement->statut === 'en_attente') {
            $paiement->update(['statut' => 'echoue']);
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
