<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SiteController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des sites (multi-sites) du groupe, avec KPIs par site.
     * Réservé aux plans multi-sites (Standard / Premium).
     */
    public function index(): JsonResponse
    {
        $etablissement = auth()->user()->etablissement;
        $this->autoriser();

        $messagePlan = $this->verifierPlanMultiSites($etablissement);
        if ($messagePlan) {
            return response()->json(['message' => $messagePlan['message']], $messagePlan['code']);
        }

        $sitePrincipal = $this->resoudreSitePrincipal($etablissement);

        $sites = $sitePrincipal->sites()->get();
        $tousLesSites = $sites->prepend($sitePrincipal);

        $kpisParSite = $tousLesSites->map(function ($site) {
            $totalEncaisse = Paiement::whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $site->id))
                ->where('statut', 'valide')
                ->sum('montant');

            return [
                'site'          => $this->formaterSite($site),
                'nb_apprenants' => $site->apprenants()->count(),
                'total_encaisse'=> (int) $totalEncaisse,
            ];
        })->values();

        return response()->json([
            'data' => [
                'site_principal'         => $this->formaterSite($sitePrincipal),
                'est_site_principal'     => $etablissement->id === $sitePrincipal->id,
                'sites'                  => $kpisParSite,
                'total_groupe_encaisse'  => $kpisParSite->sum('total_encaisse'),
                'total_groupe_apprenants'=> $kpisParSite->sum('nb_apprenants'),
            ],
        ]);
    }

    /**
     * Crée un site secondaire (réservé au directeur du site principal, plan multi-sites).
     */
    public function store(Request $request): JsonResponse
    {
        $etablissement = auth()->user()->etablissement;
        $this->autoriser();

        abort_unless(
            auth()->user()->hasRole('directeur') && $etablissement->parent_etablissement_id === null,
            403,
            'Seul le directeur du site principal peut ajouter un nouveau site.'
        );

        $messagePlan = $this->verifierPlanMultiSites($etablissement);
        if ($messagePlan) {
            return response()->json(['message' => $messagePlan['message']], $messagePlan['code']);
        }

        $validated = $request->validate([
            'nom'              => ['required', 'string', 'max:255'],
            'ville'            => ['required', 'string', 'max:255'],
            'quartier'         => ['nullable', 'string', 'max:255'],
            'telephone'        => ['required', 'string', 'max:30'],
            'email'            => ['required', 'email', 'max:255'],
            'directeur_prenom' => ['required', 'string', 'max:255'],
            'directeur_nom'    => ['required', 'string', 'max:255'],
            'directeur_email'  => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [
            'directeur_email.unique' => 'Cet email est déjà utilisé par un autre compte EduPay.',
        ]);

        $nouveauSite = Etablissement::create([
            'code_etablissement'      => 'EP-' . strtoupper(uniqid()),
            'nom'                     => $validated['nom'],
            'type'                    => $etablissement->type,
            'statut_juridique'        => $etablissement->statut_juridique,
            'region'                  => $etablissement->region,
            'ville'                   => $validated['ville'],
            'quartier'                => $validated['quartier'] ?? null,
            'telephone'               => $validated['telephone'],
            'email'                   => $validated['email'],
            'statut'                  => 'actif',
            'taux_commission'         => $etablissement->taux_commission,
            'parent_etablissement_id' => $etablissement->id,
        ]);

        $directeur = User::create([
            'prenom'           => $validated['directeur_prenom'],
            'nom'              => $validated['directeur_nom'],
            'email'            => $validated['directeur_email'],
            'password'         => Hash::make(str()->random(14)),
            'etablissement_id' => $nouveauSite->id,
        ]);
        $directeur->assignRole('directeur');

        return response()->json([
            'message' => 'Site « ' . $nouveauSite->nom . ' » créé avec succès. Un compte directeur a été généré pour ' . $directeur->email . '.',
            'data'    => $this->formaterSite($nouveauSite),
        ], 201);
    }

    /**
     * Modifie un site secondaire (réservé au directeur du groupe).
     */
    public function update(Request $request, Etablissement $site): JsonResponse
    {
        $etablissementCourant = auth()->user()->etablissement;
        $this->autoriser();

        abort_unless(
            auth()->user()->hasRole('directeur') && $site->parent_etablissement_id === $etablissementCourant->id,
            403,
            'Vous ne pouvez modifier que les sites secondaires de votre groupe.'
        );

        $validated = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'ville'     => ['required', 'string', 'max:255'],
            'quartier'  => ['nullable', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:30'],
            'email'     => ['required', 'email', 'max:255'],
        ]);

        $site->update($validated);

        return response()->json([
            'message' => 'Site « ' . $site->nom . ' » modifié avec succès.',
            'data'    => $this->formaterSite($site->fresh()),
        ]);
    }

    /**
     * Supprime un site secondaire (réservé au directeur du groupe).
     */
    public function destroy(Request $request, Etablissement $site): JsonResponse
    {
        $etablissementCourant = auth()->user()->etablissement;
        $this->autoriser();

        abort_unless(
            auth()->user()->hasRole('directeur') && $site->parent_etablissement_id === $etablissementCourant->id,
            403,
            'Vous ne pouvez supprimer que les sites secondaires de votre groupe.'
        );

        $nom = $site->nom;
        $site->delete();

        return response()->json([
            'message' => 'Site « ' . $nom . ' » supprimé avec succès.',
        ]);
    }

    private function formaterSite(Etablissement $e): array
    {
        return [
            'id'                => $e->id,
            'code_etablissement' => $e->code_etablissement,
            'nom'               => $e->nom,
            'type'              => $e->type,
            'region'            => $e->region,
            'ville'             => $e->ville,
            'quartier'          => $e->quartier,
            'telephone'         => $e->telephone,
            'email'             => $e->email,
            'statut'            => $e->statut,
            'logo'              => $e->logo ? asset('storage/' . $e->logo) : null,
            'parent_etablissement_id' => $e->parent_etablissement_id,
        ];
    }

    private function resoudreSitePrincipal(Etablissement $etablissement): Etablissement
    {
        return $etablissement->parent_etablissement_id
            ? $etablissement->siteParent
            : $etablissement;
    }

    private function verifierPlanMultiSites(Etablissement $etablissement): ?array
    {
        $abonnement = Abonnement::where('etablissement_id', $etablissement->id)
            ->whereIn('statut', ['actif', 'grace_period'])
            ->latest()->first();

        if ($abonnement) {
            $planConfig = Abonnement::PLANS[$abonnement->plan] ?? null;
            if ($planConfig && ! $planConfig['multi_sites']) {
                return [
                    'message' => "La gestion multi-sites n'est pas disponible avec votre plan "
                        . ucfirst($abonnement->plan) . ". Passez au plan Standard ou Premium pour accéder à cette fonctionnalité.",
                    'code'    => 403,
                ];
            }
        }

        return null;
    }

    private function autoriser(): void
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }
    }
}
