<?php

namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function marquerNotificationLue(\App\Models\NotificationPayeur $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->update(['lu_at' => now()]);
        return back();
    }

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

        // Vue Famille (parent, plusieurs enfants) vs Vue Solo (élève/étudiant, son propre dossier)
        $estSolo = in_array($user->profil, ['eleve', 'etudiant']);

        // En vue solo, l'apprenant "soi-même" est le premier (et normalement unique) apprenant rattaché
        $monDossier = $estSolo ? $apprenants->first() : null;

        // % global réglé (utilisé en vue solo pour le KPI "Solde réglé")
        $totalGlobal = $totalDu + $totalPaye;
        $pourcentageGlobal = $totalGlobal > 0 ? round(($totalPaye / $totalGlobal) * 100) : 0;

        // F05 — Premier frais impayé du dossier solo
        $premierFraisImpayeSolo = $monDossier
            ? $monDossier->frais->first(fn ($f) => $f->statut !== 'regle')
            : null;

        
        $etablissements = \App\Models\Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement', 'logo']);

        $notifications = \App\Models\NotificationPayeur::where('user_id', Auth::id())
            ->whereNull('lu_at')
            ->latest()
            ->get();

        return view('payeur.dashboard', [
            'notifications' => $notifications,
            'apprenants'          => $apprenants,
            'totalDu'             => $totalDu,
            'totalPaye'           => $totalPaye,
            'nbEnfantsDus'        => $nbEnfantsDus,
            'premierFraisImpaye'  => $premierFraisImpaye,
            'derniersPaiements'   => $derniersPaiements,
            'nbRecus'             => $nbRecus,
            'estSolo'             => $estSolo,
            'monDossier'          => $monDossier,
            'pourcentageGlobal'   => $pourcentageGlobal,
            'premierFraisImpayeSolo' => $premierFraisImpayeSolo,
            'pageTitle'           => 'Mon espace — EduPay',
            'etablissements'      => $etablissements,
        ]);
    }
}
