<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FraisController extends Controller
{
    public function index()
    {
        $etablissement = Auth::user()->etablissement;

        $categories = CategoriesFrais::where('etablissement_id', $etablissement->id)
            ->with('echeanciers')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('etablissement.frais.index', compact('categories', 'etablissement'));
    }

    public function create()
    {
        return view('etablissement.frais.create');
    }

    public function store(Request $request)
    {
        $etablissement = Auth::user()->etablissement;

        $validated = $request->validate([
            'nom'              => 'required|string|max:150',
            'montant_total'    => 'required|numeric|min:0',
            'nb_tranches_max'  => 'required|integer|min:1|max:3',
            'fractionnable'    => 'nullable|boolean',
            'description'      => 'nullable|string|max:500',
            'annee_scolaire'   => 'required|string|max:20',
            'echeances'                       => 'required|array|min:1',
            'echeances.*.date_echeance'       => 'required|date',
            'echeances.*.montant'             => 'required|numeric|min:0',
            'echeances.*.libelle'             => 'nullable|string|max:100',
        ]);

        $categorie = CategoriesFrais::create([
            'etablissement_id' => $etablissement->id,
            'nom'              => $validated['nom'],
            'montant_total'    => $validated['montant_total'],
            'nb_tranches_max'  => $validated['nb_tranches_max'],
            'fractionnable'    => $request->boolean('fractionnable', true),
            'description'      => $validated['description'] ?? null,
            'annee_scolaire'   => $validated['annee_scolaire'],
            'actif'            => true,
        ]);

        foreach ($validated['echeances'] as $i => $ech) {
            Echeancier::create([
                'categorie_frais_id' => $categorie->id,
                'numero_tranche'     => $i + 1,
                'libelle'            => $ech['libelle'] ?? 'Tranche ' . ($i + 1),
                'montant'            => $ech['montant'],
                'date_echeance'      => $ech['date_echeance'],
            ]);
        }

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie « ' . $categorie->nom . ' » créée avec succès.');
    }

    public function edit(CategoriesFrais $frais)
    {
        $this->autoriser($frais);
        $echeances = $frais->echeanciers()->orderBy('numero_tranche')->get();
        return view('etablissement.frais.edit', compact('frais', 'echeances'));
    }

    public function update(Request $request, CategoriesFrais $frais)
    {
        $this->autoriser($frais);

        $validated = $request->validate([
            'nom'             => 'required|string|max:150',
            'montant_total'   => 'required|numeric|min:0',
            'nb_tranches_max' => 'required|integer|min:1|max:3',
            'fractionnable'   => 'nullable|boolean',
            'description'     => 'nullable|string|max:500',
            'annee_scolaire'  => 'required|string|max:20',
            'actif'           => 'nullable|boolean',
        ]);

        $frais->update([
            'nom'             => $validated['nom'],
            'montant_total'   => $validated['montant_total'],
            'nb_tranches_max' => $validated['nb_tranches_max'],
            'fractionnable'   => $request->boolean('fractionnable', true),
            'description'     => $validated['description'] ?? null,
            'annee_scolaire'  => $validated['annee_scolaire'],
            'actif'           => $request->boolean('actif', true),
        ]);

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(CategoriesFrais $frais)
    {
        $this->autoriser($frais);
        $nom = $frais->nom;
        $frais->echeanciers()->delete();
        $frais->delete();

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie « ' . $nom . ' » supprimée.');
    }

    private function autoriser(CategoriesFrais $frais)
    {
        if ($frais->etablissement_id != Auth::user()->etablissement->id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
