<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Commission::with([
                'etablissement',
                'paiement',
            ])
            ->orderByDesc('created_at');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('etablissement_id')) {
            $query->where('etablissement_id', $request->etablissement_id);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->whereHas('etablissement', function($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%");
            });
        }

        $commissions = $query->paginate(20)->withQueryString();

        $stats = [
            'total_mois'   => Commission::whereMonth('created_at', now()->month)->sum('montant_commission'),
            'nb_mois'      => Commission::whereMonth('created_at', now()->month)->count(),
            'calculees'    => Commission::where('statut', 'calculee')->count(),
            'prelevees'    => Commission::where('statut', 'prelevee')->count(),
        ];

        $etablissements = Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom']);

        $tauxActuel = config('services.edupay.taux_commission', 0.025);

        return view('admin.commissions.index', compact(
            'commissions', 'stats', 'etablissements', 'tauxActuel'
        ));
    }

    public function edit(Etablissement $etablissement)
    {
        return view('admin.commissions.edit', compact('etablissement'));
    }

    public function update(Request $request, Etablissement $etablissement)
    {
        $request->validate([
            'taux_commission' => ['required', 'numeric', 'min:0', 'max:0.1'],
        ], [
            'taux_commission.required' => 'Le taux est obligatoire.',
            'taux_commission.min'      => 'Le taux minimum est 0%.',
            'taux_commission.max'      => 'Le taux maximum est 10%.',
        ]);

        $avant = $etablissement->taux_commission;
        $etablissement->update([
            'taux_commission' => $request->taux_commission,
        ]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'COMMISSION_MODIFIEE',
            "Taux commission {$etablissement->nom} : {$avant} -> {$request->taux_commission}",
            $request, 'WARNING',
            ['taux_commission' => $avant],
            ['taux_commission' => $request->taux_commission]
        );

        return back()->with('success', "Taux de commission mis a jour pour « {$etablissement->nom} ».");
    }

    public function marquerPrelevee(Request $request, Commission $commission)
    {
        $commission->update(['statut' => 'prelevee']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'COMMISSION_PRELEVEE',
            "Commission #{$commission->id} marquee comme prelevee",
            $request, 'INFO'
        );

        return back()->with('success', 'Commission marquee comme prelevee.');
    }
}
