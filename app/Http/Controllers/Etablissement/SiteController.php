<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        $etablissement = Auth::user()->etablissement;
        $sitePrincipal = $this->resoudreSitePrincipal($etablissement);

        $sites = $sitePrincipal->sites()->get();
        $tousLesSites = $sites->prepend($sitePrincipal);

        $kpisParSite = $tousLesSites->map(function ($site) {
            $totalEncaisse = Paiement::whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $site->id))
                ->where('statut', 'valide')
                ->sum('montant');

            return [
                'etablissement'  => $site,
                'nb_apprenants'  => $site->apprenants()->count(),
                'total_encaisse' => $totalEncaisse,
            ];
        });

        return view('etablissement.sites.index', [
            'sitePrincipal'         => $sitePrincipal,
            'kpisParSite'           => $kpisParSite,
            'totalGroupeEncaisse'   => $kpisParSite->sum('total_encaisse'),
            'totalGroupeApprenants' => $kpisParSite->sum('nb_apprenants'),
            'estSitePrincipal'      => $etablissement->id === $sitePrincipal->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $etablissement = Auth::user()->etablissement;

        abort_unless(
            Auth::user()->hasRole('directeur') && $etablissement->parent_etablissement_id === null,
            403,
            'Seul le directeur du site principal peut ajouter un nouveau site.'
        );

        $validated = $request->validate([
            'nom'              => 'required|string|max:255',
            'ville'            => 'required|string|max:255',
            'quartier'         => 'nullable|string|max:255',
            'telephone'        => 'required|string|max:30',
            'email'            => 'required|email|max:255',
            'directeur_prenom' => 'required|string|max:255',
            'directeur_nom'    => 'required|string|max:255',
            'directeur_email'  => 'required|email|max:255|unique:users,email',
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

        // TODO : déclencher InvitationUtilisateurMail vers $directeur->email (mot de passe à définir)

        return redirect()->route('etablissement.sites.index')
            ->with('success', 'Site « ' . $nouveauSite->nom . ' » créé avec succès. Un compte directeur a été généré pour ' . $directeur->email . '.');
    }

    public function update(Request $request, Etablissement $site): RedirectResponse
    {
        $etablissementCourant = Auth::user()->etablissement;

        abort_unless(
            Auth::user()->hasRole('directeur') && $site->parent_etablissement_id === $etablissementCourant->id,
            403,
            'Vous ne pouvez modifier que les sites secondaires de votre groupe.'
        );

        $validated = $request->validate([
            'nom'       => 'required|string|max:255',
            'ville'     => 'required|string|max:255',
            'quartier'  => 'nullable|string|max:255',
            'telephone' => 'required|string|max:30',
            'email'     => 'required|email|max:255',
        ]);

        $site->update($validated);

        return redirect()->route('etablissement.sites.index')
            ->with('success', 'Site « ' . $site->nom . ' » modifié avec succès.');
    }

    public function destroy(Etablissement $site): RedirectResponse
    {
        $etablissementCourant = Auth::user()->etablissement;

        abort_unless(
            Auth::user()->hasRole('directeur') && $site->parent_etablissement_id === $etablissementCourant->id,
            403,
            'Vous ne pouvez supprimer que les sites secondaires de votre groupe.'
        );

        $nom = $site->nom;
        $site->delete();

        return redirect()->route('etablissement.sites.index')
            ->with('success', 'Site « ' . $nom . ' » supprimé avec succès.');
    }

    private function resoudreSitePrincipal(Etablissement $etablissement): Etablissement
    {
        // Un établissement sans groupe est traité comme son propre "site principal"
        // potentiel : ça permet d'afficher la page Multi-sites avec le bouton
        // "+ Ajouter un site" même quand aucun site n'existe encore.
        return $etablissement->parent_etablissement_id
            ? $etablissement->siteParent
            : $etablissement;
    }
}
