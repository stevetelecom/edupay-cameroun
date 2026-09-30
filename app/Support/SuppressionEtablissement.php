<?php

namespace App\Support;

use App\Models\Apprenant;
use App\Models\Commission;
use App\Models\Etablissement;
use App\Models\Paiement;

/**
 * Regle metier de suppression d'un etablissement (back-office super admin).
 *
 * Un etablissement actif n'est pas une fiche isolable : ses e eleves portent des
 * frais, ces frais sont couverts par des paiements valides qui ont genere des
 * commissions et des reversements deja preleves chez AangaraaPay. Effacer
 * l'etablissement en cascade detruirait l'historique financier d'une ecole
 * cliente, ce que le CDC §3.2 interdit (« tracabilite complete et infalsifiable
 * de toutes les transactions financieres de la plateforme ») et ce que le §8.3
 * aggrave (« logs [...] conserves 12 mois min »).
 *
 * D'ou la regle retenue :
 *
 *   1. etablissement sans aucune activite (aucun eleve, aucun frais, aucun
 *      paiement, aucune commission) -> suppression REELLE. Il n'y a rien a
 *      perdre : la fiche etait creee par erreur ou jamais utilisee.
 *   2. etablissement ayant deja de l'activite -> ARCHIVAGE (soft delete). La
 *      ligne sort des listes operatoires mais reste en base, avec son
 *      historique comptable intact et consultable.
 *
 * Le comportement est annonce a l'admin dans le message de retour, qui ne doit
 * jamais pretendre « supprime » pour un simple archivage.
 */
final class SuppressionEtablissement
{
    /**
     * @return array{
     *     autorise: bool,
     *     eleves: int,
     *     paiements: int,
     *     commissions: int,
     *     motif: string|null
     * }
     */
    public static function analyser(Etablissement $etablissement): array
    {
        $eleves      = Apprenant::where('etablissement_id', $etablissement->id)->count();
        $paiements   = Paiement::whereHas(
            'apprenant',
            fn ($q) => $q->where('etablissement_id', $etablissement->id)
        )->count();
        $commissions = Commission::where('etablissement_id', $etablissement->id)->count();

        $autorise = $eleves === 0 && $paiements === 0 && $commissions === 0;

        return [
            'autorise'   => $autorise,
            'eleves'     => $eleves,
            'paiements'  => $paiements,
            'commissions' => $commissions,
            'motif'      => $autorise ? null : __('admin.suppr_etab_archivage', [
                'eleves'     => $eleves,
                'paiements'  => $paiements,
                'commissions' => $commissions,
            ]),
        ];
    }
}
