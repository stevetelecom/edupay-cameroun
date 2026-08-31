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
            'apprenant_id'       => 'nullable|exists:apprenants,id',
            'lien'               => 'required|in:parent,soi-meme',
        ], [
            'etablissement_nom.required_without' => 'Veuillez indiquer ou choisir un établissement.',
            'prenom_apprenant.required_unless'   => "Le prénom de l'apprenant est obligatoire.",
            'nom_apprenant.required_unless'      => "Le nom de l'apprenant est obligatoire.",
            'classe.required'                    => 'La classe est obligatoire.',
        ]);

        // Nettoyage défense en profondeur : retire toute balise HTML/JS des champs texte libres
        foreach (['prenom_apprenant', 'nom_apprenant', 'classe', 'etablissement_nom', 'matricule'] as $champ) {
            if (!empty($validated[$champ])) {
                $validated[$champ] = strip_tags(trim($validated[$champ]));
            }
        }

        $etablissement = $this->resoudreEtablissement($validated);

        if (!$etablissement) {
            return back()->withInput()
                ->withErrors(['etablissement_nom' => 'Établissement introuvable. Sélectionnez-le dans la liste.']);
        }

        $prenomApprenant = $validated['lien'] === 'soi-meme' ? $user->prenom : $validated['prenom_apprenant'];
        $nomApprenant    = $validated['lien'] === 'soi-meme' ? $user->nom    : $validated['nom_apprenant'];

        $apprenant = null;

        // Cas 1 : apprenant sélectionné directement depuis l'annuaire
        if (!empty($validated['apprenant_id'])) {
            $apprenant = Apprenant::where('id', $validated['apprenant_id'])
                ->where('etablissement_id', $etablissement->id)
                ->first();
        }

        // Cas 2 : recherche par matricule
        if (!$apprenant && !empty($validated['matricule'])) {
            $apprenant = Apprenant::where('matricule', $validated['matricule'])
                ->where('etablissement_id', $etablissement->id)
                ->first();
        }

        // Cas 3 : créer (pré-rattachement)
        if (!$apprenant) {
            // Génération auto du matricule si non fourni
            $matriculeAuto = $validated['matricule'] ?? null;
            if (empty($matriculeAuto)) {
                $prefix = $etablissement->code_etablissement
                    ? strtoupper(explode('-', $etablissement->code_etablissement)[0])
                    : strtoupper(substr(preg_replace('/[^A-Z]/i', '', $etablissement->nom), 0, 3));
                $dernierMatricule = Apprenant::where('etablissement_id', $etablissement->id)
                    ->whereNotNull('matricule')
                    ->orderByDesc('id')
                    ->value('matricule');
                $numero = 1;
                if ($dernierMatricule) {
                    preg_match('/(\d+)$/', $dernierMatricule, $matches);
                    $numero = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
                }
                $matriculeAuto = $prefix . '-' . date('Y') . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
            }

            $apprenant = Apprenant::create([
                'etablissement_id'          => $etablissement->id,
                'prenom'                    => $prenomApprenant,
                'nom'                       => $nomApprenant,
                'classe'                    => $validated['classe'],
                'matricule'                 => $matriculeAuto,
                'statut_paiement'           => 'impaye',
                'actif'                     => true,
                'source'                    => 'payeur',
                'valide_par_etablissement'  => false,
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

        // Nettoyage défense en profondeur : retire toute balise HTML/JS des champs texte libres
        foreach (['prenom', 'nom', 'classe', 'etablissement_nom', 'matricule'] as $champ) {
            if (!empty($validated[$champ])) {
                $validated[$champ] = strip_tags(trim($validated[$champ]));
            }
        }

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

    // ── F04 : API — Recherche apprenants par etablissement ──
    public function searchApprenants(Request $request): \Illuminate\Http\JsonResponse
    {
        $etablissementId = $request->input('etablissement_id');
        $search          = trim((string) $request->input('q', ''));

        if (!$etablissementId) {
            return response()->json([]);
        }

        $query = \App\Models\Apprenant::where('etablissement_id', $etablissementId)
            ->where('actif', true);

        // Affiche l'annuaire : quand rien n'est saisi, on liste TOUS les apprenants
        // actifs de l'établissement (comme pour la liste des établissements).
        if ($search !== '') {
            // 🔒 Sécurité (E-01) : pas de recherche libre qui expose tout l'annuaire
            // dès la première frappe. Le parent doit saisir au moins 3 caractères
            // (matricule exact, ou nom / prénom seul, ou nom+prénom) — sinon aucun
            // résultat. Empêche la fouille de données personnelles de mineurs.
            if (mb_strlen($search) < 3) {
                return response()->json([]);
            }

            $query->where(function ($query) use ($search) {
                $query->where('matricule', $search)
                    ->orWhere(function ($sub) use ($search) {
                        // Correspond à tout mot saisi sur le nom OU le prénom :
                        // - 1 mot (ex : « MEkontso ») → nom OU prénom contenant ce mot
                        // - plusieurs mots (ex : « MEkontso samuel ») → Tous les mots
                        //   doivent matcher sur le nom OU le prénom (nom+prénom précis).
                        $mots = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($mots as $mot) {
                            $sub->where(function ($s) use ($mot) {
                                $s->where('nom', 'like', "%{$mot}%")
                                  ->orWhere('prenom', 'like', "%{$mot}%");
                            });
                        }
                    });
            });
        }

        $apprenants = $query
            ->orderBy('nom')
            ->limit(10)
            ->get(['id', 'nom', 'prenom', 'classe', 'matricule']);

        \Illuminate\Support\Facades\Log::info('Recherche annuaire apprenants', [
            'user_id'          => Auth::id(),
            'etablissement_id' => $etablissementId,
            'resultats'        => $apprenants->count(),
        ]);

        return response()->json($apprenants);
    }

    // ── Helpers privés ──
    private function resoudreEtablissement(array $validated): ?Etablissement
    {
        // 🔒 Sécurité : dans les DEUX cas, l'établissement doit être 'actif'.
        // Sans ce check sur etablissement_id, un payeur pouvait se rattacher
        // à un établissement en_attente ou suspendu en connaissant/devinant son ID.
        if (!empty($validated['etablissement_id'])) {
            $et = Etablissement::where('id', $validated['etablissement_id'])
                ->where('statut', 'actif')
                ->first();
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
