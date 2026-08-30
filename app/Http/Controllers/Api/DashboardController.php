<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaiementResource;
use App\Models\Paiement;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Aperçu global du payeur : enfants, soldes, premier impayé, derniers paiements,
     * notifications non lues, liste des établissements actifs (miroir du web).
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $apprenants = $user->apprenants()
            ->with(['frais.categorieFrais', 'etablissement'])
            ->get();

        $totalDu = $apprenants->sum(fn ($a) => $a->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye));
        $totalPaye = $apprenants->sum(fn ($a) => $a->frais->sum('montant_paye'));
        $nbEnfantsDus = $apprenants->filter(fn ($a) => $a->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye) > 0)->count();

        $premierFraisImpaye = $apprenants
            ->flatMap(fn ($a) => $a->frais)
            ->first(fn ($f) => $f->statut !== 'regle');

        $derniersPaiements = Paiement::with(['apprenant', 'fraisApprenant.categorieFrais'])
            ->where('user_id', $user->id)
            ->latest('date_paiement')
            ->take(5)
            ->get();

        $nbRecus = Paiement::where('user_id', $user->id)->where('statut', 'valide')->count();

        $estSolo = in_array($user->profil, ['eleve', 'etudiant']);
        $monDossier = $estSolo ? $apprenants->first() : null;
        $totalGlobal = $totalDu + $totalPaye;
        $pourcentageGlobal = $totalGlobal > 0 ? round(($totalPaye / $totalGlobal) * 100) : 0;
        $premierFraisImpayeSolo = $monDossier ? $monDossier->frais->first(fn ($f) => $f->statut !== 'regle') : null;

        $etablissements = \App\Models\Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'code_etablissement', 'logo']);

        $notifications = \App\Models\NotificationPayeur::where('user_id', $user->id)
            ->whereNull('lu_at')
            ->latest()
            ->get();

        return response()->json([
            'data' => [
                'apprenants' => $apprenants->map(fn ($a) => [
                    'id'                 => $a->id,
                    'nom'                => $a->nom,
                    'prenom'             => $a->prenom,
                    'matricule'          => $a->matricule,
                    'classe'             => $a->classe,
                    'annee_scolaire'     => $a->annee_scolaire,
                    'statut_paiement'    => $a->statut_paiement,
                    'valide_par_etablissement' => (bool) $a->valide_par_etablissement,
                    'etablissement'      => [
                        'id'        => $a->etablissement?->id,
                        'nom'       => $a->etablissement?->nom,
                        'ville'     => $a->etablissement?->ville,
                        'logo'      => $a->etablissement?->logo ? asset('storage/' . $a->etablissement->logo) : null,
                    ],
                    'total_du'           => $a->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye),
                    'total_paye'         => $a->frais->sum('montant_paye'),
                    'a_impayes'          => $a->frais->sum(fn ($f) => $f->montant_total - $f->montant_paye) > 0,
                ]),
                'total_du'               => $totalDu,
                'total_paye'             => $totalPaye,
                'nb_enfants_dus'         => $nbEnfantsDus,
                'nb_recus'               => $nbRecus,
                'est_solo'               => $estSolo,
                'pourcentage_global'     => $pourcentageGlobal,
                'premier_frais_impaye'   => $this->formaterFraisApercu($premierFraisImpaye),
                'premier_frais_impaye_solo' => $this->formaterFraisApercu($premierFraisImpayeSolo),
                'derniers_paiements'     => PaiementResource::collection($derniersPaiements),
                'notifications_non_lues' => $notifications->map(fn ($n) => [
                    'id'        => $n->id,
                    'type'      => $n->type,
                    'contenu'   => $n->contenu,
                    'created_at' => $n->created_at?->toISOString(),
                ])->values(),
                'etablissements'         => $etablissements->map(fn ($e) => [
                    'id'                => $e->id,
                    'nom'               => $e->nom,
                    'ville'             => $e->ville,
                    'type'              => $e->type,
                    'code_etablissement'=> $e->code_etablissement,
                    'logo'              => $e->logo ? asset('storage/' . $e->logo) : null,
                ]),
            ],
        ]);
    }

    /**
     * Marque une notification comme lue.
     */
    public function marquerNotificationLue(\App\Models\NotificationPayeur $notification): JsonResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403, 'Accès non autorisé à cette notification.');
        $notification->update(['lu_at' => now()]);

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    private function formaterFraisApercu($frais): ?array
    {
        if (! $frais) {
            return null;
        }

        return [
            'id'             => $frais->id,
            'categorie'      => $frais->categorieFrais?->nom,
            'montant_total'  => (float) $frais->montant_total,
            'montant_paye'   => (float) $frais->montant_paye,
            'reste'          => (float) ($frais->montant_total - $frais->montant_paye),
            'statut'         => $frais->statut,
            'annee_scolaire' => $frais->annee_scolaire,
            'apprenant'      => $frais->apprenant ? ($frais->apprenant->prenom . ' ' . $frais->apprenant->nom) : null,
        ];
    }
}
