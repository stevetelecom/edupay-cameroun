<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $apprenants = $user->apprenants()
            ->with(['frais.categorieFrais', 'etablissement'])
            ->get();

        $totalDu = $apprenants->sum(function ($apprenant) {
            return $apprenant->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye);
        });

        $totalPaye = $apprenants->sum(function ($apprenant) {
            return $apprenant->frais->sum('montant_paye');
        });

        $nbEnfantsDus = $apprenants->filter(function ($apprenant) {
            return $apprenant->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye) > 0;
        })->count();

        // Premier frais impayé tous enfants confondus — utilisé par le bouton "Payer maintenant"
        $premierFraisImpaye = $apprenants
            ->flatMap(fn ($apprenant) => $apprenant->frais)
            ->first(fn ($frais) => $frais->statut !== 'regle');

        $derniersPaiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', $user->id)
            ->latest('date_paiement')
            ->take(5)
            ->get();

        $nbRecus = Paiement::where('user_id', $user->id)
            ->where('statut', 'valide')
            ->count();

        return view('payeur.dashboard', [
            'apprenants'          => $apprenants,
            'totalDu'             => $totalDu,
            'totalPaye'           => $totalPaye,
            'nbEnfantsDus'        => $nbEnfantsDus,
            'premierFraisImpaye'  => $premierFraisImpaye,
            'derniersPaiements'   => $derniersPaiements,
            'nbRecus'             => $nbRecus,
            'pageTitle'           => 'Mon espace — EduPay',
        ]);
    }
}
