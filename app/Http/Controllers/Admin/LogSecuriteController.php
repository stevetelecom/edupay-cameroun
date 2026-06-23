<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LogSecuriteController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::orderByDesc('created_at');

        if ($request->filled('niveau')) {
            $query->where('niveau', $request->niveau);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('action', 'like', "%{$q}%")
                    ->orWhere('detail', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // Export CSV
        if ($request->has('export')) {
            return $this->exportCsv($query->get());
        }

        $logs = $query->paginate(25)->withQueryString();

        $stats = [
            'total_jour'   => AuditLog::whereDate('created_at', today())->count(),
            'critiques'    => AuditLog::where('niveau', 'CRITICAL')->whereDate('created_at', today())->count(),
            'warnings'     => AuditLog::where('niveau', 'WARNING')->whereDate('created_at', today())->count(),
            'connexions'   => AuditLog::where('action', 'LOGIN_SUCCES')->whereDate('created_at', today())->count(),
        ];

        return view('admin.logs.index', compact('logs', 'stats'));
    }

    public function show(AuditLog $log)
    {
        return view('admin.logs.show', compact('log'));
    }

    private function exportCsv($logs)
    {
        $rows   = [];
        $rows[] = implode(';', [
            'Date', 'Niveau', 'Action', 'Detail', 'IP', 'Acteur'
        ]);

        foreach ($logs as $log) {
            $rows[] = implode(';', [
                $log->created_at->format('d/m/Y H:i:s'),
                $log->niveau,
                $log->action,
                str_replace(["
", "", ";"], [' ', ' ', ','], $log->detail ?? ''),
                $log->ip_address ?? '—',
                $log->acteur_type . '#' . $log->acteur_id,
            ]);
        }

        $csv      = implode("
", $rows);
        $filename = 'logs_securite_edupay_' . now()->format('Ymd_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
