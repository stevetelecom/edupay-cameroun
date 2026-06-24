<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reclamation;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReclamationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Reclamation::with(['user', 'paiement'])
            ->orderByDesc('created_at');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('numero_ticket', 'like', "%{$q}%")
                    ->orWhere('sujet', 'like', "%{$q}%")
                    ->orWhereHas('user', function($u) use ($q) {
                        $u->where('nom', 'like', "%{$q}%")
                          ->orWhere('prenom', 'like', "%{$q}%")
                          ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $reclamations = $query->paginate(20)->withQueryString();

        $stats = [
            'ouvertes'   => Reclamation::where('statut', 'ouvert')->count(),
            'en_cours'   => Reclamation::where('statut', 'en_cours')->count(),
            'resolues'   => Reclamation::where('statut', 'resolu')->count(),
            'rejetees'   => Reclamation::where('statut', 'rejete')->count(),
        ];

        return view('admin.reclamations.index', compact('reclamations', 'stats'));
    }

    public function show(Reclamation $reclamation)
    {
        $reclamation->load(['user', 'paiement']);
        return view('admin.reclamations.show', compact('reclamation'));
    }

    public function repondre(Request $request, Reclamation $reclamation)
    {
        $request->validate([
            'reponse_admin' => ['required', 'string', 'max:2000'],
            'statut'        => ['required', 'in:en_cours,resolu,rejete'],
        ], [
            'reponse_admin.required' => 'La reponse est obligatoire.',
            'statut.required'        => 'Le statut est obligatoire.',
        ]);

        $avant = $reclamation->statut;

        $reclamation->update([
            'reponse_admin' => $request->reponse_admin,
            'statut'        => $request->statut,
            'resolu_le'     => in_array($request->statut, ['resolu', 'rejete']) ? now() : null,
        ]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'RECLAMATION_TRAITEE',
            "Reclamation {$reclamation->numero_ticket} — statut : {$avant} -> {$request->statut}",
            $request, 'INFO',
            ['statut' => $avant],
            ['statut' => $request->statut]
        );

        return back()->with('success', "Reclamation {$reclamation->numero_ticket} mise a jour.");
    }
}
