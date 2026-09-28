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

        // Garde-fous identiques a ceux de l'API
        // (Api/Etablissement/RemboursementController.php:102-125), absents
        // d'ici. Sans eux, un caissier pouvait poster autant de demandes que
        // de fois sur le meme paiement, chacune approuvee ensuite par un
        // directeur : l'argent sortait N fois pour un seul encaissement.
        if ($paiement->statut !== 'valide') {
            return back()->withInput()->withErrors([
                'paiement_id' => 'Seul un paiement validé peut être remboursé.',
            ]);
        }

        $dejaEnCours = Remboursement::where('paiement_id', $paiement->id)
            ->whereIn('statut', ['en_attente', 'approuve'])
            ->exists();

        if ($dejaEnCours) {
            return back()->withInput()->withErrors([
                'paiement_id' => 'Un remboursement est déjà en cours pour ce paiement.',
            ]);
        }

        // Plafond sur le CUMUL approuve, pas seulement sur la demande
        // courante : deux demandes partielles acceptees et approuvees
        //Independamment totalisaient plus que le paiement.
        $dejaRembourse = (float) Remboursement::where('paiement_id', $paiement->id)
            ->where('statut', 'approuve')
            ->sum('montant');

        $plafond = (float) $paiement->montant - $dejaRembourse;

        if ((float) $validated['montant'] > $plafond) {
            return back()->withInput()->withErrors([
                'montant' => $dejaRembourse > 0
                    ? 'Le cumulative ne peut pas dépasser ' . number_format($plafond, 0, ',', ' ') . ' FCFA (déjà ' . number_format($dejaRembourse, 0, ',', ' ') . ' FCFA remboursés sur ce paiement).'
                    : 'Le montant ne peut pas dépasser celui du paiement (' . number_format($paiement->montant, 0, ',', ' ') . ' FCFA).',
            ]);
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

        $paiement = $remboursement->paiement;

        // Revalidation a l'approbation. Le controle de store() ne suffit pas :
        // entre la demande et l'approbation, le paiement a pu etre rembourse,
        // annule, ou avoir deja fait l'objet d'un autre remboursement approuve.
        if ($paiement->statut !== 'valide') {
            return back()->with('error', 'Ce paiement n\'est plus validé, le remboursement ne peut pas être approuvé.');
        }

        $cumule = (float) Remboursement::where('paiement_id', $paiement->id)
            ->where('statut', 'approuve')
            ->sum('montant') + (float) $remboursement->montant;

        if ($cumule > (float) $paiement->montant) {
            return back()->with('error', 'Le cumulative des remboursements approuvé (' . number_format($cumule, 0, ',', ' ') . ' FCFA) dépasse le paiement (' . number_format($paiement->montant, 0, ',', ' ') . ' FCFA).');
        }

        // Avertissement claw-back, comme l'API : rembourser apres reversement
        // fait sortir de l'etablissement une somme qu'il a deja recue.
        $commission = $paiement->commission()->first();
        $dejaReverse = $commission?->reversementEffectue() === true;

        if ($dejaReverse) {
            \Illuminate\Support\Facades\Log::critical('Remboursement approuve (web) alors que le reversement etait deja effectue', [
                'remboursement_id'  => $remboursement->id,
                'paiement_id'       => $paiement->id,
                'commission_id'     => $commission->id,
                'montant_rembourse' => (float) $remboursement->montant,
                'reference'         => $commission->reference_reversement,
            ]);
        }

        $remboursement->update([
            'statut'     => 'approuve',
            'traite_par' => Auth::id(),
            'traite_le'  => now(),
        ]);

        if ($cumule >= (float) $paiement->montant) {
            $paiement->update(['statut' => 'rembourse']);
        }

        $message = 'Remboursement de ' . number_format($remboursement->montant, 0, ',', ' ') . ' FCFA approuvé.';

        if ($dejaReverse) {
            $message .= ' ATTENTION : cet argent a déjà été reversé à votre établissement (référence '
                . ($commission->reference_reversement ?? 'inconnue') . '), il faudra le récupérer.';
        }

        return redirect()->route('etablissement.remboursements.index')
            ->with('success', $message);
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
