<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etablissement extends Model
{
    protected $casts = [
        'parent_etablissement_id' => 'integer',
    ];

    use SoftDeletes;

    protected $fillable = [
        'code_etablissement', 'nom', 'logo', 'type', 'statut_juridique',
        'numero_agrement', 'nb_eleves', 'region', 'ville', 'quartier',
        'boite_postale', 'telephone', 'email', 'site_web',
        'mobile_money_principal', 'document_agrement', 'description',
        'statut', 'taux_commission', 'parent_etablissement_id',
        'numero_momo_reversement', 'operateur_momo_reversement',
    ];

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
