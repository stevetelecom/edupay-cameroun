<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\Reclamation;
use App\Models\Remboursement;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    /**
     * Page d'accueil des exports réglementaires (S08).
     */
    public function index(Request $request)
    {
        $moisDefaut       = now()->format('Y-m');
        $trimestreDefaut  = now()->year . '-T' . (int) ceil(now()->month / 3);

        // Liste des 4 derniers trimestres pour le select
        $trimestres = [];
        for ($i = 0; $i < 4; $i++) {
            $date  = now()->subMonths($i * 3);
            $q     = (int) ceil($date->month / 3);
            $key   = $date->year . '-T' . $q;
            $trimestres[$key] = 'T' . $q . ' ' . $date->year;
        }
        $trimestres = array_unique($trimestres);

        return view('admin.exports.index', [
            'pageTitle'       => 'Exports réglementaires — Super Admin EduPay',
            'moisDefaut'      => $moisDefaut,
            'trimestreDefaut' => $trimestreDefaut,
            'trimestres'      => $trimestres,
        ]);
    }

    /**
     * S08 — Rapport mensuel des flux financiers (conforme directives BEAC).
     */
    public function rapportMensuelBeac(Request $request)
    {
        $request->validate([
            'mois' => ['nullable', 'date_format:Y-m'],
        ]);

        $mois  = $request->input('mois', now()->format('Y-m'));
        $debut = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
        $fin   = $debut->copy()->endOfMonth();

        $data = $this->donneesPeriode($debut, $fin);
        $data['periodeLabel'] = 'Mois de ' . $debut->translatedFormat('F Y');
        $data['mois']         = $mois;

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'EXPORT_RAPPORT_BEAC',
            'Génération du rapport mensuel BEAC — ' . $mois,
            $request,
            'INFO'
        );

        $pdf = Pdf::loadView('pdf.export-mensuel-beac', $data);

        return $pdf->download('rapport-mensuel-beac-' . $mois . '.pdf');
    }

    /**
     * S08 — Déclaration trimestrielle COBAC (volume, commissions, anomalies).
     */
    public function declarationCobac(Request $request)
    {
        $request->validate([
            'trimestre' => ['nullable', 'regex:/^\d{4}-T[1-4]$/'],
        ]);

        $trimestre = $request->input('trimestre', now()->year . '-T' . (int) ceil(now()->month / 3));
        [$annee, $tLabel] = explode('-T', $trimestre);
        $numTrimestre      = (int) $tLabel;
        $moisDebut         = ($numTrimestre - 1) * 3 + 1;

        $debut = Carbon::create((int) $annee, $moisDebut, 1)->startOfMonth();
        $fin   = $debut->copy()->addMonths(2)->endOfMonth();

        $data = $this->donneesPeriode($debut, $fin);
        $data['periodeLabel'] = 'T' . $numTrimestre . ' ' . $annee;
        $data['trimestre']    = $trimestre;

        // Anomalies — exigence COBAC
        $data['nbEchecs'] = Paiement::where('statut', 'echoue')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $data['nbRemboursements'] = Remboursement::whereBetween('created_at', [$debut, $fin])->count();

        $data['nbReclamations'] = Reclamation::whereBetween('created_at', [$debut, $fin])->count();
        $data['nbReclamationsOuvertes'] = Reclamation::where('statut', 'ouvert')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'EXPORT_DECLARATION_COBAC',
            'Génération de la déclaration trimestrielle COBAC — ' . $trimestre,
            $request,
            'INFO'
        );

        $pdf = Pdf::loadView('pdf.export-cobac', $data);

        return $pdf->download('declaration-cobac-' . $trimestre . '.pdf');
    }

    /**
     * Agrégats communs (volume, commissions, répartition opérateur, top établissements)
     * pour une période [debut, fin] donnée.
     */
    private function donneesPeriode(Carbon $debut, Carbon $fin): array
    {
        $volumeTotal = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->sum('montant');

        $nbTransactions = Paiement::where('statut', 'valide')
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $commissionsTotal = Commission::whereBetween('created_at', [$debut, $fin])
            ->sum('montant_commission');

        $repartitionOperateur = Transaction::where('statut', 'success')
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('operateur, COUNT(*) as nb, SUM(montant) as volume')
            ->groupBy('operateur')
            ->get();

        $topEtablissements = Commission::whereBetween('created_at', [$debut, $fin])
            ->selectRaw('etablissement_id, SUM(montant_transaction) as volume, SUM(montant_commission) as commission')
            ->groupBy('etablissement_id')
            ->orderByDesc('volume')
            ->limit(5)
            ->with('etablissement')
            ->get();

        $etablissementsActifs = Etablissement::where('statut', 'actif')->count();

        return [
            'volumeTotal'           => $volumeTotal,
            'nbTransactions'        => $nbTransactions,
            'commissionsTotal'      => $commissionsTotal,
            'repartitionOperateur'  => $repartitionOperateur,
            'topEtablissements'     => $topEtablissements,
            'etablissementsActifs'  => $etablissementsActifs,
        ];
    }
}
