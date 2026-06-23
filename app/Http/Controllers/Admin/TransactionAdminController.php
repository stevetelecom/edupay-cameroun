<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class TransactionAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Paiement::with([
                'apprenant',
                'fraisApprenant.categorieFrais',
                'fraisApprenant.etablissement',
            ])
            ->orderByDesc('created_at');

        // Filtre operateur / onglet
        if ($request->filled('operateur')) {
            $query->where('operateur', $request->operateur);
        }

        // Filtre statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre recherche
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function($sub) use ($q) {
                $sub->where('reference', 'like', "%{$q}%")
                    ->orWhere('telephone_paiement', 'like', "%{$q}%");
            });
        }

        // Export CSV
        if ($request->has('export')) {
            return $this->exportCsv($query->get());
        }

        $paiements = $query->paginate(20)->withQueryString();

        // KPIs
        $stats = [
            'total_mois'   => Paiement::where('statut', 'valide')
                                ->whereMonth('created_at', now()->month)
                                ->sum('montant'),
            'nb_mois'      => Paiement::where('statut', 'valide')
                                ->whereMonth('created_at', now()->month)
                                ->count(),
            'en_attente'   => Paiement::where('statut', 'en_attente')->count(),
            'echecs'       => Paiement::where('statut', 'echoue')
                                ->whereMonth('created_at', now()->month)
                                ->count(),
        ];

        return view('admin.transactions.index', compact('paiements', 'stats'));
    }

    public function show(Paiement $paiement)
    {
        $paiement->load([
            'apprenant',
            'fraisApprenant.categorieFrais',
            'fraisApprenant.etablissement',
            'user',
        ]);
        return view('admin.transactions.show', compact('paiement'));
    }

    private function exportCsv($paiements)
    {
        $rows   = [];
        $rows[] = implode(';', [
            'Reference', 'Date', 'Ecole', 'Apprenant',
            'Montant FCFA', 'Operateur', 'Telephone', 'Statut'
        ]);

        foreach ($paiements as $p) {
            $ecole     = $p->fraisApprenant->etablissement->nom ?? '—';
            $apprenant = $p->apprenant
                ? ($p->apprenant->nom . ' ' . $p->apprenant->prenom)
                : '—';
            $rows[] = implode(';', [
                $p->reference,
                $p->created_at->format('d/m/Y H:i'),
                $ecole,
                $apprenant,
                $p->montant,
                $p->operateur ?? '—',
                $p->telephone_paiement ?? '—',
                $p->statut,
            ]);
        }

        $csv = implode("
", $rows);
        $filename = 'transactions_edupay_' . now()->format('Ymd_His') . '.csv';

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }
}
