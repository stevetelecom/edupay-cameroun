<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\User;
use App\Support\AnneeScolaire;
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

        $sitePrincipal = $this->resoudreSitePrincipal($etablissement);

        // Audit J : le plan se verifie sur le SITE PRINCIPAL (le groupe est
        // l'entite facturable), pas sur le site secondaire consulte.
        $messagePlan = $this->verifierPlanMultiSites($sitePrincipal);
        if ($messagePlan) {
            return response()->json(['message' => $messagePlan['message']], $messagePlan['code']);
        }

        $sites = $sitePrincipal->sites()->get();
        $tousLesSites = $sites->prepend($sitePrincipal);

        $kpisParSite = $tousLesSites->map(function ($site) use ($sitePrincipal) {
            // Chaque site a sa propre annee active : on ne cumule pas les
            // encaissements d'annees differentes dans un meme compteur.
            $anneeDuSite = AnneeScolaire::active($site);

            $totalEncaisse = Paiement::whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $site->id))
                ->whereHas('fraisApprenant', fn ($q) => $q->where('annee_scolaire', $anneeDuSite))
                ->where('statut', 'valide')
                ->sum('montant');

            $siteFormate = $this->formaterSite($site);

            // Audit I : le mobile lit chaque site a plat (nom, ville...).
            // On garde la cle `site` pour les clients existants.
            return array_merge($siteFormate, [
                'site'           => $siteFormate,
                'est_principal'  => $site->id === $sitePrincipal->id,
                'annee_scolaire' => $anneeDuSite,
                'nb_apprenants'  => $site->apprenants()->count(),
                'total_encaisse' => (int) $totalEncaisse,
            ]);
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'site_principal'         => $this->formaterSite($sitePrincipal),
                'est_site_principal'     => $etablissement->id === $sitePrincipal->id,
                'sites'                  => $kpisParSite,
                'total_groupe_encaisse'  => (int) $kpisParSite->sum('total_encaisse'),
                'total_groupe_apprenants'=> (int) $kpisParSite->sum('nb_apprenants'),
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

        // $etablissement est ici le site principal (parent_etablissement_id
        // nul, verifie ci-dessus) : c'est bien lui qui porte le plan.
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

        // Audit J : l'index verifiait le plan, ni la modification ni la
        // suppression ne le faisaient -> un groupe retrograde en plan Basique
        // pouvait encore editer ou supprimer ses sites.
        $messagePlan = $this->verifierPlanMultiSites($etablissementCourant);
        if ($messagePlan) {
            return response()->json(['message' => $messagePlan['message']], $messagePlan['code']);
        }

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

        $messagePlan = $this->verifierPlanMultiSites($etablissementCourant);
        if ($messagePlan) {
            return response()->json(['message' => $messagePlan['message']], $messagePlan['code']);
        }

        // Audit I/K : on ne supprime pas un site qui porte des donnees
        // pedagogiques ou financieres. Le soft delete laissait behind des
        // comptes actifs rattaches a un etablissement invisible, et les
        // indicateurs du groupe perdaient des encaissements sans trace.
        $nbApprenants = $site->apprenants()->count();
        $nbPaiements  = Paiement::whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $site->id))->count();

        if ($nbApprenants > 0 || $nbPaiements > 0) {
            return response()->json([
                'message' => 'Le site « ' . $site->nom . ' » ne peut pas être supprimé : il compte '
                    . $nbApprenants . ' apprenant(s) et ' . $nbPaiements
                    . ' paiement(s). Suspendez le site ou transférez ses données avant de le supprimer.',
                'data'    => [
                    'nb_apprenants' => $nbApprenants,
                    'nb_paiements'  => $nbPaiements,
                ],
            ], 409);
        }

        $nom = $site->nom;

        // Aucun compte actif ne doit rester rattache a un site supprime
        // (convention de suspension : `suspendu` + motif + date).
        $site->users()->where('suspendu', false)->update([
            'suspendu'        => true,
            'suspendu_raison' => 'Site « ' . $nom . ' » supprimé du groupe.',
            'suspendu_at'     => now(),
        ]);

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

    /**
     * Verrou multi-sites (audit J).
     *
     * @param  Etablissement  $sitePrincipal  entite facturable du groupe
     * @return array|null {message, code} a renvoyer tel quel, ou null si autorise
     */
    private function verifierPlanMultiSites(Etablissement $sitePrincipal): ?array
    {
        // Source de vérité unique : Etablissement::abonnementCourant()
        // (tri date_debut puis id, AUCUN filtre sur `statut`).
        //
        // Ce code faisait whereIn('statut', [actif, grace_period])->latest(),
        // c'est-à-dire un filtre sur un statut DERIVE des dates et un tri
        // created_at. C'est exactement l'erreur que documente le middleware
        // CheckAbonnement : deux abonnements inseres le meme jour étaient
        // departages au hasard, et une ligne repassée 'expire' par une visite
        // anterieure etait traitée comme « pas d'abonnement » alors que le
        // middleware, lui, la prenait en compte. Consequence directe depuis le
        // CDC S0 #3 : le plan affiche pouvait ne pas etre celui qui a fixe le
        // taux de commission preleve.
        $abonnement = $sitePrincipal->abonnementCourant();

        // Pas d'abonnement actif, ou plan inconnu : on refuse par defaut.
        // L'ancien code laissait passer l'absence d'abonnement, ce qui
        // ouvrait le multi-sites a un etablissement sans plan payant.
        $planConfig = $abonnement ? (Abonnement::PLANS[$abonnement->plan] ?? null) : null;

        if (! $planConfig) {
            return [
                'message' => 'La gestion multi-sites nécessite un abonnement actif au plan Standard ou Premium. '
                    . 'Aucun abonnement en cours ne vous y autorise.',
                'code'    => 403,
            ];
        }

        if (! $planConfig['multi_sites']) {
            return [
                'message' => "La gestion multi-sites n'est pas disponible avec votre plan "
                    . ucfirst($abonnement->plan) . ". Passez au plan Standard ou Premium pour accéder à cette fonctionnalité.",
                'code'    => 403,
            ];
        }

        return null;
    }

    private function autoriser(): void
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, __('api.acces_etablissement'));
        }
    }
}
