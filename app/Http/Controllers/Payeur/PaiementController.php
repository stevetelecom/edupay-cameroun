<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaiementController extends Controller
{
    public function show(FraisApprenant $fraisApprenant)
    {
        $this->autoriserAcces($fraisApprenant);

        $fraisApprenant->load(['apprenant.etablissement', 'categorieFrais']);

        return view('payeur.paiement', compact('fraisApprenant'));
    }

    public function initier(Request $request, FraisApprenant $fraisApprenant)
    {
        $this->autoriserAcces($fraisApprenant);

        $validated = $request->validate([
            'type_paiement'      => ['required', Rule::in(['integral', 'tranche'])],
            'mode_paiement'      => ['required', Rule::in(['mtn_momo', 'orange_money', 'carte'])],
            'telephone_paiement' => ['required_unless:mode_paiement,carte', 'nullable', 'string', 'max:20'],
        ]);

        $resteAPayer = $fraisApprenant->montant_total - $fraisApprenant->montant_paye;

        $montant = $validated['type_paiement'] === 'tranche'
            ? round($resteAPayer / ($fraisApprenant->categorieFrais->nb_tranches_max ?? 2))
            : $resteAPayer;

        $paiement = Paiement::create([
            'user_id'             => Auth::id(),
            'apprenant_id'        => $fraisApprenant->apprenant_id,
            'frais_apprenant_id'  => $fraisApprenant->id,
            'montant'             => $montant,
            'mode_paiement'       => $validated['mode_paiement'],
            'type_paiement'       => $validated['type_paiement'],
            'statut'              => 'en_attente',
            'telephone_paiement'  => $validated['telephone_paiement'] ?? null,
            'date_paiement'       => now(),
        ]);

        // TODO Sprint 4 — appel réel MtnMomoService / OrangeMoneyService / CinetPayService
        // selon $paiement->mode_paiement, puis mise à jour du statut via callback/webhook.

        return redirect()
            ->route('payeur.dashboard')
            ->with('info', 'Paiement initié — Réf. ' . $paiement->reference . '. Confirmez la transaction sur votre téléphone.');
    }

    public function historique()
    {
        $paiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', Auth::id())
            ->latest('date_paiement')
            ->paginate(15);

        return view('payeur.historique', compact('paiements'));
    }

    /**
     * Empêche un parent de payer/consulter les frais d'un enfant qui n'est pas le sien.
     */
    private function autoriserAcces(FraisApprenant $fraisApprenant): void
    {
        $estParentDeLApprenant = Auth::user()
            ->apprenants()
            ->where('apprenants.id', $fraisApprenant->apprenant_id)
            ->exists();

        if (! $estParentDeLApprenant) {
            abort(403, 'Vous n\'êtes pas autorisé à accéder à ce dossier de paiement.');
        }
    }
}
