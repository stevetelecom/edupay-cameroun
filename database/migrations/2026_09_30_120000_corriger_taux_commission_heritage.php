<?php

use App\Services\AangaraaPayService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Correction du taux fige sur les commissions anterieures.
 *
 * Le taux enregistre sur chaque commission venait de
 * `etablissements.taux_commission`, une colonne decorative figee a 0,005 qui
 * n'entrait dans aucun calcul : le prelevement reellement applique etait bien
 * le taux global (2,2 % AangaraaPay + 0,1 % EduPay = 2,3 %), mais la page
 * Commissions affichait 0,5 % pour ces lignes.
 *
 * Verifie sur les paiements 34 et 50 XAF / 100 XAF concernes :
 *   - frais_aangaraa = 1 et 2 = floor(montant * 0,022)  -> conforme ;
 *   - marge = 1 et 1 = ceil(montant * 0,023) - frais_aangaraa -> conforme.
 * Les montants sont donc justes : seul le champ `taux` est errone, et on ne
 * touche qu'a lui. Aucun montant, date, reference ni audit n'est reecrit.
 *
 * Selection volontairement etroite : seules les lignes portant encore
 * l'ancienne valeur decorative 0,005 sont corrigees. Les commissions creees
 * depuis la refactorisation (taux par profil d'abonnement) sont laissees
 * intactes.
 */
return new class extends Migration
{
    /** Ancienne valeur decorative, copiee depuis etablissements.taux_commission. */
    private const ANCIEN_TAUX = 0.005;

    public function up(): void
    {
        $tauxReel = app(AangaraaPayService::class)->tauxFraisService();

        $corrigees = DB::table('commissions')
            ->where('taux', self::ANCIEN_TAUX)
            ->update(['taux' => $tauxReel, 'updated_at' => now()]);

        if ($corrigees > 0) {
            \Illuminate\Support\Facades\Log::info('Taux de commission heritage corrige', [
                'lignes'   => $corrigees,
                'ancien'   => self::ANCIEN_TAUX,
                'nouveau'  => $tauxReel,
            ]);
        }
    }

    /**
     * Non annulable : on ne peut pas distinguer, apres coup, les lignes
     * corrigees ici de celles creees legitement a 2,3 % depuis la
     * refactorisation. Les remettre toutes a 0,005 detruirait l'historique
     * valide. Une sauvegarde des lignes d'origine est conservee dans la table
     * `_sauvegarde_taux_commission` pour permettre une restauration manuelle.
     */
    public function down(): void
    {
        //
    }
};
