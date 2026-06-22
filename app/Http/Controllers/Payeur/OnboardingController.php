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
    // Affiche la page de rattachement (maquette s-onboarding)
    public function index(): View
    {
        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement']);

        return view('payeur.onboarding', compact('etablissements'));
    }

    // POST — Rattacher un apprenant à un établissement
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
            'etablissement_id.exists'            => 'Établissement introuvable, merci de le sélectionner dans la liste.',
            'prenom_apprenant.required_unless'   => 'Le prénom de l\'apprenant est obligatoire.',
            'nom_apprenant.required_unless'      => 'Le nom de l\'apprenant est obligatoire.',
            'classe.required'                    => 'La classe est obligatoire.',
            'lien.required'                      => 'Veuillez préciser le lien avec l\'apprenant.',
        ]);

        // Résolution de l'établissement : par ID caché (choix exact via datalist),
        // sinon par code établissement, sinon par nom exact (secours).
        $etablissement = null;

        if (!empty($validated['etablissement_id'])) {
            $etablissement = Etablissement::find($validated['etablissement_id']);
        }

        if (!$etablissement && !empty($validated['code_etablissement'])) {
            $etablissement = Etablissement::where('code_etablissement', $validated['code_etablissement'])->first();
        }

        if (!$etablissement && !empty($validated['etablissement_nom'])) {
            $etablissement = Etablissement::where('nom', $validated['etablissement_nom'])
                ->where('statut', 'actif')
                ->first();
        }

        if (!$etablissement) {
            return back()
                ->withInput()
                ->withErrors(['etablissement_nom' => 'Établissement introuvable. Merci de le sélectionner dans la liste proposée.']);
        }

        // Profil solo (élève / étudiant) : prénom/nom = ceux du compte lui-même
        $prenomApprenant = $validated['lien'] === 'soi-meme' ? $user->prenom : $validated['prenom_apprenant'];
        $nomApprenant     = $validated['lien'] === 'soi-meme' ? $user->nom    : $validated['nom_apprenant'];

        // Chercher si l'apprenant existe déjà par matricule
        $apprenant = null;
        if (!empty($validated['matricule'])) {
            $apprenant = Apprenant::where('matricule', $validated['matricule'])
                ->where('etablissement_id', $etablissement->id)
                ->first();
        }

        // Sinon le créer (pré-rattachement — validé ensuite par l'école)
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

        // Rattacher au user (pivot user_apprenant)
        $user->apprenants()->syncWithoutDetaching([
            $apprenant->id => ['lien' => $validated['lien']]
        ]);

        // Si élève/étudiant solo → mettre à jour etablissement_id sur le user
        if ($validated['lien'] === 'soi-meme') {
            $user->update(['etablissement_id' => $etablissement->id]);
        }

        return redirect()->route('payeur.dashboard')
            ->with('success', 'Rattachement effectué avec succès !');
    }
}
