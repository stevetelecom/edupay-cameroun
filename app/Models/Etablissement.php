<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etablissement extends Model
{
    protected $casts = [
        'parent_etablissement_id' => 'integer',
        // Sans ce cast, la colonne 'date' revient en string brute : le
        // comparatif `?->toDateString()` de la commande
        // abonnements:synchroniser levait « Call to a member function
        // toDateString() on string » des la seconde execution.
        'abonnement_expire_le'   => 'date',
    ];

    use SoftDeletes;

    protected $fillable = [
        'code_etablissement', 'annee_scolaire_active', 'nom', 'logo', 'type', 'statut_juridique',
        'numero_agrement', 'nb_eleves', 'region', 'ville', 'quartier',
        'boite_postale', 'telephone', 'email', 'site_web',
        'mobile_money_principal', 'document_agrement', 'description',
        'statut', 'taux_commission', 'parent_etablissement_id',
        'numero_momo_reversement', 'operateur_momo_reversement',
        // Colonnes denormalisees de l'abonnement. Elles manquaient du
        // $fillable : les cinq Etablissement::...->update() qui les ecrivez
        // (Admin\AbonnementController x4, CheckAbonnement x1) etaient
        // silencieusement sans effet, l'etablissement restait donc affiche
        // « plan = aucun » alors qu'un abonnement actif existait (constate
        // en base le 27/09/2026). Model::update() jette en silence un
        // attribut non fillable, sans exception ni avertissement.
        'plan_abonnement', 'abonnement_expire_le',
    ];

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    /**
     * Abonnement courant = celui dont la periode a commence le plus
     * recemment.
     *
     * Triee sur date_debut puis id, et volontairement SANS filtre sur
     * `statut` : le statut est une valeur derivee des dates, resynchronisee
     * par abonnements:synchroniser. Filtrer dessus rendait le choix
     * aleatoire (abonnements #6 et #7 de l'etablissement 1, memes dates,
     * departages au created_at). A l'appelant de decider avec etat().
     */
    public function abonnementCourant(): ?Abonnement
    {
        return $this->abonnements()
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->first();
    }

    public function apprenants() { return $this->hasMany(Apprenant::class); }
    public function categoriesFrais() { return $this->hasMany(CategoriesFrais::class); }
    public function users() { return $this->hasMany(User::class); }
    public function commissions() { return $this->hasMany(Commission::class); }

    // ── Multi-sites (E12) ──────────────────────────────────
    public function sites()
    {
        return $this->hasMany(Etablissement::class, 'parent_etablissement_id');
    }

    public function siteParent()
    {
        return $this->belongsTo(Etablissement::class, 'parent_etablissement_id');
    }

    public function estSitePrincipal(): bool
    {
        return $this->parent_etablissement_id === null && $this->sites()->exists();
    }
}
