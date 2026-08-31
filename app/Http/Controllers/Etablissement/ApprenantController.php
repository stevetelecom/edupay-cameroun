<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use App\Models\FraisApprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ApprenantController extends Controller
{
    public function valider(Apprenant $apprenant)
    {
        $etablissementId = Auth::user()->etablissement_id;
        abort_unless($apprenant->etablissement_id === $etablissementId, 403);

        $apprenant->update(['valide_par_etablissement' => true]);

        $nomComplet = $apprenant->prenom . ' ' . $apprenant->nom;

        // Notifier chaque payeur rattaché
        foreach ($apprenant->parents()->get() as $payeur) {
            \App\Models\NotificationPayeur::create([
                'user_id' => $payeur->id,
                'titre'   => 'Rattachement validé',
                'message' => 'Votre demande de rattachement pour ' . $nomComplet . ' a été validée par l\'établissement. Vous pouvez désormais consulter et régler ses frais de scolarité.',
                'type'    => 'success',
            ]);
        }

        return back()->with('success', $nomComplet . ' a été validé(e) avec succès. Le payeur a été notifié.');
    }

    public function rejeter(Apprenant $apprenant)
    {
        $etablissementId = Auth::user()->etablissement_id;
        abort_unless($apprenant->etablissement_id === $etablissementId, 403);

        if ($apprenant->source !== 'payeur' || $apprenant->valide_par_etablissement) {
            abort(403, 'Cet apprenant ne peut pas être rejeté.');
        }

        $nomComplet = $apprenant->prenom . ' ' . $apprenant->nom;

        // Récupérer les payeurs rattachés avant suppression pour les notifier
        $payeurs = $apprenant->parents()->get();

        // Détacher le pivot user_apprenant (le soft delete n'appelle pas le cascadeOnDelete)
        $apprenant->parents()->detach();

        $apprenant->delete();

        // Notifier chaque payeur concerné
        foreach ($payeurs as $payeur) {
            \App\Models\NotificationPayeur::create([
                'user_id' => $payeur->id,
                'titre'   => 'Rattachement refusé',
                'message' => 'Votre demande de rattachement pour ' . $nomComplet . ' a été refusée par l\'établissement. '
                    . 'Vérifiez les informations saisies ou recherchez l\'apprenant dans l\'annuaire officiel de l\'établissement.',
                'type'    => 'error',
            ]);
        }

        return back()->with('success', 'La demande de rattachement de ' . $nomComplet . ' a été rejetée. Le payeur a été notifié.');
    }

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

        // Charger les catégories et échéanciers pour le modal
        $categories = CategoriesFrais::where('etablissement_id', $etablissementId)
            ->where('actif', true)
            ->with(['echeanciers' => fn($q) => $q->orderBy('numero_tranche')])
            ->orderBy('nom')
            ->get();

        $echeanciers = Echeancier::whereHas('categorieFrais', fn($q) => $q->where('etablissement_id', $etablissementId))
            ->with('categorieFrais')
            ->orderBy('date_echeance')
            ->get();

        return view('etablissement.apprenants.index', compact('apprenants', 'classes', 'categories', 'echeanciers'));
    }

    /**
     * Endpoint AJAX pour DataTables — retourne JSON pagine cote serveur
     */
    public function datatable(Request $request)
    {
        $etablissementId = Auth::user()->etablissement_id;

        $draw   = $request->integer('draw', 1);
        $start  = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $search = $request->input('search.value', '');
        $classe = $request->input('classe', '');
        $statutPaiement = $request->input('statut_paiement', '');

        $cols = ['matricule', 'nom', 'classe', 'sexe', 'statut_paiement', 'actif'];
        $orderCol = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');

        $query = Apprenant::where('etablissement_id', $etablissementId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        if ($classe)         $query->where('classe', $classe);
        if ($statutPaiement) $query->where('statut_paiement', $statutPaiement);

        $total    = Apprenant::where('etablissement_id', $etablissementId)->count();
        $filtered = $query->count();

        $col = $cols[$orderCol] ?? 'nom';
        $query = $query->orderBy($col, $orderDir);
        if ($length < 1) {
            $length = $filtered > 0 ? $filtered : 1;
        }
        $apprenants = $query->skip($start)->take($length)->get();

        $rows = $apprenants->map(function ($a) {
            $statutBadge = match($a->statut_paiement) {
                'regle'   => '<span class="ep-badge ep-badge-green">Regle</span>',
                'partiel' => '<span class="ep-badge ep-badge-yellow">Partiel</span>',
                'impaye'  => '<span class="ep-badge ep-badge-red">Impaye</span>',
                default   => '<span class="ep-badge ep-badge-gray">'.ucfirst($a->statut_paiement).'</span>',
            };

            $actifBadge = $a->actif
                ? '<span class="ep-badge ep-badge-green">Actif</span>'
                : '<span class="ep-badge ep-badge-red">Inactif</span>';

            $enAttente = $a->source === 'payeur' && ! $a->valide_par_etablissement;

            $origineBadge = $enAttente
                ? '<span class="ep-badge ep-badge-yellow">En attente de validation</span>'
                : ($a->source === 'payeur'
                    ? '<span class="ep-badge ep-badge-blue">Ajoute par famille</span>'
                    : '<span class="ep-badge ep-badge-green">Etablissement</span>');

            $nomComplet = htmlspecialchars($a->prenom.' '.$a->nom, ENT_QUOTES, 'UTF-8');
            $nomJs      = htmlspecialchars($a->nom, ENT_QUOTES, 'UTF-8');
            $prenomJs   = htmlspecialchars($a->prenom, ENT_QUOTES, 'UTF-8');
            $classeJs   = htmlspecialchars($a->classe, ENT_QUOTES, 'UTF-8');
            $matJs      = htmlspecialchars($a->matricule ?? '', ENT_QUOTES, 'UTF-8');
            $ddnJs      = $a->date_naissance ?? '';
            $sexeJs     = $a->sexe ?? '';
            $actifJs    = $a->actif ? 'true' : 'false';

            $actions = '<div class="ep-actions">';

            if ($enAttente) {
                $actions .= '
                <button onclick="ouvrirValidationApprenant('.$a->id.', &quot;'.$nomComplet.'&quot;)" class="ep-btn-icon ep-btn-green" title="Valider">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
                <button onclick="ouvrirRejetApprenant('.$a->id.', &quot;'.$nomComplet.'&quot;)" class="ep-btn-icon ep-btn-red" title="Rejeter">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>';
            }

            $actions .= '
                <button onclick="voirApprenantId('.$a->id.')" class="ep-btn-icon ep-btn-teal" title="Voir">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
                <button onclick="modifierApprenant('.$a->id.', &quot;'.$nomJs.'&quot;, &quot;'.$prenomJs.'&quot;, &quot;'.$classeJs.'&quot;, &quot;'.$matJs.'&quot;, &quot;'.$ddnJs.'&quot;, &quot;'.$sexeJs.'&quot;, '.$actifJs.')" class="ep-btn-icon ep-btn-blue" title="Modifier">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button onclick="supprimerApprenant('.$a->id.', &quot;'.$nomComplet.'&quot;)" class="ep-btn-icon ep-btn-red" title="Supprimer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>';

            return [
                '<div class="ep-dt-sub">'.e($a->matricule ?? '\u2014').'</div>',
                '<div class="ep-dt-name">'.e($a->nom).' '.e($a->prenom).'</div>',
                '<div>'.e($a->classe).'</div>',
                '<div>'.e($a->sexe ?? '\u2014').'</div>',
                $statutBadge,
                $actifBadge,
                $origineBadge,
                $actions,
            ];
        });

        return response()->json([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
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
            // 🔒 Sécurité (IDOR) : la catégorie de frais doit appartenir à CET établissement,
            // sinon un comptable/caissier pourrait injecter le categorie_frais_id d'un autre
            // établissement et créer un FraisApprenant lié à sa structure tarifaire.
            'categorie_frais_id' => [
                'nullable',
                Rule::exists('categories_frais', 'id')->where('etablissement_id', $etablissementId),
            ],
        ]);

        // Vérification limite apprenants selon plan abonnement
        $abonnement = \App\Models\Abonnement::where('etablissement_id', $etablissementId)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()->first();

        if ($abonnement) {
            $plan     = \App\Models\Abonnement::PLANS[$abonnement->plan] ?? null;
            $maxApp   = $plan['max_apprenants'] ?? -1;
            if ($maxApp > 0) {
                $nbActuels = \App\Models\Apprenant::where('etablissement_id', $etablissementId)
                    ->where('actif', true)->count();
                if ($nbActuels >= $maxApp) {
                    return back()->with('error',
                        'Limite atteinte : votre plan ' . ucfirst($abonnement->plan) .
                        ' autorise ' . $maxApp . ' apprenants actifs maximum. ' .
                        'Passez au plan supérieur pour en ajouter davantage.');
                }
            }
        }

        $validated['etablissement_id'] = $etablissementId;
        $validated['actif']            = $request->boolean('actif', true);
        $validated['statut_paiement']  = 'impaye';

        // Génération automatique du matricule si non fourni
        if (empty($validated['matricule'])) {
            $etablissement = Auth::user()->etablissement;

            // Base = code complet de l'établissement (ex. LYC-MEL-2026) ou initiales du nom
            $base = $etablissement->code_etablissement
                ? strtoupper(trim($etablissement->code_etablissement))
                : strtoupper(substr(preg_replace('/[^A-Z]/i', '', $etablissement->nom), 0, 3));

            // Numéro séquentiel : dernier matricule de cet établissement + 1.
            // Boucle de retry pour garantir l'unicité (contrainte UNIQUE en base),
            // même en cas de soft-delete ou de saisie simultanée — évite le 500
            // sur violation de contrainte d'unicité.
            $dernierMatricule = \App\Models\Apprenant::withTrashed()
                ->where('etablissement_id', $etablissementId)
                ->whereNotNull('matricule')
                ->orderByDesc('id')
                ->value('matricule');

            $numero = 1;
            if ($dernierMatricule) {
                preg_match('/(\d+)$/', $dernierMatricule, $matches);
                $numero = (isset($matches[1]) ? (int)$matches[1] : 0) + 1;
            }

            do {
                $matriculeGenere = $base . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
                $libre = ! \App\Models\Apprenant::withTrashed()->where('matricule', $matriculeGenere)->exists();
                $numero++;
            } while (! $libre);

            $validated['matricule'] = $matriculeGenere;
        }

        $categorieFraisId = $validated['categorie_frais_id'] ?? null;
        unset($validated['categorie_frais_id']);

        try {
            $apprenant = Apprenant::create($validated);

            // Si une catégorie de frais est sélectionnée, créer un FraisApprenant
            if ($categorieFraisId) {
                $categorieFrais = CategoriesFrais::findOrFail($categorieFraisId);
                FraisApprenant::create([
                    'apprenant_id'        => $apprenant->id,
                    'categorie_frais_id'  => $categorieFraisId,
                    'montant_total'       => $categorieFrais->montant_total,
                    'montant_paye'        => 0,
                    'statut'              => 'impaye',
                    'annee_scolaire'      => $categorieFrais->annee_scolaire ?? '2025-2026',
                ]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error('Erreur enregistrement apprenant (etab ' . $etablissementId . ') : ' . $e->getMessage());
            return back()->withInput()->with('error',
                'Impossible d\'enregistrer cet apprenant. Vérifiez que le matricule n\'est pas déjà utilisé, puis réessayez.');
        }

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
