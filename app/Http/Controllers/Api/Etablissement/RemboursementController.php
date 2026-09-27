<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Remboursement;
use App\Support\TexteLibre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class RemboursementController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des demandes de remboursement de l'établissement.
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $remboursements = Remboursement::with(['paiement.apprenant', 'paiement.fraisApprenant.categorieFrais', 'initiateur', 'traiteur'])
            ->whereHas('paiement.apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $remboursements->map(fn ($r) => [
                'id'                => $r->id,
                'reference'         => $r->reference,
                'paiement'          => [
                    'id'        => $r->paiement->id,
                    'reference' => $r->paiement->reference,
                    'montant'   => (float) $r->paiement->montant,
                    // O : `categorieFrais` est la cle canonique lue par le
                    // mobile ; `frais` (texte) reste pour les anciens clients.
                    'categorieFrais' => $r->paiement->fraisApprenant?->categorieFrais ? [
                        'id'            => $r->paiement->fraisApprenant->categorieFrais->id,
                        'nom'           => $r->paiement->fraisApprenant->categorieFrais->nom,
                        'annee_scolaire' => $r->paiement->fraisApprenant->categorieFrais->annee_scolaire,
                    ] : null,
                    'frais'     => $r->paiement->fraisApprenant?->categorieFrais?->nom,
                    // L : l'apprenant etait serialise en texte ("Nom Prenom"),
                    // le mobile ne pouvait ni l'afficher proprement ni ouvrir
                    // sa fiche. On expose l'objet, plus `apprenant_nom` pour
                    // les clients qui affichaient la chaine.
                    'apprenant' => $r->paiement->apprenant ? [
                        'id'         => $r->paiement->apprenant->id,
                        'matricule'  => $r->paiement->apprenant->matricule,
                        'nom'        => $r->paiement->apprenant->nom,
                        'prenom'     => $r->paiement->apprenant->prenom,
                        'nom_complet' => trim($r->paiement->apprenant->prenom . ' ' . $r->paiement->apprenant->nom),
                        'classe'     => $r->paiement->apprenant->classe,
                    ] : null,
                    'apprenant_nom' => $r->paiement->apprenant
                        ? trim($r->paiement->apprenant->prenom . ' ' . $r->paiement->apprenant->nom)
                        : null,
                ],
                'montant'           => (float) $r->montant,
                'motif'             => $r->motif,
                'statut'            => $r->statut,
                'motif_refus'       => $r->motif_refus,
                // N : alias `reponse_admin`, le nom utilise par l'ecran de
                // validation cote mobile pour la raison d'un rejet.
                'reponse_admin'     => $r->motif_refus,
                'initie_par'        => $r->initiateur ? ($r->initiateur->prenom . ' ' . $r->initiateur->nom) : null,
                'traite_par'        => $r->traiteur ? ($r->traiteur->prenom . ' ' . $r->traiteur->nom) : null,
                'traite_le'         => $r->traite_le?->toISOString(),
                'created_at'        => $r->created_at?->toISOString(),
            ])->values(),
            'meta' => [
                'current_page' => $remboursements->currentPage(),
                'last_page'    => $remboursements->lastPage(),
                'total'        => $remboursements->total(),
                'per_page'     => $remboursements->perPage(),
            ],
        ]);
    }

    /**
     * Crée une demande de remboursement sur un paiement valide.
     */
    public function store(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();

        $validated = $request->validate([
            'paiement_id' => ['required', 'exists:paiements,id'],
            'montant'     => ['required', 'numeric', 'min:1'],
            'motif'       => ['required', 'string', 'max:255'],
        ], [
            'paiement_id.required' => 'Veuillez sélectionner un paiement.',
            'montant.min'          => 'Le montant doit être supérieur à 0.',
            'motif.required'       => 'Veuillez préciser le motif du remboursement.',
        ]);

        $paiement = Paiement::with('apprenant')->findOrFail($validated['paiement_id']);

        if ($paiement->statut !== 'valide') {
            return response()->json([
                'message' => 'Seul un paiement validé peut être remboursé.',
            ], 422);
        }

        abort_unless(
            $paiement->apprenant->etablissement_id === $etablissementId,
            403,
            'Ce paiement n\'appartient pas à votre établissement.'
        );

        $remboursementExistant = Remboursement::where('paiement_id', $paiement->id)
            ->whereIn('statut', ['en_attente', 'approuve'])
            ->exists();

        if ($remboursementExistant) {
            return response()->json([
                'message' => 'Un remboursement est déjà en cours pour ce paiement.',
            ], 422);
        }

        if ($validated['montant'] > $paiement->montant) {
            return response()->json([
                'message' => 'Le montant ne peut pas dépasser celui du paiement ('
                    . number_format($paiement->montant, 0, ',', ' ') . ' FCFA).',
            ], 422);
        }

        $remboursement = Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => $validated['montant'],
            'motif'       => $validated['motif'],
            'statut'      => 'en_attente',
            'initie_par'  => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Demande de remboursement créée avec succès.',
            'data'    => [
                'id'     => $remboursement->id,
                'montant' => (float) $remboursement->montant,
                'statut' => 'en_attente',
                'motif'  => $remboursement->motif,
            ],
        ], 201);
    }

    /**
     * Approuve une demande de remboursement (directeur / comptable).
     */
    public function approuver(Request $request, $remboursement): JsonResponse
    {
        $this->autoriser();
        $remboursement = $this->resoudre($remboursement);
        $this->autoriserTraitement();
        $this->autoriserAcces($remboursement);

        if ($remboursement->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        $remboursement->update([
            'statut'     => 'approuve',
            'traite_par' => auth()->id(),
            'traite_le'  => now(),
        ]);

        if ($remboursement->montant >= $remboursement->paiement->montant) {
            $remboursement->paiement->update(['statut' => 'rembourse']);
        }

        // Avertissement claw-back : si l'argent a deja ete reverse a
        // l'etablissement, rembourser le parent fait sortir de l'etablissement
        // une somme qui lui a deja ete versee. Avant ce controle rien ne le
        // signalait : le remboursement etait approuve en base sans trace.
        $commission = $remboursement->paiement->commission()->first();

        $dejaReverse = $commission?->reversementEffectue() === true;

        if ($dejaReverse) {
            Log::critical('Remboursement approuve alors que le reversement etait deja effectue', [
                'remboursement_id'  => $remboursement->id,
                'paiement_id'       => $remboursement->paiement_id,
                'commission_id'     => $commission->id,
                'montant_rembourse' => (float) $remboursement->montant,
                'reference'         => $commission->reference_reversement,
            ]);
        }

        $message = 'Remboursement de '
            . number_format($remboursement->montant, 0, ',', ' ') . ' FCFA approuve.';

        if ($dejaReverse) {
            $message .= ' ATTENTION : cet argent a deja ete reverse a votre etablissement (reference '
                . ($commission->reference_reversement ?? 'inconnue') . ') : l\'etablissement doit le '
                . 'restituer avant que le parent ne soit rembourse, sinon EduPay paie deux fois.';
        }

        return response()->json([
            'message' => $message,
            'data'    => array_filter([
                'id'                   => $remboursement->id,
                'statut'               => 'approuve',
                'alerte_reversement'   => $dejaReverse
                    ? 'Le reversement AangaraaPay de ce paiement a deja ete effectue : une restitution par l\'etablissement est necessaire avant le remboursement du parent.'
                    : null,
            ]),
        ]);
    }

    /**
     * Refuse une demande de remboursement.
     */
    public function refuser(Request $request, $remboursement): JsonResponse
    {
        $this->autoriser();
        $remboursement = $this->resoudre($remboursement);
        $this->autoriserTraitement();
        $this->autoriserAcces($remboursement);

        if ($remboursement->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        // N : un refus sans motif n'est pas opposable au payeur. Le champ
        // etait `nullable`, la raison etait donc perdue. `reponse_admin` est
        // accepte comme alias (nom du formulaire mobile).
        $validated = $request->validate([
            'motif_refus'   => ['required_without:reponse_admin', 'nullable', 'string', 'max:500'],
            'reponse_admin' => ['required_without:motif_refus', 'nullable', 'string', 'max:500'],
        ], [
            'motif_refus.required_without'   => 'Veuillez indiquer le motif du refus.',
            'reponse_admin.required_without' => 'Veuillez indiquer le motif du refus.',
        ]);

        $motif = TexteLibre::normaliser($validated['motif_refus'] ?? $validated['reponse_admin'] ?? null);

        if ($motif === null) {
            return response()->json([
                'message' => 'Veuillez indiquer le motif du refus.',
                'errors'  => ['motif_refus' => ['Veuillez indiquer le motif du refus.']],
            ], 422);
        }

        $remboursement->update([
            'statut'      => 'refuse',
            'traite_par'  => auth()->id(),
            'traite_le'   => now(),
            'motif_refus' => $motif,
        ]);

        return response()->json([
            'message' => 'Demande de remboursement refusée.',
            'data'    => [
                'id'     => $remboursement->id,
                'statut' => 'refuse',
            ],
        ]);
    }

    /**
     * Resout la demande visee par la route.
     *
     * La route est declaree avec `{remboursement}` mais le contrat
     * (docs/DOCUMENTATION_API.md) expose
     * `POST /remboursements/{paiement_id}/approuver` : le mobile envoie
     * l'identifiant du PAIEMENT. On accepte les deux, en&idotent sur le
     * remboursement d'abord, puis sur son paiement.
     */
    private function resoudre(int|string $identifiant): Remboursement
    {
        return Remboursement::where('id', $identifiant)
            ->orWhereHas('paiement', fn ($q) => $q->where('paiements.id', $identifiant))
            ->firstOrFail();
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, __('api.acces_etablissement'));
        }

        return $user->etablissement_id;
    }

    private function autoriserTraitement(): void
    {
        abort_unless(
            auth()->user()->hasRole('directeur') || auth()->user()->hasRole('comptable'),
            403,
            'Seuls le directeur et le comptable peuvent traiter les remboursements.'
        );
    }

    private function autoriserAcces(Remboursement $remboursement): void
    {
        $remboursement->loadMissing('paiement.apprenant');

        if ($remboursement->paiement->apprenant->etablissement_id !== auth()->user()->etablissement_id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
