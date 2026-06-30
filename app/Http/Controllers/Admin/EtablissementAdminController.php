<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use App\Mail\EtablissementActiveMail;
use App\Mail\EtablissementSuspenduMail;
use App\Mail\EtablissementSupprimeMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
        $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
            ->first();
        return view('admin.etablissements.show', compact('etablissement', 'responsable'));
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

        // Notifier le responsable (directeur) par email
        $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
            ->first();

        if ($responsable) {
            try {
                Mail::to($responsable->email)
                    ->send(new EtablissementActiveMail($etablissement, $responsable));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Échec email activation établissement : ' . $e->getMessage());
            }
        }

        return back()->with('success', "L'etablissement « {$etablissement->nom} » a ete active. Un email de notification a ete envoye au responsable.");
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

        // Notifier le responsable par email
        $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
            ->first();
        if ($responsable) {
            try {
                Mail::to($responsable->email)
                    ->send(new EtablissementSuspenduMail($etablissement, $responsable, $request->raison));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Échec email suspension : ' . $e->getMessage());
            }
        }

        return back()->with('success', "L'etablissement « {$etablissement->nom} » a ete suspendu. Le responsable a ete notifie.");
    }

    public function destroy(Request $request, Etablissement $etablissement)
    {
        $nom = $etablissement->nom;
        $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
            ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
            ->first();
        $etablissement->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ETABLISSEMENT_SUPPRIME',
            "Etablissement #{$etablissement->id} — {$nom} supprime (soft delete)",
            $request, 'CRITICAL'
        );

        // Notifier le responsable par email (avant suppression du compte)
        if ($responsable) {
            try {
                Mail::to($responsable->email)
                    ->send(new EtablissementSupprimeMail($nom, $responsable));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Échec email suppression : ' . $e->getMessage());
            }
        }

        return back()->with('success', "L'etablissement « {$nom} » a ete supprime. Le responsable a ete notifie.");
    }
}
