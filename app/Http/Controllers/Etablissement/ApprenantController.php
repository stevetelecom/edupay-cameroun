<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApprenantController extends Controller
{
    public function index(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;

        $apprenants = Apprenant::where('etablissement_id', $etablissementId)
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('nom', 'like', "%{$term}%")
                        ->orWhere('prenom', 'like', "%{$term}%")
                        ->orWhere('matricule', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('classe'), fn ($q) => $q->where('classe', $request->classe))
            ->when($request->filled('statut_paiement'), fn ($q) => $q->where('statut_paiement', $request->statut_paiement))
            ->orderBy('nom')
            ->paginate(20)
            ->withQueryString();

        $classes = Apprenant::where('etablissement_id', $etablissementId)
            ->distinct()
            ->orderBy('classe')
            ->pluck('classe');

        return view('etablissement.apprenants.index', compact('apprenants', 'classes'));
    }

    public function create()
    {
        return view('etablissement.apprenants.create');
    }

    public function store(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;

        $validated = $request->validate([
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['required', 'string', 'max:100'],
            'classe'         => ['required', 'string', 'max:50'],
            'matricule'      => ['nullable', 'string', 'max:50', 'unique:apprenants,matricule'],
            'date_naissance' => ['nullable', 'date'],
            'sexe'           => ['nullable', Rule::in(['M', 'F'])],
            'actif'          => ['nullable', 'boolean'],
        ]);

        $validated['etablissement_id'] = $etablissementId;
        $validated['actif']            = $request->boolean('actif', true);
        $validated['statut_paiement']  = 'impaye';

        $apprenant = Apprenant::create($validated);

        return redirect()
            ->route('etablissement.apprenants.show', $apprenant)
            ->with('success', 'Apprenant ' . $apprenant->nom . ' ' . $apprenant->prenom . ' ajouté avec succès.');
    }

    public function show(Apprenant $apprenant)
    {
        $this->autoriserAcces($apprenant);

        $apprenant->load(['frais.categorieFrais', 'parents']);

        return view('etablissement.apprenants.show', compact('apprenant'));
    }

    public function edit(Apprenant $apprenant)
    {
        $this->autoriserAcces($apprenant);

        return view('etablissement.apprenants.edit', compact('apprenant'));
    }

    public function update(Request $request, Apprenant $apprenant)
    {
        $this->autoriserAcces($apprenant);

        $validated = $request->validate([
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['required', 'string', 'max:100'],
            'classe'         => ['required', 'string', 'max:50'],
            'matricule'      => ['nullable', 'string', 'max:50', Rule::unique('apprenants', 'matricule')->ignore($apprenant->id)],
            'date_naissance' => ['nullable', 'date'],
            'sexe'           => ['nullable', Rule::in(['M', 'F'])],
            'actif'          => ['nullable', 'boolean'],
        ]);

        $validated['actif'] = $request->boolean('actif', false);

        $apprenant->update($validated);

        return redirect()
            ->route('etablissement.apprenants.show', $apprenant)
            ->with('success', 'Informations mises à jour avec succès.');
    }

    public function destroy(Apprenant $apprenant)
    {
        $this->autoriserAcces($apprenant);

        $apprenant->delete();

        return redirect()
            ->route('etablissement.apprenants.index')
            ->with('success', 'Apprenant supprimé avec succès.');
    }

    /**
     * Empêche un établissement d'accéder aux apprenants d'un autre établissement.
     */
    private function autoriserAcces(Apprenant $apprenant): void
    {
        if ($apprenant->etablissement_id !== Auth::user()->etablissement_id) {
            abort(403, 'Accès non autorisé à cet apprenant.');
        }
    }
}
