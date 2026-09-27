<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    /** Reversement calcule, pas encore tente. Seul etat eligible a un rejeu automatique. */
    public const STATUT_CALCULEE = 'calculee';

    /** Appel AangaraaPay engage : le sort du virement n'est pas encore connu. */
    public const STATUT_EN_COURS = 'en_cours';

    /** Argent parti chez l'établissement. */
    public const STATUT_PRELEVEE = 'prelevee';

    /** L'argent est peut-être parti : vérification humaine, NE PAS renvoyer. */
    public const STATUT_A_VERIFIER = 'a_verifier';

    /** Échec définitif : reversement manuel à faire. */
    public const STATUT_ECHEC = 'echec';

    /** États qui demandent une intervention humaine. */
    public const STATUTS_A_TRAITER = [self::STATUT_A_VERIFIER, self::STATUT_ECHEC];

    protected $fillable = [
        'paiement_id', 'etablissement_id',
        'montant_transaction', 'taux', 'montant_commission', 'statut',
        'montant_net_etablissement', 'frais_aangaraa',
        'reference_reversement', 'reversed_at',
        'reversement_tente_le', 'reversement_erreur',
    ];

    protected $casts = [
        'reversement_tente_le' => 'datetime',
        'reversed_at'          => 'datetime',
    ];

    public function paiement() { return $this->belongsTo(Paiement::class); }
    public function etablissement() { return $this->belongsTo(Etablissement::class); }

    /** L'établissement a-t-il déjà été payé pour ce paiement ? */
    public function reversementEffectue(): bool
    {
        return $this->statut === self::STATUT_PRELEVEE;
    }

    /** Blocage visible côté admin : un humain doit intervenir. */
    public function requiertIntervention(): bool
    {
        return in_array($this->statut, self::STATUTS_A_TRAITER, true);
    }
}
