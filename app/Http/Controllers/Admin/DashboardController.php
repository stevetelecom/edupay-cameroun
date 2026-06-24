<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Dashboard KPIs globaux — Super Admin.
     * Données à zéro tant que les modules métier ne sont pas branchés.
     */
    public function index()
    {
        return view('admin.dashboard', [
            'volumeMois'             => 0,
            'commissionsMois'        => 0,
            'etablissementsActifs'   => 0,
            'transactionsMois'       => 0,
            'repartitionMoyens'      => collect(),
            'tauxRecouvrement'       => 0,
            'derniersEtablissements' => collect(),
            'dernieresTransactions'  => collect(),
            'tauxCommission'         => 0.025,
            'pageTitle'              => 'Tableau de bord — Super Admin EduPay',
            'mois'                   => now()->translatedFormat('F Y'),
        ]);
    }
}
