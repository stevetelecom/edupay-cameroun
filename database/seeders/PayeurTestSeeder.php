<?php

namespace Database\Seeders;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Echeancier;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Database\Seeder;

class PayeurTestSeeder extends Seeder
{
    /**
     * Crée des données de test complètes pour le module Payeur :
     * - 2 apprenants rattachés au parent de test (parent@test.cm)
     * - Catégories de frais pour le Lycée de Melen
     * - Frais avec différents statuts (réglé, partiel, impayé)
     * - Un historique de paiements
     *
     * Lancer avec : php artisan db:seed --class=PayeurTestSeeder
     * Prérequis : DatabaseSeeder déjà exécuté (parent@test.cm + Lycée de Melen existants)
     */
    public function run(): void
    {
        $lycee = Etablissement::where('code_etablissement', 'LYC-MEL-2026')->first();

        if (! $lycee) {
            $this->command->error('Lycée de Melen introuvable. Lancez d\'abord : php artisan db:seed --class=DatabaseSeeder');
            return;
        }

        $parent = User::where('email', 'parent@test.cm')->first();

        if (! $parent) {
            $this->command->error('Parent de test introuvable. Lancez d\'abord : php artisan db:seed --class=DatabaseSeeder');
            return;
        }

        // ────────────────────────────────────────────
        // 1. CATÉGORIES DE FRAIS — Lycée de Melen
        // ────────────────────────────────────────────
        $scolarite = CategoriesFrais::firstOrCreate(
            ['etablissement_id' => $lycee->id, 'nom' => 'Scolarité', 'annee_scolaire' => '2025-2026'],
            [
                'description'     => 'Frais de scolarité annuelle',
                'montant_total'   => 95000,
                'fractionnable'   => true,
                'nb_tranches_max' => 2,
                'actif'           => true,
            ]
        );

        $inscription = CategoriesFrais::firstOrCreate(
            ['etablissement_id' => $lycee->id, 'nom' => 'Inscription', 'annee_scolaire' => '2025-2026'],
            [
                'description'     => 'Frais d\'inscription annuelle',
                'montant_total'   => 20000,
                'fractionnable'   => false,
                'nb_tranches_max' => 1,
                'actif'           => true,
            ]
        );

        $cantine = CategoriesFrais::firstOrCreate(
            ['etablissement_id' => $lycee->id, 'nom' => 'Cantine', 'annee_scolaire' => '2025-2026'],
            [
                'description'     => 'Frais de cantine trimestriels',
                'montant_total'   => 35000,
                'fractionnable'   => true,
                'nb_tranches_max' => 3,
                'actif'           => true,
            ]
        );

        // ────────────────────────────────────────────
        // 1b. ÉCHÉANCIERS — pour que la colonne "échéance" des impayés s'affiche
        // ────────────────────────────────────────────
        $this->creerEcheanciers($scolarite, 2, 95000);
        $this->creerEcheanciers($cantine, 3, 35000);

        // ────────────────────────────────────────────
        // 2. APPRENANT 1 — FONO Brice (3ème) — Partiellement payé
        // ────────────────────────────────────────────
        $brice = Apprenant::firstOrCreate(
            ['matricule' => 'LYC-MEL-2026-001'],
            [
                'etablissement_id' => $lycee->id,
                'nom'              => 'FONO',
                'prenom'           => 'Brice',
                'classe'           => '3ème',
                'date_naissance'   => '2011-03-15',
                'sexe'             => 'M',
                'statut_paiement'  => 'partiel',
                'actif'            => true,
            ]
        );

        $parent->apprenants()->syncWithoutDetaching([$brice->id => ['lien' => 'parent']]);

        $fraisScolariteBrice = FraisApprenant::firstOrCreate(
            ['apprenant_id' => $brice->id, 'categorie_frais_id' => $scolarite->id, 'annee_scolaire' => '2025-2026'],
            ['montant_total' => 95000, 'montant_paye' => 42500, 'statut' => 'partiel']
        );

        $fraisInscriptionBrice = FraisApprenant::firstOrCreate(
            ['apprenant_id' => $brice->id, 'categorie_frais_id' => $inscription->id, 'annee_scolaire' => '2025-2026'],
            ['montant_total' => 20000, 'montant_paye' => 20000, 'statut' => 'regle']
        );

        // ────────────────────────────────────────────
        // 3. APPRENANT 2 — FONO Chloé (CM2) — Totalement impayé
        // ────────────────────────────────────────────
        $chloe = Apprenant::firstOrCreate(
            ['matricule' => 'LYC-MEL-2026-002'],
            [
                'etablissement_id' => $lycee->id,
                'nom'              => 'FONO',
                'prenom'           => 'Chloé',
                'classe'           => 'CM2',
                'date_naissance'   => '2015-07-22',
                'sexe'             => 'F',
                'statut_paiement'  => 'impaye',
                'actif'            => true,
            ]
        );

        $parent->apprenants()->syncWithoutDetaching([$chloe->id => ['lien' => 'parent']]);

        $fraisScolariteChloe = FraisApprenant::firstOrCreate(
            ['apprenant_id' => $chloe->id, 'categorie_frais_id' => $scolarite->id, 'annee_scolaire' => '2025-2026'],
            ['montant_total' => 35000, 'montant_paye' => 0, 'statut' => 'impaye']
        );

        // ────────────────────────────────────────────
        // 4. HISTORIQUE DE PAIEMENTS (validés, liés aux frais ci-dessus)
        // ────────────────────────────────────────────
        Paiement::firstOrCreate(
            ['reference' => 'EP2026-TEST1'],
            [
                'user_id'             => $parent->id,
                'apprenant_id'        => $brice->id,
                'frais_apprenant_id'  => $fraisScolariteBrice->id,
                'montant'             => 25000,
                'mode_paiement'       => 'mtn_momo',
                'type_paiement'       => 'tranche',
                'numero_tranche'      => 1,
                'statut'              => 'valide',
                'telephone_paiement'  => '699123456',
                'date_paiement'       => now()->subDays(8),
                'date_validation'     => now()->subDays(8),
            ]
        );

        Paiement::firstOrCreate(
            ['reference' => 'EP2026-TEST2'],
            [
                'user_id'             => $parent->id,
                'apprenant_id'        => $brice->id,
                'frais_apprenant_id'  => $fraisScolariteBrice->id,
                'montant'             => 17500,
                'mode_paiement'       => 'orange_money',
                'type_paiement'       => 'tranche',
                'numero_tranche'      => 1,
                'statut'              => 'valide',
                'telephone_paiement'  => '699123456',
                'date_paiement'       => now()->subDays(3),
                'date_validation'     => now()->subDays(3),
            ]
        );

        Paiement::firstOrCreate(
            ['reference' => 'EP2026-TEST3'],
            [
                'user_id'             => $parent->id,
                'apprenant_id'        => $brice->id,
                'frais_apprenant_id'  => $fraisInscriptionBrice->id,
                'montant'             => 20000,
                'mode_paiement'       => 'mtn_momo',
                'type_paiement'       => 'integral',
                'statut'              => 'valide',
                'telephone_paiement'  => '699123456',
                'date_paiement'       => now()->subDays(20),
                'date_validation'     => now()->subDays(20),
            ]
        );

        // ────────────────────────────────────────────
        // 5. APPRENANT 3 — FONO Junior (Maternelle) — À jour (100% payé)
        // ────────────────────────────────────────────
        $junior = Apprenant::firstOrCreate(
            ['matricule' => 'LYC-MEL-2026-003'],
            [
                'etablissement_id' => $lycee->id,
                'nom'              => 'FONO',
                'prenom'           => 'Junior',
                'classe'           => 'Maternelle',
                'date_naissance'   => '2019-11-02',
                'sexe'             => 'M',
                'statut_paiement'  => 'regle',
                'actif'            => true,
            ]
        );

        $parent->apprenants()->syncWithoutDetaching([$junior->id => ['lien' => 'parent']]);

        $fraisInscriptionJunior = FraisApprenant::firstOrCreate(
            ['apprenant_id' => $junior->id, 'categorie_frais_id' => $inscription->id, 'annee_scolaire' => '2025-2026'],
            ['montant_total' => 20000, 'montant_paye' => 20000, 'statut' => 'regle']
        );

        Paiement::firstOrCreate(
            ['reference' => 'EP2026-TEST4'],
            [
                'user_id'             => $parent->id,
                'apprenant_id'        => $junior->id,
                'frais_apprenant_id'  => $fraisInscriptionJunior->id,
                'montant'             => 20000,
                'mode_paiement'       => 'mtn_momo',
                'type_paiement'       => 'integral',
                'statut'              => 'valide',
                'telephone_paiement'  => '699123456',
                'date_paiement'       => now()->subDays(15),
                'date_validation'     => now()->subDays(15),
            ]
        );

        // ────────────────────────────────────────────
        // RÉCAPITULATIF
        // ────────────────────────────────────────────
        $this->command->info('✅ Données de test Payeur créées avec succès.');
        $this->command->table(
            ['Élément', 'Détail'],
            [
                ['Parent de test', 'parent@test.cm / password'],
                ['Enfant 1', 'FONO Brice — 3ème — Partiel (52 500 FCFA restants sur Scolarité)'],
                ['Enfant 2', 'FONO Chloé — CM2 — Impayé (35 000 FCFA dus sur Scolarité)'],
                ['Paiements historiques', '3 paiements validés (25 000 + 17 500 + 20 000 FCFA)'],
            ]
        );
        $this->command->warn('Connectez-vous avec parent@test.cm / password puis allez sur /espace/tableau-de-bord');
    }

    /**
     * Crée les échéances (tranches) d'une catégorie de frais, étalées sur
     * les mois à venir, pour alimenter la colonne "échéance" des impayés.
     *
     * @param \App\Models\CategoriesFrais $categorie
     * @param int $nbTranches
     * @param float $montantTotal
     */
    private function creerEcheanciers($categorie, int $nbTranches, float $montantTotal): void
    {
        if ($categorie->echeanciers()->exists()) {
            return;
        }
        $montantTranche = round($montantTotal / max($nbTranches, 1), 0);
        for ($t = 1; $t <= $nbTranches; $t++) {
            Echeancier::updateOrCreate(
                [
                    'categorie_frais_id' => $categorie->id,
                    'numero_tranche'     => $t,
                ],
                [
                    'montant'       => $montantTranche,
                    'date_echeance' => now()->addMonths($t)->format('Y-m-d'),
                    'libelle'       => "Tranche $t",
                ]
            );
        }
    }
}
