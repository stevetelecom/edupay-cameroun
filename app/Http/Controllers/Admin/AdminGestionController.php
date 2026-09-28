<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Traits\TelephoneCamerounais;

class AdminGestionController extends Controller
{
    use TelephoneCamerounais;

    private array $rolesDisponibles = [
        'super-admin'          => 'Super Admin — Accès total',
        'superviseur'          => 'Superviseur — Lecture + rapports',
        'comptable_plateforme' => 'Comptable plateforme — Commissions + exports',
    ];

    public function updateProfil(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'prenom'    => ['required', 'string', 'max:80'],
            'nom'       => ['required', 'string', 'max:80'],
            'email'     => ['required', 'email', 'unique:admins,email,' . $admin->id],
            'telephone' => ['nullable', 'string', 'max:20'],
            'password'  => ['nullable', 'string', 'min:10', 'confirmed'],
        ], [
            'email.unique'       => 'Cet email est déjà utilisé.',
            'password.min'       => 'Minimum 10 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $data = [
            'prenom'    => $validated['prenom'],
            'nom'       => strtoupper($validated['nom']),
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'] ?? $admin->telephone,
        ];
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        $admin->update($data);

        AuditLog::enregistrer($admin, 'PROFIL_MODIFIE', 'Profil admin mis à jour.', $request, 'INFO');

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function index()
    {
        $totalAdmins = Admin::count();
        return view('admin.admins.index', [
            'totalAdmins'       => $totalAdmins,
            'rolesDisponibles'  => $this->rolesDisponibles,
            'pageTitle'         => 'Équipe de supervision — EduPay',
        ]);
    }

