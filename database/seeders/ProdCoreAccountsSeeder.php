<?php

namespace Database\Seeders;

use App\Models\Abonnement;
use App\Models\Apprenant;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Comptes de démo/présentation à reproduire en production, identiques à ceux
 * utilisés en local pour les tests de paiement + reversement.
 *
 * IMPORTANT — comportement "créer une fois, ne jamais écraser ensuite" :
 * ce seeder tourne à CHAQUE déploiement (voir deploy.yml). Il ne doit donc
 * JAMAIS réécrire un mot de passe, un rôle ou un statut déjà en place, sous
 * peine de réinitialiser silencieusement des actions manuelles faites en
 * production (changement de mot de passe, désactivation d'un établissement,
 * retrait d'un rôle, etc.).
 *
 * Mot de passe des comptes de démo lu depuis SEED_DEMO_PASSWORD (.env) —
 * jamais en dur dans le code. En environnement production, le seeder refuse
 * de créer un compte si cette variable est absente plutôt que de deviner
 * une valeur par défaut.
 *
 * Lancer avec : php artisan db:seed --class=ProdCoreAccountsSeeder
 */
class ProdCoreAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $motDePasseDemo = $this->resoudreMotDePasseDemo();

        // ────────────────────────────────────────────
        // 1. ÉTABLISSEMENT — Université de Douala
        // ────────────────────────────────────────────
        $etablissement = Etablissement::where('code_etablissement', 'UD-2026')->first();

        if (! $etablissement) {
            $etablissement = Etablissement::create([
                'code_etablissement' => 'UD-2026',
                'nom'                 => 'Université de Douala',
                'statut'              => 'actif',
                'ville'               => 'Douala',
            ]);
            $this->command->info('Établissement créé : ' . $etablissement->nom . ' (id ' . $etablissement->id . ')');
        } else {
            $this->command->info('Établissement déjà présent (id ' . $etablissement->id . '), statut actuel : ' . $etablissement->statut . ' — non modifié');

            if ($etablissement->statut !== 'actif') {
                $this->command->warn('ATTENTION : UD-2026 est inactif en base — statut NON réactivé automatiquement (décision manuelle respectée).');
            }
        }

        // ────────────────────────────────────────────
        // 2. ABONNEMENT — plan standard, créé uniquement s'il n'existe pas
        // ────────────────────────────────────────────
        $abonnementExistant = Abonnement::where('etablissement_id', $etablissement->id)
            ->where('statut', 'actif')
            ->first();

        if (! $abonnementExistant) {
            Abonnement::create([
                'etablissement_id'   => $etablissement->id,
                'plan'                => 'standard',
                'montant_mensuel'     => Abonnement::PLANS['standard']['montant'] ?? 10000,
                'date_debut'          => now(),
                'date_fin'            => now()->addMonth(),
                'statut'              => 'actif',
                'reference_paiement'  => 'SEED-PROD-STANDARD',
                'notes'               => 'Créé automatiquement par ProdCoreAccountsSeeder',
                'active_at'           => now(),
            ]);
            $this->command->info('Abonnement standard actif créé pour Université de Douala');
        } else {
            $this->command->info('Abonnement actif déjà présent (id ' . $abonnementExistant->id . ')');
        }

        // ────────────────────────────────────────────
        // 3. PAYEUR — Carine FONO (élève)
        // ────────────────────────────────────────────
        $carine = User::where('email', 'bebemakany@gmail.com')->first();

        if (! $carine) {
            if (! $motDePasseDemo) {
                $this->command->error('SEED_DEMO_PASSWORD absent de .env — compte Carine FONO non créé.');
            } else {
                $carine = User::create([
                    'email'            => 'bebemakany@gmail.com',
                    'prenom'           => 'Carine',
                    'nom'              => 'FONO',
                    'telephone'        => '654862989',
                    'ville'            => 'Douala',
                    'password'         => Hash::make($motDePasseDemo),
                    'etablissement_id' => $etablissement->id,
                ]);
                $carine->assignRole('eleve');
                $this->command->info('Payeur créé : ' . $carine->email . ' (id ' . $carine->id . ')');
            }
        } else {
            $this->command->info('Payeur déjà présent (id ' . $carine->id . ') — mot de passe et rôle non modifiés');
        }

        // Apprenant lié (non sensible — mise à jour sans risque)
        Apprenant::updateOrCreate(
            [
                'nom'              => 'FONO',
                'prenom'           => 'Carine',
                'etablissement_id' => $etablissement->id,
            ],
            [
                'classe' => 'Master 1 Informatique',
            ]
        );

        // ────────────────────────────────────────────
        // 4. RESPONSABLE ÉTABLISSEMENT — Paul ATEBA (directeur)
        // ────────────────────────────────────────────
        $directeur = User::where('email', 'bebewandji2@gmail.com')->first();

        if (! $directeur) {
            if (! $motDePasseDemo) {
                $this->command->error('SEED_DEMO_PASSWORD absent de .env — compte directeur non créé.');
            } else {
                $directeur = User::create([
                    'email'            => 'bebewandji2@gmail.com',
                    'prenom'           => 'Paul',
                    'nom'              => 'ATEBA',
                    'telephone'        => '677999888',
                    'ville'            => 'Douala',
                    'password'         => Hash::make($motDePasseDemo),
                    'etablissement_id' => $etablissement->id,
                ]);
                $directeur->assignRole('directeur');
                $this->command->info('Directeur créé : ' . $directeur->email . ' (id ' . $directeur->id . ')');
            }
        } else {
            $this->command->info('Directeur déjà présent (id ' . $directeur->id . ') — mot de passe et rôle non modifiés');
        }
    }

    /**
     * En local/dev, une valeur par defaut est toleree pour ne pas bloquer
     * le developpement. En production, la variable DOIT etre definie
     * explicitement dans le .env du serveur (jamais commitee).
     */
    private function resoudreMotDePasseDemo(): ?string
    {
        $valeur = env('SEED_DEMO_PASSWORD');

        if ($valeur) {
            return $valeur;
        }

        return app()->environment('production') ? null : 'Edupay2026!';
    }
}
