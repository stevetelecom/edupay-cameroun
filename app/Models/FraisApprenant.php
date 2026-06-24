<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraisApprenant extends Model
{
    protected $table = 'frais_apprenant';

    protected $fillable = [
        'apprenant_id', 'categorie_frais_id',
        'montant_total', 'montant_paye', 'statut', 'annee_scolaire',
    ];

    public function apprenant() { return $this->belongsTo(Apprenant::class); }
    public function categorieFrais() { return $this->belongsTo(CategoriesFrais::class); }
    public function paiements() { return $this->hasMany(Paiement::class); }
}
