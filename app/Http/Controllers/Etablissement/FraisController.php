<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
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

        $classes = Apprenant::where('etablissement_id', $etablissement->id)
            ->distinct()
            ->orderBy('classe')
            ->pluck('classe');

        return view('etablissement.frais.index', compact('categories', 'etablissement', 'classes'));
    }

    public function affecter(Request $request, CategoriesFrais $frais)
    {
        $this->autoriser($frais);

        $validated = $request->validate([
            'classe' => 'nullable|string|max:50',
        ]);

        $etablissement = Auth::user()->etablissement;

        $apprenants = Apprenant::where('etablissement_id', $etablissement->id)
            ->where('actif', true)
            ->when($validated['classe'] ?? null, fn ($q, $classe) => $q->where('classe', $classe))
            ->get();

        if ($apprenants->isEmpty()) {
            return redirect()->route('etablissement.frais.index')
                ->with('error', 'Aucun apprenant actif trouvé pour cette classe.');
        }

        $ajoutes = 0;

        foreach ($apprenants as $apprenant) {
            $existe = FraisApprenant::where('apprenant_id', $apprenant->id)
                ->where('categorie_frais_id', $frais->id)
                ->where('annee_scolaire', $frais->annee_scolaire)
                ->exists();

            if ($existe) {
                continue;
            }

            FraisApprenant::create([
                'apprenant_id' => $apprenant->id,
                'categorie_frais_id' => $frais->id,
                'montant_total' => $frais->montant_total,
                'montant_paye' => 0,
                'statut' => 'impaye',
                'annee_scolaire' => $frais->annee_scolaire,
            ]);

            $ajoutes++;
        }

        $message = $ajoutes > 0
            ? 'Frais affectés à ' . $ajoutes . ' apprenant(s).' : 'Tous les apprenants de cette sélection ont déjà cette catégorie de frais.';

        return redirect()->route('etablissement.frais.index')
            ->with('success', $message);
    }

    /**
     * Désaffecte une catégorie de frais d'un apprenant (retire le FraisApprenant).
     * 🔒 Permission : on ne peut désaffecter que si AUCUN paiement n'est enregistré
     * pour cette catégorie — sinon on briserait l'historique de règlement.
     */
    public function desaffecter(Apprenant $apprenant, FraisApprenant $fraisApprenant)
    {
        $this->autoriser($fraisApprenant->categorieFrais);
        $this->autoriserApprenant($apprenant);

        if ($fraisApprenant->apprenant_id !== $apprenant->id) {
            return redirect()->route('etablissement.apprenants.show', $apprenant)
                ->with('error', 'Cette affectation ne correspond pas à cet apprenant.');
        }

        if ($fraisApprenant->paiements()->exists()) {
            return redirect()->route('etablissement.apprenants.show', $apprenant)
                ->with('error', 'Impossible de désaffecter « ' . ($fraisApprenant->categorieFrais->nom ?? '')
                    . ' » : des paiements sont déjà enregistrés. Contactez le support pour un remboursement.');
        }

        $nom = $fraisApprenant->categorieFrais->nom ?? 'la catégorie';
        $fraisApprenant->delete();

        return redirect()->route('etablissement.apprenants.show', $apprenant)
            ->with('success', 'Catégorie « ' . $nom . ' » désaffectée de ' . $apprenant->prenom . ' ' . $apprenant->nom . '.');
    }

    private function autoriserApprenant(Apprenant $apprenant): void
    {
        if ($apprenant->etablissement_id !== (Auth::user()->etablissement->id ?? null)) {
            abort(403, 'Accès non autorisé à cet apprenant.');
        }
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
            'echeances'                        => 'sometimes|array',
            'echeances.*.date_echeance'       => 'required_with:echeances|date',
            'echeances.*.montant'             => 'required_with:echeances|numeric|min:0',
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

        if (!empty($validated['echeances'])) {
            foreach ($validated['echeances'] as $i => $ech) {
                Echeancier::create([
                    'categorie_frais_id' => $categorie->id,
                    'numero_tranche'     => $i + 1,
                    'libelle'            => $ech['libelle'] ?? 'Tranche ' . ($i + 1),
                    'montant'            => $ech['montant'],
                    'date_echeance'      => $ech['date_echeance'],
                ]);
            }
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

    public function storeEcheancier(Request $request, CategoriesFrais $frais)
    {
        $this->autoriser($frais);

        $validated = $request->validate([
            'numero_tranche' => 'required|integer|min:1',
            'montant'        => 'required|numeric|min:0',
            'date_echeance'  => 'required|date',
            'libelle'        => 'nullable|string|max:100',
        ]);

        Echeancier::create([
            'categorie_frais_id' => $frais->id,
            'numero_tranche'     => $validated['numero_tranche'],
            'libelle'            => $validated['libelle'] ?? 'Tranche ' . $validated['numero_tranche'],
            'montant'            => $validated['montant'],
            'date_echeance'      => $validated['date_echeance'],
        ]);

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Tranche ajoutée à la catégorie « ' . $frais->nom . ' ».');
    }

    public function updateEcheancier(Request $request, CategoriesFrais $frais, Echeancier $echeancier)
    {
        $this->autoriser($frais);

        if ($echeancier->categorie_frais_id != $frais->id) {
            abort(403, 'Échéance non liée à cette catégorie.');
        }

        $validated = $request->validate([
            'numero_tranche' => 'required|integer|min:1',
            'montant'        => 'required|numeric|min:0',
            'date_echeance'  => 'required|date',
            'libelle'        => 'nullable|string|max:100',
        ]);

        $echeancier->update([
            'numero_tranche' => $validated['numero_tranche'],
            'montant'        => $validated['montant'],
            'date_echeance'  => $validated['date_echeance'],
            'libelle'        => $validated['libelle'] ?? $echeancier->libelle,
        ]);

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Tranche mise à jour.');
    }

    public function destroyEcheancier(CategoriesFrais $frais, Echeancier $echeancier)
    {
        $this->autoriser($frais);

        if ($echeancier->categorie_frais_id != $frais->id) {
            abort(403, 'Échéance non liée à cette catégorie.');
        }

        $echeancier->delete();

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Tranche supprimée.');
    }

    private function autoriser(CategoriesFrais $frais)
    {
        if ($frais->etablissement_id != Auth::user()->etablissement->id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