    public function datatable(Request $request)
    {
        $draw   = $request->integer('draw', 1);
        $start  = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $search = $request->input('search.value', '');
        $orderCol = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        $cols = ['nom', null, 'telephone', 'derniere_connexion', 'est_actif', null];

        $query = Admin::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $total    = Admin::count();
        $filtered = $query->count();

        $col = $cols[$orderCol] ?? 'created_at';
        if ($col) {
            $query->orderBy($col, $orderDir);
        } else {
            $query->orderBy('created_at', $orderDir);
        }

        if ($length < 1) {
            $length = $total > 0 ? $total : 1;
        }

        $admins = $query->skip($start)->take($length)->get();

        $rows = $admins->map(function (Admin $admin) {
            $roleAdmin = $admin->getRoleNames()->first() ?? '';
            $roleStyles = [
                'super-admin'          => 'bg-purple-50 text-purple-700 border-purple-200',
                'superviseur'          => 'bg-blue-50 text-blue-700 border-blue-200',
                'comptable_plateforme' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
            $roleLabels = [
                'super-admin'          => 'Super Admin',
                'superviseur'          => 'Superviseur',
                'comptable_plateforme' => 'Comptable plateforme',
            ];
            // Libelles et statuts traduits : ils etaient ecrits en dur en
            // francais dans cette chaine HTML, donc un utilisateur anglais
            // lisait « Actif », « Jamais », « Voir » dans un back office
            // english. Idem pour les title= des boutons, lus au survol.
            $libelleStatut = $admin->est_actif ? __('admin.actif') : __('admin.suspendu');
            $libelleConnexion = $admin->derniere_connexion
                ? $admin->derniere_connexion->diffForHumans()
                : __('admin.jamais');
            $icone = fn (string $nom) => '<span class="material-symbols-outlined" style="font-size:15px;line-height:1;">' . $nom . '</span>';

            $statusBadge = $admin->est_actif
                ? '<span class="ep-badge ep-badge-green">' . e(__('admin.actif')) . '</span>'
                : '<span class="ep-badge ep-badge-red">' . e(__('admin.suspendu')) . '</span>';

            $actions = '<div class="ep-actions">';
            $actions .= '<button onclick="voirAdmin(\'' . addslashes($admin->initiales) . '\', \'' . addslashes($admin->nom_complet) . '\', \'' . addslashes($admin->email) . '\', \'' . addslashes($admin->telephone ?? '') . '\', \'' . addslashes($libelleStatut) . '\', \'' . addslashes($libelleConnexion) . '\', \'' . addslashes($roleAdmin) . '\')" class="ep-btn-icon ep-btn-teal" title="' . e(__('admin.voir')) . '">'
                . $icone('visibility')
                . '</button>';

            if ($admin->id !== auth()->guard('admin')->id()) {
                $actions .= '<button onclick="modifierAdmin(' . $admin->id . ', \'' . addslashes($admin->prenom) . '\', \'' . addslashes($admin->nom) . '\', \'' . addslashes($admin->email) . '\', \'' . addslashes($admin->telephone ?? '') . '\', \'' . addslashes($roleAdmin) . '\')" class="ep-btn-icon ep-btn-yellow" title="' . e(__('admin.edit')) . '">'
                    . $icone('edit')
                    . '</button>';
            }

            if ($admin->id !== auth()->guard('admin')->id()) {
                if ($admin->est_actif) {
                    $actions .= '<button onclick="confirmerSuspensionAdmin(' . $admin->id . ', \'' . addslashes($admin->prenom . ' ' . $admin->nom) . '\')" class="ep-btn-icon ep-btn-red" title="' . e(__('admin.suspendre')) . '">'
                        . $icone('pause')
                        . '</button>';
                } else {
                    $nomCompletAdmin = addslashes($admin->prenom . ' ' . $admin->nom);
                    $actions .= '<button onclick="confirmerActivationAdmin(' . $admin->id . ', \'' . $nomCompletAdmin . '\')" class="ep-btn-icon ep-btn-green" title="' . e(__('admin.activer')) . '">'
                        . $icone('check')
                        . '</button>';
                }
                $actions .= '<button onclick="confirmerSuppressionAdmin(' . $admin->id . ', \'' . addslashes($admin->prenom . ' ' . $admin->nom) . '\')" class="ep-btn-icon ep-btn-red" title="' . e(__('admin.supprimer')) . '">'
                    . $icone('delete')
                    . '</button>';
            }

            $actions .= '</div>';
            return [
                '<div><div class="ep-dt-name">'.e($admin->nom_complet).'</div><div class="ep-dt-sub">'.e($roleLabels[$roleAdmin] ?? $roleAdmin).'</div></div>',
                $roleAdmin ? '<span class="ep-badge '.($roleStyles[$roleAdmin] ?? 'ep-badge-gray').'">'.e($roleLabels[$roleAdmin] ?? $roleAdmin).'</span>' : '—',
                '<div>'.e($admin->telephone ?? '—').'</div><div class="ep-dt-sub">'.e($admin->email ?? '—').'</div>',
                '<div class="ep-dt-sub">'.e($admin->derniere_connexion ? $admin->derniere_connexion->diffForHumans() : __('admin.jamais_connecte')).'</div>',
                $statusBadge,
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
        // Seul un super_admin peut créer d'autres admins
        $adminConnecte = Auth::guard('admin')->user();
        if (!$adminConnecte->hasRole('super-admin')) {
            abort(403, 'Seul un Super Admin peut créer des comptes administrateurs.');
        }

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => $this->normaliserTelephoneCm((string) $request->input('telephone'))]);
        }

        $validated = $request->validate([
            'prenom'    => ['required', 'string', 'max:80'],
            'nom'       => ['required', 'string', 'max:80'],
            'email'     => ['required', 'email', 'unique:admins,email'],
            'telephone' => ['required', 'regex:/^6\d{8}$/'],
            'role'      => ['required', 'in:super-admin,superviseur,comptable_plateforme'],
            'password'  => ['required', 'string', 'min:10', 'confirmed'],
        ], [
            'email.unique'        => 'Cet email est déjà utilisé par un autre admin.',
            'telephone.regex'     => 'Numéro invalide. Format attendu : 6XXXXXXXX (9 chiffres, mobile camerounais).',
            'password.min'        => 'Le mot de passe doit contenir au moins 10 caractères.',
            'password.confirmed'  => 'Les mots de passe ne correspondent pas.',
        ]);

        $admin = Admin::create([
            'prenom'    => $validated['prenom'],
            'nom'       => strtoupper($validated['nom']),
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'],
            'password'  => Hash::make($validated['password']),
            'est_actif' => true,
        ]);
        $admin->assignRole($validated['role']);

        AuditLog::enregistrer(
            $adminConnecte,
            'ADMIN_CREE',
            'Nouvel admin créé : ' . $admin->email . ' — rôle : ' . $validated['role'],
            $request,
            'INFO'
        );

        return redirect()
            ->route('admin.admins.index')
            ->with('success', 'Compte administrateur créé pour ' . $admin->prenom . ' ' . $admin->nom . '.');
    }

    public function update(Request $request, Admin $admin)
    {
        $adminConnecte = Auth::guard('admin')->user();
        if (!$adminConnecte->hasRole('super-admin')) {
            abort(403);
        }
        if ($admin->id === $adminConnecte->id) {
            return back()->with('error', 'Utilisez votre profil pour modifier votre propre compte.');
        }

        if ($request->filled('telephone')) {
            $request->merge(['telephone' => $this->normaliserTelephoneCm((string) $request->input('telephone'))]);
        }

        $validated = $request->validate([
            'prenom'    => ['required', 'string', 'max:80'],
            'nom'       => ['required', 'string', 'max:80'],
            'email'     => ['required', 'email', 'unique:admins,email,' . $admin->id],
            'telephone' => ['nullable', 'regex:/^6\d{8}$/'],
            'role'      => ['required', 'in:super-admin,superviseur,comptable_plateforme'],
            'password'  => ['nullable', 'string', 'min:10', 'confirmed'],
        ], [
            'email.unique'       => 'Cet email est déjà utilisé.',
            'telephone.regex'    => 'Numéro invalide. Format attendu : 6XXXXXXXX (9 chiffres, mobile camerounais).',
            'password.min'       => 'Minimum 10 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ]);

        $data = [
            'prenom'    => $validated['prenom'],
            'nom'       => strtoupper($validated['nom']),
            'email'     => $validated['email'],
            'telephone' => $validated['telephone'],
        ];
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }
        $admin->update($data);
        $admin->syncRoles([$validated['role']]);

        AuditLog::enregistrer(
            $adminConnecte,
            'ADMIN_MODIFIE',
            'Admin modifié : ' . $admin->email . ' — rôle : ' . $validated['role'],
            $request,
            'INFO'
        );

        return redirect()->route('admin.admins.index')
            ->with('success', $admin->prenom . ' ' . $admin->nom . ' mis à jour avec succès.');
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

        // Suspension = retrait immediat de l'acces, pas seulement a la
        // prochaine connexion : `est_actif` est desormais revérifié par
        // SuperAdminMiddleware et AdminAuthController, mais une session et un
        // cookie « se souvenir de moi » deja emis continuaient de passer
        // jusqu'a expiration. On invalide donc les deux ici.
        $admin->setRememberToken(\Illuminate\Support\Str::random(60));
        $admin->save();
        $admin->tokens()->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'ADMIN_SUSPENDU',
            'Admin suspendu, sessions et tokens revokes : ' . $admin->email,
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
