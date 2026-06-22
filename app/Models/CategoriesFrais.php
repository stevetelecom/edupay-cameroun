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

    protected $casts = [
        'fractionnable' => 'boolean',
        'actif'         => 'boolean',
    ];

    public function etablissement()
    {
        return $this->belongsTo(Etablissement::class);
    }

    // 'categorie_frais_id' explicite — sinon Laravel devine 'categories_frais_id' (faux)
    public function echeanciers()
    {
        return $this->hasMany(Echeancier::class, 'categorie_frais_id')
                    ->orderBy('numero_tranche');
    }

    public function fraisApprenants()
    {
        return $this->hasMany(FraisApprenant::class, 'categorie_frais_id');
    }
}
