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

    /**
     * Soldes de l'apprenant pour une annee scolaire (audit E).
     *
     * Utilise la relation `frais` deja chargee quand elle l'est (liste et
     * detail de l'etablissement la chargent) : aucun appel SQL supplementaire,
     * donc pas de N+1. Sinon elle est chargee a la volee.
     *
     * @return array{total_du:float, total_paye:float, solde_du:float, nb_frais:int}
     */
    public function soldes(?string $anneeScolaire = null): array
    {
        $frais = $this->relationLoaded('frais')
            ? $this->frais
            : $this->frais()->get();

        if ($anneeScolaire !== null) {
            $frais = $frais->where('annee_scolaire', $anneeScolaire);
        }

        $totalDu   = (float) $frais->sum('montant_total');
        $totalPaye = (float) $frais->sum('montant_paye');

        return [
            'total_du'   => $totalDu,
            'total_paye' => $totalPaye,
            'solde_du'   => max(0, round($totalDu - $totalPaye, 2)),
            'nb_frais'   => $frais->count(),
        ];
    }

    /**
     * Statut de rattachement (audit F).
     *
     * Le rejet d'un rattachement SUPPRIME l'apprenant (soft delete) : il n'existe
     * donc pas d'etat « rejete » en base. Les deux seuls etats observables sont
     * derivés de `source` + `valide_par_etablissement` :
     *   en_attente : cree par la famille, en attente de validation (boutons
     *                Valider / Rejeter du back-office) ;
     *   valide     : rattache valide par l'etablissement ;
     *   actif      : apprenant saisi directement par l'etablissement.
     */
    public function statutRattachement(): string
    {
        // Seuls les apprenants crees par la famille passent par une validation.
        if ($this->source !== 'payeur') {
            return 'actif';
        }

        return $this->valide_par_etablissement ? 'valide' : 'en_attente';
    }
}
