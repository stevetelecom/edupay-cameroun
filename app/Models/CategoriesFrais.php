<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriesFrais extends Model
{
    protected $table = 'categories_frais';

    protected $fillable = [
        'etablissement_id', 'nom', 'description', 'montant_total',
        'fractionnable', 'nb_tranches_max', 'actif', 'annee_scolaire',
    ];

    public function etablissement() { return $this->belongsTo(Etablissement::class); }
    public function echeanciers() { return $this->hasMany(Echeancier::class)->orderBy('numero_tranche'); }
    public function fraisApprenants() { return $this->hasMany(FraisApprenant::class); }
}
