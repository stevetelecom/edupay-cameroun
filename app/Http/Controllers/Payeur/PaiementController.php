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
use App\Jobs\SendNotificationEchecPaiement;

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

        $telephonePrefill = Auth::user()->telephone
            ? $this->aangaraa->normaliserNumero(Auth::user()->telephone)
            : '';

        return view('payeur.paiement', compact('fraisApprenant', 'telephonePrefill'));
    }

    // ─────────────────────────────────────────────
    // Initier le paiement → appel AangaraaPay
    // ─────────────────────────────────────────────
    public function initier(Request $request, FraisApprenant $fraisApprenant)
    {
        try {
            return $this->executerInitierPaiement($request, $fraisApprenant);
        } catch (\Throwable $e) {
            Log::error('[debug-ee0550] Paiement initier exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile() . ':' . $e->getLine(),
                'user_id' => Auth::id(),
                'frais'   => $fraisApprenant->id,
            ]);

            return back()->withInput()->with(
                'error',
                'Erreur technique lors du paiement. Vérifiez votre numéro (9 chiffres, ex. 654862989) et réessayez.'
            );
        }
    }

    private function executerInitierPaiement(Request $request, FraisApprenant $fraisApprenant)
    {
        $this->autoriserAcces($fraisApprenant);

        // Empêche un double paiement : si un paiement en_attente existe déjà
        // pour ce frais depuis moins de 5 minutes, on redirige vers sa page d'attente
        // au lieu d'en créer un nouveau.
        $paiementRecent = Paiement::where('frais_apprenant_id', $fraisApprenant->id)
            ->where('user_id', Auth::id())
            ->where('statut', 'en_attente')
            ->where('annule_manuellement', false)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->latest()
            ->first();

        // Expirer ou synchroniser les paiements en_attente bloquants
        if ($paiementRecent) {
            $peutRelancer = $this->synchroniserPaiementEnAttente($paiementRecent);

            if (! $peutRelancer) {
                return redirect()
                    ->route('payeur.paiement.attente', $paiementRecent)
                    ->with('info', 'Un paiement est déjà en cours pour ce frais. Confirmez-le sur votre téléphone ou attendez 5 minutes.');
            }
        }

        $validated = $request->validate([
            'type_paiement'      => ['required', Rule::in(['integral', 'tranche'])],
            'mode_paiement'      => ['required', Rule::in(['mtn_momo', 'orange_money'])],
            'telephone_paiement' => ['required', 'string', 'max:20'],
        ]);

        $telephoneNormalise = $this->aangaraa->normaliserNumero($validated['telephone_paiement']);
        $numeroLocal        = substr($telephoneNormalise, 3);

        if (! preg_match('/^6[0-9]{8}$/', $numeroLocal)) {
            return back()->withInput()->withErrors([
                'telephone_paiement' => 'Numéro invalide. Utilisez 9 chiffres (ex. 654862989) ou +237654862989.',
            ]);
        }

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
            'telephone_paiement' => $telephoneNormalise,
            'date_paiement'      => now(),
        ]);

        // Mobile Money → appel AangaraaPay
        $telephone   = $telephoneNormalise;
        $notifyUrl   = config('services.aangaraa.notify_url')
            ?: route('payeur.paiement.webhook');

        if (str_contains($notifyUrl, 'localhost') || str_contains($notifyUrl, '127.0.0.1')) {
            Log::warning('[debug-ee0550] notify_url pointe vers localhost — AangaraaPay refusera probablement le paiement', [
                'notify_url' => $notifyUrl,
                'app_url'    => config('app.url'),
            ]);
        }
        $description = 'EduPay — ' . $fraisApprenant->categorieFrais->nom . ' — ' . $fraisApprenant->apprenant->nom;

        $operateur = match ($validated['mode_paiement']) {
            'mtn_momo'     => 'MTN_Cameroon',
            'orange_money' => 'Orange_Cameroon',
            default        => null,
        };

        // #region agent log
        Log::info('[debug-ee0550] Formulaire paiement recu', [
            'hypothesisId'           => 'A,B,E,D',
            'telephone_saisi'        => $telephone,
            'telephone_normalise'    => $telephoneNormalise,
            'mode_paiement'          => $validated['mode_paiement'],
            'operateur_choisi'       => $operateur,
            'profil_user_telephone'  => Auth::user()->telephone,
            'notify_url'             => $notifyUrl,
            'app_url'                => config('app.url'),
            'reference'              => $paiement->reference,
        ]);
        // #endregion

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

            return back()->withInput()->with('error',
                'Échec du paiement : ' . $resultat['message']
                . ' (numéro envoyé à ' . $this->libelleOperateur($resultat['operateur'] ?? $operateur) . ' : ' . $telephoneNormalise . ')'
            );
        }

        // Sauvegarder le payToken pour vérifier le statut plus tard
        $paiement->update([
            'pay_token'               => $resultat['pay_token'],
            'aangaraa_transaction_id' => $paiement->reference,
            'operateur'               => $resultat['operateur'],
        ]);

        // Note sécurité (E-04 audit) : l'ancienne vérification synchrone (usleep 1.5s
        // + appel API bloquant le worker PHP-FPM à chaque paiement) a été retirée —
        // incompatible avec la charge visée (500 tx/min, CDC §6.4). La page d'attente
        // fait déjà un polling à 5s qui détecte PENDING/FAILED/SUCCESSFUL de façon
        // non bloquante, avec le même message d'erreur précis à l'utilisateur.

        return redirect()
            ->route('payeur.paiement.attente', $paiement)
            ->with('info', 'Confirmez le paiement sur votre téléphone ' . $telephoneNormalise);
    }

    /**
     * Libelle court et lisible d'un code operateur AangaraaPay, utilise dans
     * les messages affiches au payeur (toasts, emails).
     */
    private function libelleOperateur(?string $operateur): string
    {
        return match ($operateur) {
            'MTN_Cameroon'    => 'MTN',
            'Orange_Cameroon' => 'Orange',
            default           => $operateur ?? 'l\'opérateur',
        };
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
            $this->marquerEchoue($paiement, $resultat['message'] ?? null);

            return response()->json([
                'statut'  => 'echoue',
                'message' => $resultat['message'] ?? 'Paiement refusé par l\'opérateur.',
                'reason'  => $resultat['reason']  ?? null,
            ]);
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
        // Sécurité (E-02 audit) : l'appel HTTP de reversement (timeout 30s)
        // a été sorti de la transaction verrouillée — on récupère juste l'ID
        // de la commission créée, et on dispatche le job APRÈS le commit.
        $commissionId = null;

        $resultat = \Illuminate\Support\Facades\DB::transaction(function () use ($paiementId, &$commissionId) {
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
                // Violation de contrainte unique = une commission existe déjà pour ce paiement.
                // Filet de sécurité ultime : on arrête là, pas de double reversement.
                \Illuminate\Support\Facades\Log::warning('Commission déjà existante pour ce paiement, reversement ignoré', [
                    'paiement_id' => $paiement->id,
                ]);
            }

            return true;
        });

        // Le verrou est relâché (transaction commitée) — l'appel HTTP externe
        // de reversement se fait maintenant en dehors de toute transaction DB,
        // via une file d'attente avec retry automatique.
        if ($resultat && $commissionId) {
            \App\Jobs\ReverserEtablissementJob::dispatch($commissionId);
        }

        return $resultat;
    }

    // ─────────────────────────────────────────────
    // Webhook AangaraaPay (POST public, sans CSRF)
    // ─────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $payload = $request->all();

        Log::info('AangaraaPay webhook reçu (non fiable, à revérifier)', $payload);

        // AangaraaPay envoie deux types de webhooks avec des champs différents :
        // - webhook synchrone immédiat : transaction_id = NOTRE référence (EP2026-XXXXX)
        // - webhook différé post-confirmation opérateur : transaction_id = ID INTERNE AangaraaPay (ex: "2808")
        //   mais le champ "paytoken" contient toujours NOTRE pay_token, stable des deux côtés.
        // On priorise donc systématiquement le pay_token pour retrouver le paiement.
        $payToken  = $payload['paytoken'] ?? $payload['pay_token'] ?? $payload['payToken'] ?? null;
        $reference = $payload['transaction_id'] ?? null;

        $paiement = null;

        if ($payToken) {
            $paiement = Paiement::where('pay_token', $payToken)->first();
        }

        if (! $paiement && $reference) {
            $paiement = Paiement::where('reference', $reference)->first();
        }

        if (! $paiement) {
            Log::warning('Webhook AangaraaPay : paiement introuvable', [
                'reference' => $reference,
                'pay_token' => $payToken,
            ]);
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
            $this->marquerEchoue($paiement, $verification['message'] ?? null);
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

    /**
     * Réconciliation de sécurité — appelée par la commande planifiée aangaraa:reconcilie.
     * Filet de sécurité totalement indépendant du webhook (qui peut échouer) et du
     * polling client (qui s'arrête après ~20 min) : revérifie directement auprès
     * d'AangaraaPay les paiements restés en_attente, et finalise leur statut.
     */
    public function reconcilierPaiement(Paiement $paiement): void
    {
        if (! $paiement->pay_token || in_array($paiement->statut, ['valide', 'echoue', 'rembourse'])) {
            return;
        }

        $resultat = $this->aangaraa->verifierStatut($paiement->pay_token);

        if ($resultat['statut'] === 'SUCCESSFUL') {
            $this->traiterPaiementValide($paiement->id);
            return;
        }

        if ($resultat['statut'] === 'FAILED') {
            $this->marquerEchoue($paiement, $resultat['message'] ?? null);
        }
    }

    /**
     * Marque un paiement comme echoue de maniere atomique et idempotente,
     * puis notifie le payeur (email + SMS). Point d'entree UNIQUE pour tout
     * passage a l'etat 'echoue' — evite les envois en double et garantit
     * que le payeur est toujours informe, peu importe le chemin (polling
     * client, webhook, reconciliation planifiee).
     */
    private function marquerEchoue(Paiement $paiement, ?string $raison = null): bool
    {
        $vientDetreMarque = \Illuminate\Support\Facades\DB::transaction(function () use ($paiement) {
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

    /**
     * Annulation manuelle par le payeur d'un paiement en_attente.
     * Ne modifie JAMAIS le statut reel (reste en_attente) afin qu'une
     * confirmation tardive et legitime de l'operateur (webhook ou
     * reconciliation) puisse toujours regulariser le paiement plus tard
     * si l'argent a en realite ete debite. Sert uniquement a debloquer
     * un nouvel essai immediat sans attendre les 5 minutes.
     */
    public function annuler(Paiement $paiement)
    {
        if ($paiement->user_id !== Auth::id()) {
            abort(403);
        }

        if ($paiement->statut !== 'en_attente') {
            return back()->with('error', 'Ce paiement ne peut plus être annulé.');
        }

        $paiement->update(['annule_manuellement' => true]);

        return back()->with('info',
            'Paiement marqué comme annulé. S\'il a tout de même été débité sur votre compte, il sera automatiquement régularisé dès confirmation de l\'opérateur.'
        );
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

        // 🔒 Sécurité (E-01) : tant que l'établissement n'a pas validé le
        // rattachement (auto-création via apprenant_id/matricule au moment
        // de l'onboarding), on bloque l'accès aux frais/solde — évite qu'un
        // payeur consulte les données financières d'un enfant qui n'est pas
        // réellement le sien avant vérification par l'école.
        $apprenant = $fraisApprenant->apprenant;
        if ($apprenant && ! $apprenant->valide_par_etablissement) {
            abort(403, 'Ce rattachement est en attente de validation par l\'établissement. '
                . 'Vous serez notifié dès qu\'il sera confirmé.');
        }
    }

    /**
     * Synchronise un paiement en_attente avec AangaraaPay.
     * Retourne true si un nouveau paiement peut être lancé.
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
