<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Etablissement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AbonnementController extends Controller
{
    public function index(Request $request)
    {
        $query = Abonnement::with(['etablissement', 'activePar'])
            ->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        $abonnements = $query->paginate(20)->withQueryString();

        $aujourdhui = now()->toDateString();

        // Les compteurs sont calcules sur les DATES, pas sur le statut stocke.
        // Le statut n'est qu'une valeur derivee, resynchronisee par
        // abonnements:synchroniser (toutes les heures) et par le middleware
        // CheckAbonnement (au passage d'un utilisateur) : entre deux
        // synchronisations, une periode echue comptait encore comme « actif »,
        // et une periode en grace était déjà sortie du compteur « actifs ».
        $stats = [
            'actifs'       => Abonnement::whereDate('date_fin', '>=', $aujourdhui)->count(),
            'grace_period' => Abonnement::whereDate('date_fin', '<', $aujourdhui)
                ->whereDate('grace_period_fin', '>=', $aujourdhui)->count(),
            'expires'      => Abonnement::whereDate('grace_period_fin', '<', $aujourdhui)->count(),

            // Argent reellement encaisse : SOMME DES MONTANTS TOTAUX, pas des
            // prix unitaires. Un abonnement de 12 mois a 12 fois le prix
            // mensuel, donc compter `montant_mensuel` sous-estimait la recette
            // d'un facteur 3 a 12.
            // whereMonth SANS whereYear cumulait en plus le mois courant de
            // TOUTES les annees : le KPI gonflait a chaque janvier.
            'revenus_mois' => Abonnement::whereDate('date_fin', '>=', $aujourdhui)
                ->whereYear('date_debut', now()->year)
                ->whereMonth('date_debut', now()->month)
                ->get(['montant_mensuel', 'date_debut', 'date_fin'])
                ->sum(fn (Abonnement $a) => $a->montantTotal()),

            // Argent en attente d'action : periodes echues needing renewal.
            'a_renouveler' => Abonnement::whereDate('date_fin', '<', $aujourdhui)->count(),
        ];

        return view('admin.abonnements.index', compact('abonnements', 'stats'));
    }

    public function datatable(Request $request)
    {
        $draw      = $request->integer('draw', 1);
        $start     = $request->integer('start', 0);
        $length    = $request->integer('length', 15);
        $search    = $request->input('search.value', '');
        $statut    = $request->input('statut', '');
        $plan      = $request->input('plan', '');
        $orderCol  = $request->input('order.0.column', 0);
        $orderDir  = $request->input('order.0.dir', 'desc');

        $query = Abonnement::select('abonnements.*')
            ->join('etablissements', 'etablissements.id', '=', 'abonnements.etablissement_id')
            ->with('etablissement');

        if ($statut) {
            $query->where('abonnements.statut', $statut);
        }
        if ($plan) {
            $query->where('abonnements.plan', $plan);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('etablissements.nom', 'like', "%{$search}%")
                  ->orWhere('etablissements.ville', 'like', "%{$search}%")
                  ->orWhere('abonnements.plan', 'like', "%{$search}%")
                  ->orWhere('abonnements.statut', 'like', "%{$search}%");
            });
        }

        $total    = Abonnement::count();
        $filtered = $query->count();

        $cols = ['etablissements.nom', 'abonnements.plan', 'abonnements.date_debut', 'abonnements.statut', 'abonnements.montant_mensuel'];
        $col = $cols[$orderCol] ?? 'abonnements.date_debut';
        $query->orderBy($col, $orderDir);

        if ($length < 1) {
            $length = $filtered > 0 ? $filtered : 1;
        }

        $abonnements = $query->skip($start)->take($length)->get();

        $rows = $abonnements->map(function (Abonnement $abo) {
            $couleurs = [
                'actif'        => 'bg-green-50 text-green-700 border-green-200',
                'grace_period' => 'bg-amber-50 text-amber-700 border-amber-200',
                'expire'       => 'bg-red-50 text-red-700 border-red-200',
                'suspendu'     => 'bg-gray-50 text-gray-600 border-gray-200',
            ];
            $planCouleurs = [
                'basique'  => 'bg-teal-50 text-teal-700',
                'standard' => 'bg-blue-50 text-blue-700',
                'premium'  => 'bg-amber-50 text-amber-700',
            ];
            $etablissement = $abo->etablissement;
            $actions = '<div class="ep-actions">';

            // La duree reelle est derivee des dates et doit etre connue AVANT
            // la construction des boutons : c'est elle qui est transmise a
            // modifierAbo() pour pre-remplir le formulaire. Un abonnement
            // « mensuel » datant de 13 mois (constate le 27/09/2026 sur #6 et
            // #7) apparait donc reellement comme tel.
            $dureeMois = $abo->dureeEnMois();
            $icone = fn (string $nom) => '<span class="material-symbols-outlined" style="font-size:15px;line-height:1;">' . $nom . '</span>';

            if (in_array($abo->statut, ['actif', 'grace_period', 'expire'])) {
                $actions .= '<button type="button" onclick="renouveler(' . $abo->id . ', \'' . addslashes($etablissement->nom ?? '') . '\', \'' . addslashes($abo->plan) . '\')" class="ep-btn-icon ep-btn-teal" title="' . e(__('admin.renew')) . '">'
                    . $icone('edit_square')
                    . '</button>';
            }

            $actions .= '<button type="button" onclick="modifierAbo(' . $abo->id
                . ', \'' . addslashes($etablissement->nom ?? '') . '\''
                . ', \'' . addslashes($abo->plan) . '\''
                . ', \'' . $abo->date_debut?->format('Y-m-d') . '\''
                . ', \'' . $dureeMois . '\''
                . ', \'' . addslashes($abo->periodeAttendue()) . '\''
                . ')" class="ep-btn-icon ep-btn-yellow" title="' . e(__('admin.edit')) . '">'
                . $icone('edit')
                . '</button>';

            $actions .= '<button type="button" onclick="supprimerAbo(' . $abo->id . ', \'' . addslashes($etablissement->nom ?? '') . '\')" class="ep-btn-icon ep-btn-red" title="' . e(__('admin.supprimer')) . '">'
                . $icone('delete')
                . '</button>';

            $actions .= '</div>';

            // Montant total encaisse : prix du plan multiplie par la duree
            // souscrite (3 mois = 3 x le prix mensuel, 12 mois = 12 x).
            // Sans cette ligne, seul le prix unitaire etait visible et
            // l'administrateur ne pouvait pas verifier la somme percue.
            $periode = '<div class="ep-dt-sub">'
                . e($abo->date_debut?->format('d/m/Y') ?? '—') . ' au '
                . e($abo->date_fin?->format('d/m/Y') ?? '—')
                . ' <span class="ep-badge-duree">' . $dureeMois . ' mois</span></div>';

            $periode .= '<div class="ep-dt-sub font-semibold text-gray-700">'
                . e(number_format($abo->montantTotal(), 0, ',', ' ')) . ' FCFA'
                . ' <span class="text-gray-400 font-normal">('
                . e(number_format((int) $abo->montant_mensuel, 0, ',', ' ')) . ' × ' . $dureeMois . ')'
                . '</span></div>';

            // L'etat est calcule depuis les DATES, pas depuis le statut stocke :
            // une periode finie s'affiche « Expiré » meme si le statut
            // n'a jamais ete synchronise (le middleware ne mettait a jour
            // qu'a la connexion d'un eleve).
            switch ($abo->etat()) {
                case 'expire':
                    $periode .= '<div class="ep-dt-sub font-semibold text-red-600">'
                        . e(__('admin.expire_le_abo', ['date' => $abo->date_fin?->format('d/m/Y')]))
                        . '</div>';
                    break;

                case 'grace_period':
                    $periode .= '<div class="ep-dt-sub font-semibold text-amber-600">'
                        . e(__('admin.expire_le_abo', ['date' => $abo->date_fin?->format('d/m/Y')])) . '</div>';
                    $periode .= '<div class="ep-dt-sub text-amber-600">'
                        . e(__('admin.grace_jusqu_au', ['date' => $abo->grace_period_fin?->format('d/m/Y')]))
                        . '</div>';
                    break;

                default:
                    $periode .= '<div class="ep-dt-sub text-gray-400">'
                        . e(trans_choice('admin.jours_restants_abo', $abo->joursRestants(), [
                            'n' => $abo->joursRestants(),
                        ]))
                        . '</div>';
            }

            // Badge de statut : on affiche l'etat reel, et on signale quand la
            // valeur stocke n'a pas encore ete resynchronisee.
            $etat = $abo->etat();
            $libelleStatut = e(ucfirst(str_replace('_', ' ', $etat)));
            if ($abo->statutDesynchronise()) {
                $libelleStatut .= ' <span class="ep-dt-sub text-gray-400" title="'
                    . e(__('admin.statut_a_synchroniser')) . '">*</span>';
            }

            return [
                '<div><div class="ep-dt-name">' . e($etablissement->nom ?? '—') . '</div><div class="ep-dt-sub">' . e($etablissement->ville ?? '—') . '</div></div>',
                '<span class="text-xs font-semibold px-2 py-1 rounded-full ' . ($planCouleurs[$abo->plan] ?? '') . '">' . ucfirst($abo->plan) . '</span>',
                $periode,
                '<span class="text-xs font-medium px-2 py-1 rounded-full border ' . ($couleurs[$etat] ?? '') . '">' . $libelleStatut . '</span>',
                '<div class="ep-dt-name">' . number_format($abo->montant_mensuel, 0, ',', ' ') . ' FCFA</div>',
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'etablissement_id'    => 'required|exists:etablissements,id',
            'plan'                => 'required|in:basique,standard,premium',
            'date_debut'          => 'required|date',
            // Duree souscrite : 1 mois par defaut (abonnement mensuel).
            'duree_mois'          => 'nullable|integer|in:' . implode(',', Abonnement::DUREES_MOIS),
            'reference_paiement'  => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ], [
            'duree_mois.integer' => 'La durée doit être un nombre de mois entier.',
            'duree_mois.in'       => 'La durée doit être l\'une des offres : ' . implode(',', Abonnement::DUREES_MOIS) . ' mois.',
        ]);

        $dateDebut = Carbon::parse($validated['date_debut']);

        // Toute la règle de durée passe par le modele : plus de « addMonth()
        // ecrit en dur » qui peut deriver de la regle mensuelle.
        $periode = Abonnement::periode($dateDebut, $validated['duree_mois'] ?? null);
        $dateFin = $periode['date_fin'];
        $montant = Abonnement::montantPlan($validated['plan']);

        // Désactiver l'abonnement précédent si existant
        Abonnement::where('etablissement_id', $validated['etablissement_id'])
            ->whereIn('statut', ['actif', 'grace_period'])
            ->update(['statut' => 'expire']);

        $abonnement = Abonnement::create([
            'etablissement_id'   => $validated['etablissement_id'],
            'plan'               => $validated['plan'],
            'montant_mensuel'    => $montant,
            'date_debut'         => $dateDebut,
            'date_fin'           => $dateFin,
            'grace_period_fin'   => $periode['grace_period_fin'],
            'statut'             => 'actif',
            'reference_paiement' => $validated['reference_paiement'] ?? null,
            'notes'              => $validated['notes'] ?? null,
            'active_par'         => Auth::guard('admin')->id(),
            'active_at'          => now(),
        ]);

        // Mettre à jour l'établissement
        Etablissement::find($validated['etablissement_id'])->update([
            'plan_abonnement'      => $validated['plan'],
            'abonnement_expire_le' => $dateFin,
        ]);

        $montantTotal = Abonnement::montantTotalPour($validated['plan'], $periode['duree_mois']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_ACTIVE',
            'Abonnement ' . strtoupper($validated['plan']) . ' activé pour établissement #' . $validated['etablissement_id']
                . ' (' . $periode['duree_mois'] . ' mois, jusqu\'au ' . $dateFin->format('d/m/Y')
                . ', total ' . number_format($montantTotal, 0, ',', ' ') . ' FCFA)',
            $request, 'INFO'
        );

        return back()->with('success', __('admin.abonnement_active_jusquau', [
            'plan'    => ucfirst($validated['plan']),
            'date'    => $dateFin->format('d/m/Y'),
            'mois'    => $periode['duree_mois'],
            'montant' => number_format($montantTotal, 0, ',', ' '),
        ]));
    }

    public function update(Request $request, Abonnement $abonnement)
    {
        // Durees acceptees : le catalogue, plus la duree que porte DEJA cette
        // ligne. Sans cetteTolerance, une periode historique de 13 mois ne
        // pourrait plus etre enregistree du tout (le select propose 13 mois
        // « hors offre », la validation le rejetait) et l'admin serait oblige
        // de la raccourcir sans avoir choisi de le faire.
        $dureesAutorisees = array_unique(array_merge(
            Abonnement::DUREES_MOIS,
            [$abonnement->dureeEnMois()]
        ));

        $validated = $request->validate([
            'plan'               => 'required|in:basique,standard,premium',
            'duree_mois'         => 'nullable|integer|in:' . implode(',', $dureesAutorisees),
            'reference_paiement' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ], [
            'duree_mois.integer' => 'La durée doit être un nombre de mois entier.',
            'duree_mois.in'       => 'La durée doit être l\'une des offres : ' . implode(',', $dureesAutorisees) . ' mois.',
        ]);

        $montant = Abonnement::montantPlan($validated['plan']);

        // La periode est RE-CALCULEE depuis date_debut : c'est ce qui garantit
        // qu'un abonnement dit « 1 mois » ne peut pas finir 13 mois plus tard.
        //
        // En revanche, si aucune duree n'est transmise (select vide), on
        // CONSERVE les dates existantes au lieu de les recalculer : une
        // periode historique hors catalogue (13 mois, cf. #6 / #7) aurait ete
        // silencieusement ramenee a 1 mois, ce qui reecrivait l'echeance et le
        // montant deja encaisses.
        $dureeDemandee = $validated['duree_mois'] ?? null;
        $dureeActuelle  = $abonnement->dureeEnMois();

        // On ne recalcule la periode que si la duree CHANGE vraiment. Envoyer
        // la duree que porte deja la ligne (ou un select vide) doit la
        // laisser intacte : sinon une periode hors catalogue de 13 mois
        // repassait a 1 mois, puisque periode() ne connait que 1/3/6/12.
        if ($dureeDemandee !== null && $dureeDemandee !== $dureeActuelle) {
            $periode = Abonnement::periode($abonnement->date_debut, $dureeDemandee);
        } else {
            $periode = [
                'date_fin'         => $abonnement->date_fin,
                'grace_period_fin' => $abonnement->grace_period_fin,
                'duree_mois'       => $dureeActuelle,
            ];
        }

        $nouvelleDateFin = $periode['date_fin'];

        $abonnement->update([
            'plan'               => $validated['plan'],
            'montant_mensuel'    => $montant,
            'date_fin'           => $nouvelleDateFin,
            'grace_period_fin'   => $periode['grace_period_fin'],
            'statut'             => 'actif',
            'reference_paiement' => $validated['reference_paiement'] ?? $abonnement->reference_paiement,
            'notes'              => $validated['notes'] ?? $abonnement->notes,
            'active_par'         => Auth::guard('admin')->id(),
            'active_at'          => now(),
        ]);

        $abonnement->etablissement->update([
            'plan_abonnement'      => $validated['plan'],
            'abonnement_expire_le' => $nouvelleDateFin,
        ]);

        $montantTotal = $montant * max(1, (int) $periode['duree_mois']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_MODIFIE',
            'Plan modifié : ' . strtoupper($validated['plan']) . ' pour établissement #' . $abonnement->etablissement_id
                . ' (' . $periode['duree_mois'] . ' mois, jusqu\'au ' . $nouvelleDateFin->format('d/m/Y')
                . ', total ' . number_format($montantTotal, 0, ',', ' ') . ' FCFA)',
            $request, 'INFO'
        );

        return back()->with('success', __('admin.abonnement_modifie_jusquau', [
            'plan'    => ucfirst($validated['plan']),
            'date'    => $nouvelleDateFin->format('d/m/Y'),
            'mois'    => $periode['duree_mois'],
            'montant' => number_format($montantTotal, 0, ',', ' '),
        ]));
    }

    public function destroy(Request $request, Abonnement $abonnement)
    {
        $nom = $abonnement->etablissement->nom ?? 'inconnu';

        $abonnement->etablissement->update([
            'plan_abonnement'      => null,
            'abonnement_expire_le' => null,
        ]);

        $abonnement->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_SUPPRIME',
            'Abonnement supprimé pour établissement : ' . $nom,
            $request, 'CRITICAL'
        );

        return back()->with('success', 'Abonnement de « ' . $nom . ' » supprimé.');
    }

    public function renouveler(Request $request, Abonnement $abonnement)
    {
        $validated = $request->validate([
            'duree_mois'         => 'nullable|integer|in:' . implode(',', Abonnement::DUREES_MOIS),
            'reference_paiement' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ], [
            'duree_mois.integer' => 'La durée doit être un nombre de mois entier.',
            'duree_mois.in'       => 'La durée doit être l\'une des offres : ' . implode(',', Abonnement::DUREES_MOIS) . ' mois.',
        ]);

        $nouvelleDateDebut = Carbon::today();
        $periode            = Abonnement::periode($nouvelleDateDebut, $validated['duree_mois'] ?? null);
        $nouvelleDateFin    = $periode['date_fin'];

        $abonnement->update([
            'date_debut'         => $nouvelleDateDebut,
            'date_fin'           => $nouvelleDateFin,
            'grace_period_fin'   => $periode['grace_period_fin'],
            'statut'             => 'actif',
            'reference_paiement' => $validated['reference_paiement'] ?? $abonnement->reference_paiement,
            'notes'              => $validated['notes'] ?? $abonnement->notes,
            'active_par'         => Auth::guard('admin')->id(),
            'active_at'          => now(),
        ]);

        $abonnement->etablissement->update([
            'plan_abonnement'      => $abonnement->plan,
            'abonnement_expire_le' => $nouvelleDateFin,
        ]);

        $montantTotal = Abonnement::montantTotalPour($abonnement->plan, $periode['duree_mois']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_RENOUVELE',
            'Abonnement renouvelé pour établissement #' . $abonnement->etablissement_id
                . ' (' . $periode['duree_mois'] . ' mois, jusqu\'au ' . $nouvelleDateFin->format('d/m/Y')
                . ', total ' . number_format($montantTotal, 0, ',', ' ') . ' FCFA)',
            $request, 'INFO'
        );

        return back()->with('success', __('admin.abonnement_renouvele_jusquau', [
            'date'    => $nouvelleDateFin->format('d/m/Y'),
            'mois'    => $periode['duree_mois'],
            'montant' => number_format($montantTotal, 0, ',', ' '),
        ]));
    }
}
