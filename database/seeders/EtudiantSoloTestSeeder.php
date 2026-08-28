<?php

namespace Database\Seeders;

use App\Models\Apprenant;
use App\Models\CategoriesFrais;
use App\Models\Etablissement;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EtudiantSoloTestSeeder extends Seeder
{
    /**
     * Crée un compte étudiant solo de test (FONO Carine — Université de Douala)
     * conforme à la maquette s-parent / pane "Vue Élève / Étudiant".
     *
     * Lancer avec : php artisan db:seed --class=EtudiantSoloTestSeeder
     */
    public function run(): void
    {
        // ────────────────────────────────────────────
        // 1. ÉTABLISSEMENT — Université de Douala
        // ────────────────────────────────────────────
        $universite = Etablissement::firstOrCreate(
            ['code_etablissement' => 'UD-2026'],
            [
                'nom'               => 'Université de Douala',
                'type'              => 'universite',
                'statut_juridique'  => 'public',
                'region'            => 'littoral',
                'ville'             => 'Douala',
                'quartier'          => 'Bonamoussadi',
                'telephone'         => '699401234',
                'email'             => 'contact@univ-douala.cm',
                'statut'            => 'actif',
            ]
        );

        // ────────────────────────────────────────────
        // 2. COMPTE ÉTUDIANT — FONO Carine
        // ────────────────────────────────────────────
        $carine = User::firstOrCreate(
            ['email' => 'etudiant@test.cm'],
            [
                'prenom'           => 'Carine',
                'nom'              => 'FONO',
                'telephone'        => '699887766',
                'ville'            => 'Douala',
                'quartier'         => 'Bonamoussadi',
                'profil'           => 'etudiant',
                'notif_sms'        => true,
                'notif_email'      => true,
                'password'         => Hash::make('password'),
                'etablissement_id' => $universite->id,
            ]
        );

        if (! $carine->hasRole('eleve')) {
            $carine->assignRole('eleve');
        }

        // ────────────────────────────────────────────
        // 3. CATÉGORIE DE FRAIS — Scolarité Université
        // ────────────────────────────────────────────
        $scolariteUd = CategoriesFrais::firstOrCreate(
            ['etablissement_id' => $universite->id, 'nom' => 'Scolarité', 'annee_scolaire' => '2025-2026'],
            [
                'description'     => 'Frais de scolarité annuelle — Licence',
                'montant_total'   => 95000,
                'fractionnable'   => true,
                'nb_tranches_max' => 2,
                'actif'           => true,
            ]
        );

        // ────────────────────────────────────────────
        // 4. APPRENANT — Carine elle-même (lien soi-même)
        // ────────────────────────────────────────────
        $apprenantCarine = Apprenant::firstOrCreate(
            ['matricule' => 'UD-88231'],
            [
                'etablissement_id' => $universite->id,
                'nom'              => 'FONO',
                'prenom'           => 'Carine',
                'classe'           => 'Licence 2 GSI',
                'date_naissance'   => '2003-09-10',
                'sexe'             => 'F',
                'statut_paiement'  => 'partiel',
                'actif'            => true,
            ]
        );

        $carine->apprenants()->syncWithoutDetaching([
            $apprenantCarine->id => ['lien' => 'soi-meme'],
        ]);

        $fraisScolariteCarine = FraisApprenant::firstOrCreate(
            ['apprenant_id' => $apprenantCarine->id, 'categorie_frais_id' => $scolariteUd->id, 'annee_scolaire' => '2025-2026'],
            ['montant_total' => 95000, 'montant_paye' => 60000, 'statut' => 'partiel']
        );

        // ────────────────────────────────────────────
        // 5. HISTORIQUE DE PAIEMENTS
        // ────────────────────────────────────────────
        Paiement::firstOrCreate(
            ['reference' => 'EP2026-CARINE1'],
            [
                'user_id'             => $carine->id,
                'apprenant_id'        => $apprenantCarine->id,
                'frais_apprenant_id'  => $fraisScolariteCarine->id,
                'montant'             => 60000,
                'mode_paiement'       => 'orange_money',
                'type_paiement'       => 'tranche',
                'numero_tranche'      => 1,
                'statut'              => 'valide',
                'telephone_paiement'  => '699887766',
                'date_paiement'       => now()->subDays(10),
                'date_validation'     => now()->subDays(10),
            ]
        );

        // ────────────────────────────────────────────
        // RÉCAPITULATIF
        // ────────────────────────────────────────────
        $this->command->info('✅ Compte étudiant solo de test créé avec succès.');
        $this->command->table(
            ['Élément', 'Détail'],
            [
                ['Compte étudiant', 'etudiant@test.cm / password'],
                ['Établissement', 'Université de Douala'],
                ['Dossier', 'FONO Carine — Licence 2 GSI — Partiel (35 000 FCFA restants sur 95 000)'],
                ['Paiement historique', '1 paiement validé (60 000 FCFA, Orange Money)'],
            ]
        );
        $this->command->warn('Connectez-vous avec etudiant@test.cm / password puis allez sur /espace/tableau-de-bord');
    }
}
