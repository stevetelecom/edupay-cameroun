<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        $reclamations = Reclamation::with('paiement.fraisApprenant.categorieFrais')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $paiements = Paiement::with(['fraisApprenant.categorieFrais', 'apprenant'])
            ->where('user_id', $user->id)
            ->where('statut', 'valide')
            ->latest('date_paiement')
            ->get();

        return view('payeur.reclamations', [
            'reclamations' => $reclamations,
            'paiements'    => $paiements,
            'pageTitle'    => 'Réclamations — EduPay',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'paiement_id' => 'nullable|exists:paiements,id',
            'sujet'       => 'required|string|max:150',
            'description' => 'required|string|max:2000',
        ], [
            'sujet.required'       => 'Veuillez indiquer un objet pour votre réclamation.',
            'description.required' => 'Veuillez décrire le problème rencontré.',
        ]);

        if (!empty($validated['paiement_id'])) {
            $appartientAuUser = Paiement::where('id', $validated['paiement_id'])
                ->where('user_id', Auth::id())
                ->exists();

            if (!$appartientAuUser) {
                return back()->withInput()->withErrors(['paiement_id' => 'Transaction invalide.']);
            }
        }

        Reclamation::create([
            'user_id'     => Auth::id(),
            'paiement_id' => $validated['paiement_id'] ?? null,
            'sujet'       => $validated['sujet'],
            'description' => $validated['description'],
            'statut'      => 'ouvert',
        ]);

        return redirect()->route('payeur.reclamations.index')
            ->with('success', 'Votre réclamation a été envoyée. Notre équipe vous répondra sous peu.');
    }
}
