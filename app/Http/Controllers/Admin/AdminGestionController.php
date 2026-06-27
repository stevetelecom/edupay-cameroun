<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminGestionController extends Controller
{
    private array $rolesDisponibles = [
        'super_admin'          => 'Super Admin — Accès total',
        'superviseur'          => 'Superviseur — Lecture + rapports',
        'comptable_plateforme' => 'Comptable plateforme — Commissions + exports',
    ];

    public function index()
    {
        $admins = Admin::orderBy('created_at')->get();
        return view('admin.admins.index', [
            'admins'            => $admins,
            'rolesDisponibles'  => $this->rolesDisponibles,
            'pageTitle'         => 'Équipe de supervision — EduPay',
        ]);
    }

    public function store(Request $request)
    {
        // Seul un super_admin peut créer d'autres admins
        $adminConnecte = Auth::guard('admin')->user();
        if ($adminConnecte->role !== 'super_admin') {
            abort(403, 'Seul un Super Admin peut créer des comptes administrateurs.');
        }

        $validated = $request->validate([
            'prenom'    => ['required', 'string', 'max:80'],
            'nom'       => ['required', 'string', 'max:80'],
            'email'     => ['required', 'email', 'unique:admins,email'],
            'telephone' => ['required', 'string', 'max:20'],
            'role'      => ['required', 'in:super_admin,superviseur,comptable_plateforme'],
            'password'  => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'email.unique'     => 'Cet email est déjà utilisé par un autre admin.',
            'password.min'     => 'Le mot de passe doit contenir au moins 10 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $admin = Admin::create([
            'prenom'    => $validated['prenom'],
            'nom'       => strtoupper($validated['nom']),
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'],
            'password'  => $validated['password'],
            'role'      => $validated['role'],
            'est_actif' => true,
        ]);

        AuditLog::enregistrer(
            $adminConnecte,
            'ADMIN_CREE',
            'Nouvel admin créé : ' . $admin->email . ' — rôle : ' . $admin->role,
            $request,
            'INFO'
        );

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Compte administrateur créé pour ' . $admin->prenom . ' ' . $admin->nom . '.');
    }

    public function activer(Request $request, Admin $admin)
    {
        $admin->update(['est_actif' => true]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ADMIN_ACTIVE',
            'Admin activé : ' . $admin->email,
            $request,
            'INFO'
        );

        return back()->with('success', $admin->prenom . ' activé(e).');
    }

    public function suspendre(Request $request, Admin $admin)
    {
        // Impossible de se suspendre soi-même
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Vous ne pouvez pas suspendre votre propre compte.');
        }

        $admin->update(['est_actif' => false]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ADMIN_SUSPENDU',
            'Admin suspendu : ' . $admin->email,
            $request,
            'WARNING'
        );

        return back()->with('success', $admin->prenom . ' suspendu(e).');
    }

    public function destroy(Request $request, Admin $admin)
    {
        // Impossible de se supprimer soi-même
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $email = $admin->email;
        $admin->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ADMIN_SUPPRIME',
            'Admin supprimé : ' . $email,
            $request,
            'CRITICAL'
        );

        return back()->with('success', 'Compte supprimé.');
    }
}
