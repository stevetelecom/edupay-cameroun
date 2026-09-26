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

        $this->synchroniserEcheanciers($frais);

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie mise à jour.');
    }

    /**
     * Répartit montant_total sur nb_tranches_max échéances.
     * Augmente/ réduit automatiquement le nombre de tranches pour coller à nb_tranches_max.
     * 🔒 Ne touche pas aux FraisApprenant (déjà réglés) — seuls les montants échéanciers et
     *   les échéances futures sont alignés.
     */
    private function synchroniserEcheanciers(CategoriesFrais $frais): void
    {
        $nb = max(1, (int) $frais->nb_tranches_max);
        $montantTotal = (float) $frais->montant_total;

        $tranches = $frais->echeanciers()->orderBy('numero_tranche')->get();

        if ($nb == 1) {
            $tranches->each->delete();
            return;
        }

        $montantParTranche = round($montantTotal / $nb);

        for ($i = 1; $i <= $nb; $i++) {
            $existe = $tranches->firstWhere('numero_tranche', $i);

            if ($existe) {
                if ($existe->montant != $montantParTranche) {
                    $existe->update(['montant' => $montantParTranche]);
                }
            } else {
                Echeancier::create([
                    'categorie_frais_id' => $frais->id,
                    'numero_tranche'     => $i,
                    'libelle'            => 'Tranche ' . $i,
                    'montant'            => $montantParTranche,
                    'date_echeance'      => now()->addMonths($i)->toDateString(),
                ]);
            }
        }

        $tranches->where('numero_tranche', '>', $nb)->each->delete();
    }

    public function destroy(CategoriesFrais $frais)
    {
        $this->autoriser($frais);
        $nom = $frais->nom;

        $anneeActive = \App\Support\AnneeScolaire::active($frais->etablissement);
        $estAnneeActive = $frais->annee_scolaire === $anneeActive;

        $aDesPaiements = \App\Models\Paiement::whereHas(
            'fraisApprenant',
            fn ($q) => $q->where('categorie_frais_id', $frais->id)
        )->exists();

        // 🔒 Pour l'année ACTIVE uniquement : on protège les paiements réels en cours.
        // Pour une année PASSÉE, on autorise la suppression cascade complète — les
        // seeders/tests d'une ancienne rentrée n'ont plus besoin d'être conservés,
        // et garder chaque année indéfiniment saturerait la base pour rien.
        if ($aDesPaiements && $estAnneeActive) {
            return redirect()->route('etablissement.frais.index')
                ->with('error', 'Impossible de supprimer « ' . $nom . ' » : des paiements sont déjà enregistrés pour cette catégorie active. Vous pouvez la désactiver à la place.');
        }

        $this->supprimerCascade($frais);

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie « ' . $nom . ' » supprimée.');
    }

    /**
     * Supprime en cascade : paiements liés -> frais_apprenant -> échéanciers -> catégorie.
     * Utilisé pour la suppression forcée d'une catégorie d'année passée.
     */
    private function supprimerCascade(CategoriesFrais $frais): void
    {
        $fraisApprenantIds = $frais->fraisApprenants()->pluck('id');

        if ($fraisApprenantIds->isNotEmpty()) {
            \App\Models\Paiement::whereIn('frais_apprenant_id', $fraisApprenantIds)->delete();
        }

        $frais->fraisApprenants()->delete();
        $frais->echeanciers()->delete();
        $frais->delete();
    }

    /**
     * Purge TOUTES les catégories de frais (+ échéanciers, affectations et
     * paiements) des années scolaires différentes de l'année active de
     * l'établissement. Ne touche jamais à l'année en cours.
     */
    public function purgerAnneesPassees()
    {
        $etablissement = Auth::user()->etablissement;
        $anneeActive   = \App\Support\AnneeScolaire::active($etablissement);

        $categoriesAnciennes = CategoriesFrais::where('etablissement_id', $etablissement->id)
            ->where('annee_scolaire', '!=', $anneeActive)
            ->get();

        if ($categoriesAnciennes->isEmpty()) {
            return redirect()->route('etablissement.frais.index')
                ->with('info', __('etablissement.purge_aucune'));
        }

        $count = $categoriesAnciennes->count();

        foreach ($categoriesAnciennes as $ancienneCategorie) {
            $this->supprimerCascade($ancienneCategorie);
        }

        return redirect()->route('etablissement.frais.index')
            ->with('success', __('etablissement.purge_reussie', ['count' => $count]));
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

    /**
     * Duplique une catégorie de frais (+ ses échéances) vers une nouvelle année scolaire.
     * Ne copie PAS les FraisApprenant (chaque année repart avec des montants_paye à 0
     * et devra être ré-affectée aux apprenants via affecter()).
     */
    public function dupliquer(Request $request, CategoriesFrais $frais)
    {
        $this->autoriser($frais);

        $validated = $request->validate([
            'nouvelle_annee_scolaire' => 'required|string|max:20',
        ]);

        $nouvelleAnnee = $validated['nouvelle_annee_scolaire'];

        $dejaExistante = CategoriesFrais::where('etablissement_id', $frais->etablissement_id)
            ->where('nom', $frais->nom)
            ->where('annee_scolaire', $nouvelleAnnee)
            ->exists();

        if ($dejaExistante) {
            return redirect()->route('etablissement.frais.index')
                ->with('error', 'Une catégorie « ' . $frais->nom . ' » existe déjà pour l\'année ' . $nouvelleAnnee . '.');
        }

        $nouvelleCategorie = CategoriesFrais::create([
            'etablissement_id' => $frais->etablissement_id,
            'nom'              => $frais->nom,
            'description'      => $frais->description,
            'montant_total'    => $frais->montant_total,
            'nb_tranches_max'  => $frais->nb_tranches_max,
            'fractionnable'    => $frais->fractionnable,
            'annee_scolaire'   => $nouvelleAnnee,
            'actif'            => true,
        ]);

        foreach ($frais->echeanciers()->orderBy('numero_tranche')->get() as $echeance) {
            // Décale les dates d'échéance d'un an par rapport à l'originale
            $nouvelleDate = \Carbon\Carbon::parse($echeance->date_echeance)->addYear();

            Echeancier::create([
                'categorie_frais_id' => $nouvelleCategorie->id,
                'numero_tranche'     => $echeance->numero_tranche,
                'libelle'            => $echeance->libelle,
                'montant'            => $echeance->montant,
                'date_echeance'      => $nouvelleDate,
            ]);
        }

        return redirect()->route('etablissement.frais.index')
            ->with('success', 'Catégorie « ' . $frais->nom . ' » dupliquée vers l\'année ' . $nouvelleAnnee . '. Pensez à l\'affecter aux apprenants.');
    }

    private function autoriser(CategoriesFrais $frais)
    {
        if ($frais->etablissement_id != Auth::user()->etablissement->id) {
            abort(403, 'Accès non autorisé.');
        }
    }
}
