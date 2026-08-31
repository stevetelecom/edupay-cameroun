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
        $type   = $request->input('type', '');
        $orderCol = $request->input('order.0.column', 1);
        $orderDir = $request->input('order.0.dir', 'asc');
        $selectedIds = $request->input('ids', []);

        $cols = [null, 'nom', 'type', 'telephone', 'apprenants_count', 'statut', 'created_at'];

        $query = Etablissement::withCount('apprenants');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('ville', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('code_etablissement', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        if ($statut) $query->where('statut', $statut);
        if ($type)   $query->where('type', $type);

        $total    = Etablissement::count();
        $filtered = $query->count();

        $col = $cols[$orderCol] ?? 'created_at';
        $query = $query->orderBy($col, $orderDir);
        if ($length < 1) {
            $length = $filtered > 0 ? $filtered : 1;
        }
        $etablissements = $query->skip($start)->take($length)->get();

        $rows = $etablissements->map(function ($e) use ($selectedIds) {
            $statutBadge = match($e->statut) {
                'actif'      => '<span class="ep-badge ep-badge-green">Actif</span>',
                'en_attente' => '<span class="ep-badge ep-badge-yellow">En attente</span>',
                'suspendu'   => '<span class="ep-badge ep-badge-red">Suspendu</span>',
                default      => '<span class="ep-badge ep-badge-gray">'.ucfirst($e->statut).'</span>',
            };

            $checked = in_array($e->id, $selectedIds) ? ' checked' : '';

            $actions = '
            <div class="ep-actions">
                <button onclick="ouvrirDetail('.$e->id.')" class="ep-btn-icon ep-btn-teal" title="Détail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>';

            if ($e->statut !== 'actif') {
                $actions .= '
                <button onclick="ouvrirActivation('.$e->id.', &quot;'.htmlspecialchars($e->nom, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-green" title="Activer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </button>';
            }
            if ($e->statut !== 'suspendu') {
                $actions .= '
                <button onclick="ouvrirSuspension('.$e->id.', &quot;'.htmlspecialchars($e->nom, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-yellow" title="Suspendre">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
                </button>';
            }
            $actions .= '
                <button onclick="ouvrirSuppression('.$e->id.', &quot;'.htmlspecialchars($e->nom, ENT_QUOTES, 'UTF-8').'&quot;)" class="ep-btn-icon ep-btn-red" title="Supprimer">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>';

            return [
                '<input type="checkbox" class="select-etablissement" value="'.$e->id.'"'.$checked.'>',
                '<div><div class="ep-dt-name">'.e($e->nom).'</div><div class="ep-dt-sub">'.e($e->code_etablissement).'</div></div>',
                '<div>'.e(ucfirst($e->type ?? '—')).'</div><div class="ep-dt-sub">'.e($e->ville).', '.e($e->region).'</div>',
                '<div>'.e($e->telephone).'</div><div class="ep-dt-sub ep-link">'.e($e->email).'</div>',
                '<div class="ep-dt-center">'.$e->apprenants_count.'</div>',
                $statutBadge,
                '<div class="ep-dt-sub">'.$e->created_at->format('d/m/Y').'</div>',
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

        $message = "L'etablissement « {$etablissement->nom} » a ete active. Un email de notification a ete envoye au responsable.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
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

        $message = "L'etablissement « {$etablissement->nom} » a ete suspendu. Le responsable a ete notifie.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
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

        $message = "L'etablissement « {$nom} » a ete supprime. Le responsable a ete notifie.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Activation groupée d'établissements (sélection multiple).
     * Body : { "ids": [1,2,3] }
     */
    public function bulkActiver(Request $request)
    {
        $ids = $this->normaliserIds($request);

        if (empty($ids)) {
            return back()->with('error', 'Aucun établissement sélectionné à activer.');
        }

        $etablissements = Etablissement::whereIn('id', $ids)->get();

        foreach ($etablissements as $etablissement) {
            $avant = $etablissement->statut;
            $etablissement->update(['statut' => 'actif']);

            AuditLog::enregistrer(
                Auth::guard('admin')->user(),
                'ETABLISSEMENT_ACTIVE',
                "Etablissement #{$etablissement->id} — {$etablissement->nom} active (etait : {$avant}) [groupé]",
                $request, 'INFO',
                ['statut' => $avant],
                ['statut' => 'actif']
            );

            $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
                ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
                ->first();
            if ($responsable) {
                try {
                    Mail::to($responsable->email)
                        ->send(new EtablissementActiveMail($etablissement, $responsable));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Échec email activation (groupée) : ' . $e->getMessage());
                }
            }
        }

        $message = $etablissements->count() . ' établissement(s) activé(s) avec succès.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Suppression groupée d'établissements (sélection multiple).
     * Body : { "ids": [1,2,3] }
     */
    public function bulkDestroy(Request $request)
    {
        $ids = $this->normaliserIds($request);

        if (empty($ids)) {
            return back()->with('error', 'Aucun établissement sélectionné à supprimer.');
        }

        $etablissements = Etablissement::whereIn('id', $ids)->get();

        $noms = $etablissements->pluck('nom')->implode(', ');

        foreach ($etablissements as $etablissement) {
            AuditLog::enregistrer(
                Auth::guard('admin')->user(),
                'ETABLISSEMENT_SUPPRIME',
                "Etablissement #{$etablissement->id} — {$etablissement->nom} supprime (soft delete) [groupé]",
                $request, 'CRITICAL'
            );

            $responsable = \App\Models\User::where('etablissement_id', $etablissement->id)
                ->whereHas('roles', fn($q) => $q->where('name', 'directeur'))
                ->first();
            if ($responsable) {
                try {
                    Mail::to($responsable->email)
                        ->send(new EtablissementSupprimeMail($etablissement->nom, $responsable));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Échec email suppression (groupée) : ' . $e->getMessage());
                }
            }

            $etablissement->delete();
        }

        $message = $etablissements->count() . ' établissement(s) supprimé(s) : ' . $noms;
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Normalise l'entrée "ids" : accepte un tableau JSON OU une chaîne CSV
     * (le form groupé envoie "3,4" via le champ caché name="ids").
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
}
