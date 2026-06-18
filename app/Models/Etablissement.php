<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etablissement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code_etablissement', 'nom', 'type', 'statut_juridique',
        'numero_agrement', 'nb_eleves', 'region', 'ville', 'quartier',
        'boite_postale', 'telephone', 'email', 'site_web',
        'mobile_money_principal', 'document_agrement', 'description',
        'statut', 'taux_commission',
    ];

    public function apprenants() { return $this->hasMany(Apprenant::class); }
    public function categoriesFrais() { return $this->hasMany(CategoriesFrais::class); }
    public function users() { return $this->hasMany(User::class); }
    public function commissions() { return $this->hasMany(Commission::class); }
}
