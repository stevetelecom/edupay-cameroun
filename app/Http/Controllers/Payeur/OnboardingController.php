<?php
namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    // ── F04 : Affiche la page de rattachement initial ──
    public function index(): View
    {
        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement']);

        return view('payeur.onboarding', compact('etablissements'));
    }

    // ── F04 : POST — Rattacher un apprenant ──
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'etablissement_id'   => 'nullable|exists:etablissements,id',
            'etablissement_nom'  => 'required_without:etablissement_id|nullable|string|max:150',
            'code_etablissement' => 'nullable|string|max:50',
            'prenom_apprenant'   => 'required_unless:lien,soi-meme|nullable|string|max:100',
            'nom_apprenant'      => 'required_unless:lien,soi-meme|nullable|string|max:100',
            'classe'             => 'required|string|max:50',
            'matricule'          => 'nullable|string|max:50',
            'lien'               => 'required|in:parent,soi-meme',
        ], [
            'etablissement_nom.required_without' => 'Veuillez indiquer ou choisir un établissement.',
            'prenom_apprenant.required_unless'   => "Le prénom de l'apprenant est obligatoire.",
            'nom_apprenant.required_unless'      => "Le nom de l'apprenant est obligatoire.",
            'classe.required'                    => 'La classe est obligatoire.',
        ]);

        $etablissement = $this->resoudreEtablissement($validated);

        if (!$etablissement) {
            return back()->withInput()
                ->withErrors(['etablissement_nom' => 'Établissement introuvable. Sélectionnez-le dans la liste.']);
        }

        $prenomApprenant = $validated['lien'] === 'soi-meme' ? $user->prenom : $validated['prenom_apprenant'];
        $nomApprenant    = $validated['lien'] === 'soi-meme' ? $user->nom    : $validated['nom_apprenant'];

        $apprenant = null;
        if (!empty($validated['matricule'])) {
            $apprenant = Apprenant::where('matricule', $validated['matricule'])
                ->where('etablissement_id', $etablissement->id)
                ->first();
        }

        if (!$apprenant) {
            $apprenant = Apprenant::create([
                'etablissement_id' => $etablissement->id,
                'prenom'           => $prenomApprenant,
                'nom'              => $nomApprenant,
                'classe'           => $validated['classe'],
                'matricule'        => $validated['matricule'] ?? null,
                'statut_paiement'  => 'impaye',
                'actif'            => true,
            ]);
        }

        $user->apprenants()->syncWithoutDetaching([
            $apprenant->id => ['lien' => $validated['lien']]
        ]);

        if ($validated['lien'] === 'soi-meme') {
            $user->update(['etablissement_id' => $etablissement->id]);
        }

        return redirect()->route('payeur.dashboard')
            ->with('success', 'Rattachement effectué avec succès !');
    }

    // ── F04 : GET — Formulaire de modification d'un rattachement ──
    public function editApprenant(Apprenant $apprenant): View
    {
        $this->autoriserAccesApprenant($apprenant);

        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement']);

        $lien = Auth::user()->apprenants()
            ->where('apprenant_id', $apprenant->id)
            ->first()?->pivot->lien ?? 'parent';

        return view('payeur.apprenant_edit', compact('apprenant', 'etablissements', 'lien'));
    }

    // ── F04 : PUT — Enregistrer les modifications ──
    public function updateApprenant(Request $request, Apprenant $apprenant): RedirectResponse
    {
        $this->autoriserAccesApprenant($apprenant);

        $validated = $request->validate([
            'etablissement_id'  => 'nullable|exists:etablissements,id',
            'etablissement_nom' => 'required_without:etablissement_id|nullable|string|max:150',
            'classe'            => 'required|string|max:50',
            'matricule'         => 'nullable|string|max:50',
            'prenom'            => 'required|string|max:100',
            'nom'               => 'required|string|max:100',
        ]);

        $etablissement = $this->resoudreEtablissement($validated);

        if (!$etablissement) {
            return back()->withInput()
                ->withErrors(['etablissement_nom' => 'Établissement introuvable.']);
        }

        // Bloquer le changement d'établissement si des paiements existent
        $aPaiements = $apprenant->paiements()->exists();
        if ($aPaiements && $apprenant->etablissement_id !== $etablissement->id) {
            return back()->withInput()
                ->withErrors(['etablissement_nom' => "Impossible de changer l'établissement : des paiements sont déjà enregistrés pour cet apprenant."]);
        }

        $apprenant->update([
            'etablissement_id' => $etablissement->id,
            'prenom'           => $validated['prenom'],
            'nom'              => $validated['nom'],
            'classe'           => $validated['classe'],
            'matricule'        => $validated['matricule'] ?? $apprenant->matricule,
        ]);

        return redirect()->route('payeur.dashboard')
            ->with('success', 'Informations de ' . $apprenant->prenom . ' mises à jour.');
    }

    // ── F04 : DELETE — Détacher un apprenant (sans supprimer si paiements) ──
    public function detachApprenant(Apprenant $apprenant): RedirectResponse
    {
        $this->autoriserAccesApprenant($apprenant);

        if ($apprenant->paiements()->exists()) {
            return back()->with('error',
                'Impossible de retirer ' . $apprenant->prenom . ' : des paiements sont enregistrés. Contactez votre établissement.');
        }

        Auth::user()->apprenants()->detach($apprenant->id);

        return redirect()->route('payeur.dashboard')
            ->with('success', $apprenant->prenom . ' a été retiré de votre compte.');
    }

    // ── Helpers privés ──
    private function resoudreEtablissement(array $validated): ?Etablissement
    {
        if (!empty($validated['etablissement_id'])) {
            $et = Etablissement::find($validated['etablissement_id']);
            if ($et) return $et;
        }
        if (!empty($validated['etablissement_nom'])) {
            $et = Etablissement::where('nom', $validated['etablissement_nom'])
                ->where('statut', 'actif')->first();
            if ($et) return $et;
        }
        return null;
    }

    private function autoriserAccesApprenant(Apprenant $apprenant): void
    {
        $rattache = Auth::user()->apprenants()
            ->where('apprenant_id', $apprenant->id)
            ->exists();
        abort_unless($rattache, 403, 'Cet apprenant ne vous est pas rattaché.');
    }
}
