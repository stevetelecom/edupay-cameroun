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

    // ─────────────────────────────────────────────────────────────
    // E11 — Import CSV apprenants
    // ─────────────────────────────────────────────────────────────

    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $file = public_path('templates/apprenants_template.csv');
        return response()->download($file, 'modele_import_apprenants.csv');
    }

    public function import(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'fichier_csv' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [
            'fichier_csv.required' => 'Veuillez sélectionner un fichier CSV.',
            'fichier_csv.mimes'    => 'Le fichier doit être au format CSV (.csv).',
            'fichier_csv.max'      => 'Le fichier ne doit pas dépasser 2 Mo.',
        ]);

        $etablissementId = Auth::user()->etablissement_id;

        if (! $etablissementId) {
            return back()->with('error', 'Aucun établissement associé à votre compte.');
        }

        $handle = fopen($request->file('fichier_csv')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'Impossible de lire le fichier CSV.');
        }

        // Sauter la ligne d'en-tête
        $header = fgetcsv($handle, 1000, ',');
        if ($header === false) {
            fclose($handle);
            return back()->with('error', 'Le fichier CSV est vide ou corrompu.');
        }

        $succes   = 0;
        $doublons = 0;
        $erreurs  = [];
        $ligne    = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $ligne++;
            $row = array_map('trim', $row);

            if (count($row) < 3) {
                $erreurs[] = "Ligne $ligne : données insuffisantes (nom, prénom, classe obligatoires).";
                continue;
            }

            $nom           = $row[0] ?? null;
            $prenom        = $row[1] ?? null;
            $classe        = $row[2] ?? null;
            $matricule     = ! empty($row[3]) ? strtoupper($row[3]) : null;
            $dateNaissance = ! empty($row[4]) ? $row[4] : null;
            $sexe          = ! empty($row[5]) ? strtoupper(substr(trim($row[5]), 0, 1)) : null;

            if (empty($nom) || empty($prenom) || empty($classe)) {
                $erreurs[] = "Ligne $ligne : nom, prénom et classe sont obligatoires.";
                continue;
            }

            // Validation date
            if ($dateNaissance) {
                $d = \DateTime::createFromFormat('Y-m-d', $dateNaissance);
                if (! $d || $d->format('Y-m-d') !== $dateNaissance) {
                    $erreurs[] = "Ligne $ligne : date_naissance invalide — format attendu AAAA-MM-JJ.";
                    continue;
                }
            }

            // Sexe : on ignore silencieusement si invalide
            if ($sexe && ! in_array($sexe, ['M', 'F'])) {
                $sexe = null;
            }

            // Unicité : matricule OU (nom + prénom + classe + établissement)
            if ($matricule) {
                $search = ['etablissement_id' => $etablissementId, 'matricule' => $matricule];
            } else {
                $search = [
                    'etablissement_id' => $etablissementId,
                    'nom'              => strtoupper($nom),
                    'prenom'           => $prenom,
                    'classe'           => $classe,
                ];
            }

            $donnees = [
                'etablissement_id' => $etablissementId,
                'nom'              => strtoupper($nom),
                'prenom'           => $prenom,
                'classe'           => $classe,
                'matricule'        => $matricule,
                'date_naissance'   => $dateNaissance,
                'sexe'             => $sexe,
                'statut_paiement'  => 'impaye',
                'actif'            => true,
            ];

            [, $created] = Apprenant::firstOrCreate($search, $donnees);

            $created ? $succes++ : $doublons++;
        }

        fclose($handle);

        $message = "$succes apprenant(s) importé(s) avec succès.";
        if ($doublons > 0) {
            $message .= " $doublons doublon(s) ignoré(s).";
        }
        if ($erreurs) {
            session()->flash('import_erreurs', $erreurs);
        }

        return redirect()
            ->route('etablissement.apprenants.index')
            ->with('success', $message);
    }

}
