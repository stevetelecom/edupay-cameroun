<?php

namespace App\Observers;

use App\Models\CategoriesFrais;
use App\Models\FraisApprenant;

/**
 * Quand on modifie une catégorie de frais, le montant de référence
 * (montant_total) doit se propager automatiquement à TOUS les dossiers
 * frais (FraisApprenant) où cette catégorie est affectée, afin que le
 * nouveau montant soit visible dans le dossier apprenant, les impayés,
 * la liste des frais, etc.
 *
 * Le « nom » et la description, eux, se propagent déjà tout seuls car
 * toutes les vues/API lisent la relation Eloquent $frais->categorieFrais.
 *
 * ⚠️ Sécurité : on ne met à jour que les FraisApprenant SANS aucun
 *    paiement. Un frais déjà payé (totalement ou partiellement) garde son
 *    montant d'origine pour ne pas fausser l'historique des encaissements.
 */
class CategoriesFraisObserver
{
    public function updated(CategoriesFrais $categorie): void
    {
        if (! $categorie->isDirty('montant_total')) {
            return;
        }

        $nouveauMontant = $categorie->montant_total;

        FraisApprenant::where('categorie_frais_id', $categorie->id)
            ->whereDoesntHave('paiements')
            ->update(['montant_total' => $nouveauMontant]);
    }
}
