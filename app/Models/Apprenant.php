<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apprenant extends Model
{
    protected $casts = [
        'etablissement_id' => 'integer',
        'user_id' => 'integer',
    ];

    use SoftDeletes;

    protected $fillable = [
        'etablissement_id', 'nom', 'prenom', 'classe',
        'matricule', 'date_naissance', 'sexe', 'statut_paiement', 'actif',
        'source', 'valide_par_etablissement',
    ];

    public function etablissement() { return $this->belongsTo(Etablissement::class); }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'user_apprenant')
                    ->withPivot('lien')->withTimestamps();
    }

    public function frais() { return $this->hasMany(FraisApprenant::class); }
    public function paiements() { return $this->hasMany(Paiement::class); }
}
