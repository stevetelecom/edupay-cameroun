<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Echeancier extends Model
{
    protected $fillable = [
        'categorie_frais_id', 'numero_tranche', 'montant', 'date_echeance', 'libelle',
    ];

    protected $casts = ['date_echeance' => 'date'];

    public function categorieFrais() { return $this->belongsTo(CategoriesFrais::class); }
    public function paiements() { return $this->hasMany(Paiement::class); }
}
