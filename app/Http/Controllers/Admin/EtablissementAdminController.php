<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EtablissementAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Etablissement::withCount(['apprenants', 'commissions'])
            ->with('sites')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('nom', 'like', "%{$q}%")
                    ->orWhere('ville', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('code_etablissement', 'like', "%{$q}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $etablissements = $query->paginate(15)->withQueryString();

        $stats = [
            'total'      => Etablissement::count(),
            'actifs'     => Etablissement::where('statut', 'actif')->count(),
            'en_attente' => Etablissement::where('statut', 'en_attente')->count(),
            'suspendus'  => Etablissement::where('statut', 'suspendu')->count(),
        ];

        return view('admin.etablissements.index', compact('etablissements', 'stats'));
    }

    public function show(Etablissement $etablissement)
    {
        $etablissement->loadCount(['apprenants', 'commissions']);
        return view('admin.etablissements.show', compact('etablissement'));
    }

    public function activer(Request $request, Etablissement $etablissement)
    {
        $avant = $etablissement->statut;
        $etablissement->update(['statut' => 'actif']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ETABLISSEMENT_ACTIVE',
            "Etablissement #{$etablissement->id} — {$etablissement->nom} active (etait : {$avant})",
            $request, 'INFO',
            ['statut' => $avant],
            ['statut' => 'actif']
        );

        return back()->with('success', "L'etablissement « {$etablissement->nom} » a ete active.");
    }

    public function suspendre(Request $request, Etablissement $etablissement)
    {
        $request->validate(['raison' => ['nullable', 'string', 'max:500']]);

        $avant = $etablissement->statut;
        $etablissement->update(['statut' => 'suspendu']);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ETABLISSEMENT_SUSPENDU',
            "Etablissement #{$etablissement->id} — {$etablissement->nom} suspendu. Raison : " . ($request->raison ?? 'Non precisee'),
            $request, 'WARNING',
            ['statut' => $avant],
            ['statut' => 'suspendu']
        );

        return back()->with('success', "L'etablissement « {$etablissement->nom} » a ete suspendu.");
    }

    public function destroy(Request $request, Etablissement $etablissement)
    {
        $nom = $etablissement->nom;
        $etablissement->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ETABLISSEMENT_SUPPRIME',
            "Etablissement #{$etablissement->id} — {$nom} supprime (soft delete)",
            $request, 'CRITICAL'
        );

        return back()->with('success', "L'etablissement « {$nom} » a ete supprime.");
    }
}
