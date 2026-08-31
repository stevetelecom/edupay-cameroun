<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayeurAdminController extends Controller
{
    /** Profils gérés par ce module (comptes "payeurs", hors staff établissement) */
    private array $profils = ['parent', 'eleve', 'etudiant'];

    public function index(Request $request)
    {
        $stats = [
            'total'     => User::whereIn('profil', $this->profils)->count(),
            'actifs'    => User::whereIn('profil', $this->profils)->where('suspendu', false)->count(),
            'suspendus' => User::whereIn('profil', $this->profils)->where('suspendu', true)->count(),
            'parents'   => User::where('profil', 'parent')->count(),
            'eleves'    => User::where('profil', 'eleve')->count(),
            'etudiants' => User::where('profil', 'etudiant')->count(),
        ];

        $etablissements = \App\Models\Etablissement::orderBy('nom')->get(['id', 'nom']);

        return view('admin.payeurs.index', compact('stats', 'etablissements'));
    }

    /**
     * Endpoint AJAX pour DataTables — retourne JSON paginé côté serveur
     */
    public function datatable(Request $request)
    {
        $draw   = $request->integer('draw', 1);
        $start  = $request->integer('start', 0);
        $length = $request->integer('length', 15);
        $search = $request->input('search.value', '');
        $statut = $request->input('statut', '');
        $profil = $request->input('profil', '');
        $etablissementId = $request->integer('etablissement_id', 0);
        $orderCol = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'desc');
        $selectedIds = $request->input('ids', []);

        $cols = [null, 'nom', 'telephone', 'apprenants_count', 'suspendu', 'created_at'];

        $query = User::whereIn('profil', $this->profils)->withCount('apprenants');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('prenom', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($statut === 'actif')    $query->where('suspendu', false);
        if ($statut === 'suspendu') $query->where('suspendu', true);
        if ($profil)                 $query->where('profil', $profil);

        if ($etablissementId > 0) {
            $query->whereHas('apprenants', function ($q) use ($etablissementId) {
                $q->where('apprenants.etablissement_id', $etablissementId);
            });
        }

        $total    = User::whereIn('profil', $this->profils)->count();
        $filtered = $query->count();

        $col = $cols[$orderCol] ?? 'created_at';
        $query = $query->orderBy($col, $orderDir);
        if ($length < 1) {
            $length = $filtered > 0 ? $filtered : 1;
        }
        $users = $query->skip($start)->take($length)->get();

        $rows = $users->map(function ($u) use ($selectedIds) {
            $profilBadge = match($u->profil) {
                'parent'   => '<span class="ep-badge ep-badge-green">Parent</span>',
                'eleve'    => '<span class="ep-badge ep-badge-yellow">Élève</span>',
                'etudiant' => '<span class="ep-badge ep-badge-gray">Étudiant</span>',
                default     => '<span class="ep-badge ep-badge-gray">'.ucfirst($u->profil).'</span>',
            };

            $statutBadge = $u->suspendu
                ? '<span class="ep-badge ep-badge-red">Suspendu</span>'
                : '<span class="ep-badge ep-badge-green">Actif</span>';

            $actions = '
            <div class="ep-actions">
                <button onclick="ouvrirDetailPayeur('.$u->id.')" class="ep-btn-icon ep-btn-teal" title="Détail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>';

            if ($u->suspendu) {
                $actions .= '
                <button onclick="ouvrirActivationPayeur('.$u->id.', &quot;'.htmlspecialchars($u->nom_complet, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-green" title="Réactiver">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>';
            } else {
                $actions .= '
                <button onclick="ouvrirSuspensionPayeur('.$u->id.', &quot;'.htmlspecialchars($u->nom_complet, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-yellow" title="Suspendre">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                </button>';
            }

            $actions .= '
                <button onclick="ouvrirSuppressionPayeur('.$u->id.', &quot;'.htmlspecialchars($u->nom_complet, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-red" title="Supprimer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>';

            $checked = in_array($u->id, $selectedIds) ? ' checked' : '';

            return [
                '<input type="checkbox" class="ep-dt-check payeur-check" value="'.$u->id.'"'.$checked.'>',
                '<div><div class="ep-dt-name">'.e($u->nom_complet).'</div><div class="ep-dt-sub">'.$profilBadge.'</div></div>',
                '<div>'.e($u->telephone ?? '—').'</div><div class="ep-dt-sub ep-link">'.e($u->email ?? '—').'</div>',
                '<div class="ep-dt-center">'.$u->apprenants_count.'</div>',
                $statutBadge,
                '<div class="ep-dt-sub">'.$u->created_at->format('d/m/Y').'</div>',
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

    public function show(User $payeur)
    {
        $payeur->loadCount('apprenants');
        $payeur->load(['apprenants' => function ($q) {
            $q->with('etablissement:id,nom');
        }]);

        $totalPaiements   = $payeur->paiements()->count();
        $montantTotalPaye = $payeur->paiements()->where('statut', 'valide')->sum('montant');

        return view('admin.payeurs.show', compact('payeur', 'totalPaiements', 'montantTotalPaye'));
    }

    public function suspendre(Request $request, User $payeur)
    {
        $request->validate(['raison' => ['nullable', 'string', 'max:500']]);

        $payeur->update([
            'suspendu'        => true,
            'suspendu_raison' => $request->raison,
            'suspendu_at'     => now(),
        ]);

        // Déconnexion immédiate de toute session active
        DB::table('sessions')->where('user_id', $payeur->id)->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEUR_SUSPENDU',
            "Compte payeur #{$payeur->id} — {$payeur->nom_complet} suspendu. Raison : " . ($request->raison ?? 'Non précisée'),
            $request, 'WARNING'
        );

        $message = "Le compte « {$payeur->nom_complet} » a été suspendu.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    public function activer(Request $request, User $payeur)
    {
        $payeur->update([
            'suspendu'        => false,
            'suspendu_raison' => null,
            'suspendu_at'     => null,
        ]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEUR_REACTIVE',
            "Compte payeur #{$payeur->id} — {$payeur->nom_complet} réactivé.",
            $request, 'INFO'
        );

        $message = "Le compte « {$payeur->nom_complet} » a été réactivé.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    public function destroy(Request $request, User $payeur)
    {
        $nom = $payeur->nom_complet;

        DB::table('sessions')->where('user_id', $payeur->id)->delete();
        $payeur->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEUR_SUPPRIME',
            "Compte payeur #{$payeur->id} — {$nom} supprimé (soft delete)",
            $request, 'CRITICAL'
        );

        $message = "Le compte « {$nom} » a été supprimé.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Action groupée — activer plusieurs comptes payeurs.
     */
    public function bulkActiver(Request $request)
    {
        $ids = $this->normaliserIds($request);

        if (empty($ids)) {
            return $this->bulkReponse($request, 'Aucun compte payeur sélectionné à activer.', 'error');
        }

        $users = User::whereIn('id', $ids)->whereIn('profil', $this->profils)->get();

        foreach ($users as $user) {
            $user->update([
                'suspendu'        => false,
                'suspendu_raison' => null,
                'suspendu_at'     => null,
            ]);
        }

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEURS_BULK_ACTIVES',
            count($users) . " comptes payeurs réactivés : #" . $users->pluck('id')->implode(', #'),
            $request, 'INFO'
        );

        return $this->bulkReponse($request, count($users) . ' compte(s) payeur(s) réactivé(s).', 'success');
    }

    /**
     * Action groupée — suspendre plusieurs comptes payeurs.
     */
    public function bulkSuspendre(Request $request)
    {
        $ids = $this->normaliserIds($request);

        if (empty($ids)) {
            return $this->bulkReponse($request, 'Aucun compte payeur sélectionné à suspendre.', 'error');
        }

        $users = User::whereIn('id', $ids)->whereIn('profil', $this->profils)->get();

        foreach ($users as $user) {
            $user->update([
                'suspendu'        => true,
                'suspendu_raison' => $request->input('raison') ?: 'Suspension groupée par administrateur',
                'suspendu_at'     => now(),
            ]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEURS_BULK_SUSPENDUS',
            count($users) . " comptes payeurs suspendus : #" . $users->pluck('id')->implode(', #'),
            $request, 'WARNING'
        );

        return $this->bulkReponse($request, count($users) . ' compte(s) payeur(s) suspendu(s).', 'success');
    }

    /**
     * Action groupée — supprimer (soft delete) plusieurs comptes payeurs.
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $this->normaliserIds($request);

        if (empty($ids)) {
            return $this->bulkReponse($request, 'Aucun compte payeur sélectionné à supprimer.', 'error');
        }

        $users = User::whereIn('id', $ids)->whereIn('profil', $this->profils)->get();
        $noms = $users->pluck('nom_complet')->implode(', ');

        DB::table('sessions')->whereIn('user_id', $ids)->delete();
        User::whereIn('id', $ids)->whereIn('profil', $this->profils)->delete();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PAYEURS_BULK_SUPPRIMES',
            count($users) . " comptes payeurs supprimés (soft delete) : {$noms}",
            $request, 'CRITICAL'
        );

        return $this->bulkReponse($request, count($users) . ' compte(s) payeur(s) supprimé(s).', 'success');
    }

    /**
     * Normalise l'entrée "ids" : accepte un tableau JSON OU une chaîne CSV.
     */
    private function normaliserIds(Request $request): array
    {
        $raw = $request->input('ids', []);

        if (is_array($raw)) {
            $ids = $raw;
        } elseif (is_string($raw) && trim($raw) !== '') {
            $ids = explode(',', $raw);
        } else {
            $ids = [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }

    /**
     * Normalise la réponse des actions groupées (JSON si AJAX, sinon redirect).
     */
    private function bulkReponse(Request $request, string $message, string $type = 'success')
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $type === 'success', 'message' => $message]);
        }
        return back()->with($type, $message);
    }
}
