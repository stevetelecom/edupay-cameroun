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
