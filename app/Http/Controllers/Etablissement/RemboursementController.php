<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Remboursement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RemboursementController extends Controller
{
    public function index(): View
    {
        $etablissementId = Auth::user()->etablissement_id;

        $remboursements = Remboursement::with(['paiement.apprenant', 'paiement.fraisApprenant.categorieFrais', 'initiateur', 'traiteur'])
            ->whereHas('paiement.apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->latest()
            ->get();

        $paiementsRemboursables = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('statut', 'valide')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->whereDoesntHave('remboursements', fn ($q) => $q->whereIn('statut', ['en_attente', 'approuve']))
            ->latest('date_paiement')
            ->get();

        return view('etablissement.remboursements.index', [
            'remboursements'         => $remboursements,
            'paiementsRemboursables' => $paiementsRemboursables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'montant'     => 'required|numeric|min:1',
            'motif'       => 'required|string|max:255',
        ], [
            'paiement_id.required' => 'Veuillez sélectionner un paiement.',
            'montant.required'     => 'Veuillez indiquer le montant à rembourser.',
            'montant.min'          => 'Le montant doit être supérieur à 0.',
            'motif.required'       => 'Veuillez préciser le motif du remboursement.',
        ]);

        $paiement = Paiement::with('apprenant')->findOrFail($validated['paiement_id']);

        abort_unless(
            $paiement->apprenant->etablissement_id === Auth::user()->etablissement_id,
            403,
            'Ce paiement n\'appartient pas à votre établissement.'
        );

        if ($validated['montant'] > $paiement->montant) {
            return back()->withInput()->withErrors(['montant' => 'Le montant ne peut pas dépasser celui du paiement (' . number_format($paiement->montant, 0, ',', ' ') . ' FCFA).']);
        }

        Remboursement::create([
            'paiement_id' => $paiement->id,
            'montant'     => $validated['montant'],
            'motif'       => $validated['motif'],
            'statut'      => 'en_attente',
            'initie_par'  => Auth::id(),
        ]);

        return redirect()->route('etablissement.remboursements.index')
            ->with('success', 'Demande de remboursement créée avec succès.');
    }

    public function approuver(Remboursement $remboursement): RedirectResponse
    {
        $this->autoriserTraitement();
        $this->autoriserAcces($remboursement);

        if ($remboursement->statut !== 'en_attente') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $remboursement->update([
            'statut'     => 'approuve',
            'traite_par' => Auth::id(),
            'traite_le'  => now(),
        ]);

        if ($remboursement->montant >= $remboursement->paiement->montant) {
            $remboursement->paiement->update(['statut' => 'rembourse']);
        }

        return redirect()->route('etablissement.remboursements.index')
            ->with('success', 'Remboursement de ' . number_format($remboursement->montant, 0, ',', ' ') . ' FCFA approuvé.');
    }

    public function refuser(Request $request, Remboursement $remboursement): RedirectResponse
    {
        $this->autoriserTraitement();
        $this->autoriserAcces($remboursement);

        if ($remboursement->statut !== 'en_attente') {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $validated = $request->validate([
            'motif_refus' => 'nullable|string|max:500',
        ]);

        $remboursement->update([
            'statut'      => 'refuse',
            'traite_par'  => Auth::id(),
            'traite_le'   => now(),
            'motif_refus' => $validated['motif_refus'] ?? null,
        ]);

        return redirect()->route('etablissement.remboursements.index')
            ->with('info', 'Demande de remboursement refusée.');
    }

    private function autoriserTraitement(): void
    {
        abort_unless(
            Auth::user()->hasRole('directeur') || Auth::user()->hasRole('comptable'),
            403,
            'Seuls le directeur et le comptable peuvent traiter les remboursements.'
        );
    }

    private function autoriserAcces(Remboursement $remboursement): void
    {
        $remboursement->loadMissing('paiement.apprenant');

        if ($remboursement->paiement->apprenant->etablissement_id !== Auth::user()->etablissement_id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
