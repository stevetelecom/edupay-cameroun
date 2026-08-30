<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Remboursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                'paiement'          => [
                    'id'        => $r->paiement->id,
                    'reference' => $r->paiement->reference,
                    'montant'   => (float) $r->paiement->montant,
                    'frais'     => $r->paiement->fraisApprenant?->categorieFrais?->nom,
                    'apprenant' => $r->paiement->apprenant
                        ? ($r->paiement->apprenant->prenom . ' ' . $r->paiement->apprenant->nom)
                        : null,
                ],
                'montant'           => (float) $r->montant,
                'motif'             => $r->motif,
                'statut'            => $r->statut,
                'motif_refus'       => $r->motif_refus,
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
    public function approuver(Request $request, Remboursement $remboursement): JsonResponse
    {
        $this->autoriser();
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

        return response()->json([
            'message' => 'Remboursement de '
                . number_format($remboursement->montant, 0, ',', ' ') . ' FCFA approuvé.',
            'data'    => [
                'id'     => $remboursement->id,
                'statut' => 'approuve',
            ],
        ]);
    }

    /**
     * Refuse une demande de remboursement.
     */
    public function refuser(Request $request, Remboursement $remboursement): JsonResponse
    {
        $this->autoriser();
        $this->autoriserTraitement();
        $this->autoriserAcces($remboursement);

        if ($remboursement->statut !== 'en_attente') {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        $validated = $request->validate([
            'motif_refus' => ['nullable', 'string', 'max:500'],
        ]);

        $remboursement->update([
            'statut'      => 'refuse',
            'traite_par'  => auth()->id(),
            'traite_le'   => now(),
            'motif_refus' => $validated['motif_refus'] ?? null,
        ]);

        return response()->json([
            'message' => 'Demande de remboursement refusée.',
            'data'    => [
                'id'     => $remboursement->id,
                'statut' => 'refuse',
            ],
        ]);
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
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
