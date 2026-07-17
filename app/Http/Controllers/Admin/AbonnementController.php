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

        $stats = [
            'actifs'       => Abonnement::where('statut', 'actif')->count(),
            'grace_period' => Abonnement::where('statut', 'grace_period')->count(),
            'expires'      => Abonnement::where('statut', 'expire')->count(),
            'revenus_mois' => Abonnement::where('statut', 'actif')
                ->whereMonth('date_debut', now()->month)
                ->sum('montant_mensuel'),
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

            if (in_array($abo->statut, ['actif', 'grace_period', 'expire'])) {
                $actions .= '<button onclick="renouveler(' . $abo->id . ', \'' . addslashes($etablissement->nom ?? '') . '\', \'' . addslashes(ucfirst($abo->plan)) . '\')" class="ep-btn-icon ep-btn-teal" title="Renouveler">'
                    . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 4 20 8 8 20 4 20 4 16 16 4"/></svg>'
                    . '</button>';
            }

            $actions .= '<button onclick="modifierAbo(' . $abo->id . ', \'' . addslashes($etablissement->nom ?? '') . '\', \'' . addslashes($abo->plan) . '\')" class="ep-btn-icon ep-btn-yellow" title="Modifier">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>'
                . '</button>';

            $actions .= '<button onclick="supprimerAbo(' . $abo->id . ', \'' . addslashes($etablissement->nom ?? '') . '\')" class="ep-btn-icon ep-btn-red" title="Supprimer">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>'
                . '</button>';

            $actions .= '</div>';

            $periode = '<div class="ep-dt-sub">' . $abo->date_debut->format('d/m/Y') . ' → ' . $abo->date_fin->format('d/m/Y') . '</div>';
            if ($abo->enGracePeriod()) {
                $periode .= '<div class="ep-dt-sub ep-link text-amber-600">Grace jusqu\'au ' . $abo->grace_period_fin->format('d/m/Y') . '</div>';
            } else {
                $periode .= '<div class="ep-dt-sub text-gray-400">' . $abo->joursRestants() . ' jours restants</div>';
            }

            return [
                '<div><div class="ep-dt-name">' . e($etablissement->nom ?? '—') . '</div><div class="ep-dt-sub">' . e($etablissement->ville ?? '—') . '</div></div>',
                '<span class="text-xs font-semibold px-2 py-1 rounded-full ' . ($planCouleurs[$abo->plan] ?? '') . '">' . ucfirst($abo->plan) . '</span>',
                $periode,
                '<span class="text-xs font-medium px-2 py-1 rounded-full border ' . ($couleurs[$abo->statut] ?? '') . '">' . ucfirst(str_replace('_', ' ', $abo->statut)) . '</span>',
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
            'reference_paiement'  => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
        ]);

        $dateDebut     = Carbon::parse($validated['date_debut']);
        $dateFin       = $dateDebut->copy()->addMonth()->subDay();
        $gracePeriod   = $dateFin->copy()->addDays(7);
        $montant       = Abonnement::montantPlan($validated['plan']);

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
            'grace_period_fin'   => $gracePeriod,
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

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_ACTIVE',
            'Abonnement ' . strtoupper($validated['plan']) . ' activé pour établissement #' . $validated['etablissement_id'],
            $request, 'INFO'
        );

        return back()->with('success', 'Abonnement ' . ucfirst($validated['plan']) . ' activé avec succès jusqu\'au ' . $dateFin->format('d/m/Y') . '.');
    }

    public function update(Request $request, Abonnement $abonnement)
    {
        $validated = $request->validate([
            'plan'               => 'required|in:basique,standard,premium',
            'reference_paiement' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        $montant       = Abonnement::montantPlan($validated['plan']);
        $nouvelleDateFin = $abonnement->date_debut->copy()->addMonth()->subDay();
        $gracePeriod     = $nouvelleDateFin->copy()->addDays(7);

        $abonnement->update([
            'plan'               => $validated['plan'],
            'montant_mensuel'    => $montant,
            'date_fin'           => $nouvelleDateFin,
            'grace_period_fin'   => $gracePeriod,
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

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_MODIFIE',
            'Plan modifié → ' . strtoupper($validated['plan']) . ' pour établissement #' . $abonnement->etablissement_id,
            $request, 'INFO'
        );

        return back()->with('success', 'Plan modifié avec succès → ' . ucfirst($validated['plan']) . '.');
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
            'reference_paiement' => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        $nouvelleDateDebut = Carbon::today();
        $nouvelleDateFin   = $nouvelleDateDebut->copy()->addMonth()->subDay();
        $gracePeriod       = $nouvelleDateFin->copy()->addDays(7);

        $abonnement->update([
            'date_debut'         => $nouvelleDateDebut,
            'date_fin'           => $nouvelleDateFin,
            'grace_period_fin'   => $gracePeriod,
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

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ABONNEMENT_RENOUVELE',
            'Abonnement renouvelé pour établissement #' . $abonnement->etablissement_id,
            $request, 'INFO'
        );

        return back()->with('success', 'Abonnement renouvelé jusqu\'au ' . $nouvelleDateFin->format('d/m/Y') . '.');
    }
}
