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

/**
 * Crée 2 parents de test dont TOUS les frais sont déjà payés à 100%
 * (statut regle sur chaque FraisApprenant), pour tester l'affichage
 * 'à jour', les reçus PDF, et les certificats de scolarité.
 *
 * Prérequis : DatabaseSeeder déjà exécuté (Lycée de Melen existant).
 * Lancer avec : php artisan db:seed --class=PayeurRegleTestSeeder
 */
class PayeurRegleTestSeeder extends Seeder
{
    public function run(): void
    {
        $lycee = Etablissement::where('code_etablissement', 'LYC-MEL-2026')->first();

        if (! $lycee) {
            $this->command->error('Lycée de Melen introuvable. Lancez d\'abord : php artisan db:seed --class=DatabaseSeeder');
            return;
        }

        $scolarite = CategoriesFrais::where('etablissement_id', $lycee->id)
            ->where('nom', 'Scolarité')->where('annee_scolaire', '2025-2026')->first();

        $inscription = CategoriesFrais::where('etablissement_id', $lycee->id)
            ->where('nom', 'Inscription')->where('annee_scolaire', '2025-2026')->first();

        if (! $scolarite || ! $inscription) {
            $this->command->error('Catégories de frais introuvables. Lancez d\'abord : php artisan db:seed --class=PayeurTestSeeder');
            return;
        }

        $parents = [
            [
                'email' => 'parent.regle1@test.cm', 'prenom' => 'Solange', 'nom' => 'NGONO',
                'telephone' => '677111222', 'ville' => 'Yaoundé', 'quartier' => 'Nlongkak',
                'enfant' => ['matricule' => 'LYC-MEL-2026-010', 'nom' => 'NGONO', 'prenom' => 'Aurelie', 'classe' => 'Tle A', 'date_naissance' => '2008-04-12', 'sexe' => 'F'],
            ],
            [
                'email' => 'parent.regle2@test.cm', 'prenom' => 'Paul', 'nom' => 'ESSOMBA',
                'telephone' => '677333444', 'ville' => 'Yaoundé', 'quartier' => 'Mvog-Ada',
                'enfant' => ['matricule' => 'LYC-MEL-2026-011', 'nom' => 'ESSOMBA', 'prenom' => 'Kevin', 'classe' => '2nde C', 'date_naissance' => '2010-09-30', 'sexe' => 'M'],
            ],
        ];

        foreach ($parents as $data) {
            $parent = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'prenom'    => $data['prenom'],
                    'nom'       => $data['nom'],
                    'telephone' => $data['telephone'],
                    'ville'     => $data['ville'],
                    'quartier'  => $data['quartier'],
                    'password'  => Hash::make('password'),
                ]
            );
            if (! $parent->hasRole('parent')) {
                $parent->assignRole('parent');
            }

            $e = $data['enfant'];
            $enfant = Apprenant::firstOrCreate(
                ['matricule' => $e['matricule']],
                [
                    'etablissement_id' => $lycee->id,
                    'nom'              => $e['nom'],
                    'prenom'           => $e['prenom'],
                    'classe'           => $e['classe'],
                    'date_naissance'   => $e['date_naissance'],
                    'sexe'             => $e['sexe'],
                    'statut_paiement'  => 'regle',
                    'actif'            => true,
                ]
            );

            $parent->apprenants()->syncWithoutDetaching([$enfant->id => ['lien' => 'parent']]);

            // Scolarité 100% payée
            $fraisScolarite = FraisApprenant::firstOrCreate(
                ['apprenant_id' => $enfant->id, 'categorie_frais_id' => $scolarite->id, 'annee_scolaire' => '2025-2026'],
                ['montant_total' => 95000, 'montant_paye' => 95000, 'statut' => 'regle']
            );

            Paiement::firstOrCreate(
                ['reference' => 'EP2026-REGLE-' . $enfant->matricule . '-SCO'],
                [
                    'user_id'            => $parent->id,
                    'apprenant_id'       => $enfant->id,
                    'frais_apprenant_id' => $fraisScolarite->id,
                    'montant'            => 95000,
                    'mode_paiement'      => 'mtn_momo',
                    'type_paiement'      => 'integral',
                    'statut'             => 'valide',
                    'telephone_paiement' => $data['telephone'],
                    'date_paiement'      => now()->subDays(30),
                    'date_validation'    => now()->subDays(30),
                ]
            );

            // Inscription 100% payée
            $fraisInscription = FraisApprenant::firstOrCreate(
                ['apprenant_id' => $enfant->id, 'categorie_frais_id' => $inscription->id, 'annee_scolaire' => '2025-2026'],
                ['montant_total' => 20000, 'montant_paye' => 20000, 'statut' => 'regle']
            );

            Paiement::firstOrCreate(
                ['reference' => 'EP2026-REGLE-' . $enfant->matricule . '-INS'],
                [
                    'user_id'            => $parent->id,
                    'apprenant_id'       => $enfant->id,
                    'frais_apprenant_id' => $fraisInscription->id,
                    'montant'            => 20000,
                    'mode_paiement'      => 'orange_money',
                    'type_paiement'      => 'integral',
                    'statut'             => 'valide',
                    'telephone_paiement' => $data['telephone'],
                    'date_paiement'      => now()->subDays(60),
                    'date_validation'    => now()->subDays(60),
                ]
            );
        }

        $this->command->info('2 parents avec tous leurs frais réglés à 100% créés.');
        $this->command->table(
            ['Login', 'Mot de passe', 'Enfant'],
            [
                ['parent.regle1@test.cm ou 677111222', 'password', 'NGONO Aurelie — Tle A — Réglé (115 000 FCFA payés)'],
                ['parent.regle2@test.cm ou 677333444', 'password', 'ESSOMBA Kevin — 2nde C — Réglé (115 000 FCFA payés)'],
            ]
        );
    }
}
