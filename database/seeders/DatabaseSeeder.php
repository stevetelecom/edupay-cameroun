<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ────────────────────────────────────────────
        // 1. RÔLES SPATIE
        // ────────────────────────────────────────────

        // Rôles pour le guard 'web' (table users)
        $rolesWeb = ['directeur', 'comptable', 'caissier', 'parent'];
        foreach ($rolesWeb as $role) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Rôle pour le guard 'admin' (table admins) — requis par SuperAdminMiddleware
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);

        // ────────────────────────────────────────────
        // 2. SUPER ADMIN — table `admins` dédiée (guard:admin)
        // Conforme à AdminAuthController : prenom, nom, email,
        // telephone, password. Rôle Spatie 'super-admin' (guard admin)
        // requis par SuperAdminMiddleware::hasRole('super-admin').
        // ────────────────────────────────────────────
        $admin = \App\Models\Admin::firstOrCreate(
            ['email' => 'admin@edupay.cm'],
            [
                'prenom'    => 'Olivier Steve',
                'nom'       => 'MEKONTSO',
                'telephone' => '699000001',
                'password'  => Hash::make('Admin2026!'),
                'est_actif' => true,
            ]
        );
        if (!$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }

        // ────────────────────────────────────────────
        // 3. ÉTABLISSEMENT DE TEST (table `users` classique)
        // → pour tester le code établissement à l'inscription parent
        // ────────────────────────────────────────────
        $lycee = \App\Models\Etablissement::firstOrCreate(
            ['code_etablissement' => 'LYC-MEL-2026'],
            [
                'nom'                    => 'Lycée Bilingue de Melen',
                'type'                   => 'lycee_general',
                'statut_juridique'       => 'public',
                'numero_agrement'        => '12345/MINESEC/2024',
                'nb_eleves'              => '300_500',
                'region'                 => 'centre',
                'ville'                  => 'Yaoundé',
                'quartier'               => 'Melen',
                'telephone'              => '222220000',
                'email'                  => 'secretariat@lyceemelen.cm',
                'mobile_money_principal' => 'les_deux',
                'statut'                 => 'actif',
                'taux_commission'        => 0.0050,
            ]
        );

        // ────────────────────────────────────────────
        // 4. PARENT DE TEST (table `users`, guard `web`, rôle Spatie)
        // ────────────────────────────────────────────
        $parent = \App\Models\User::firstOrCreate(
            ['email' => 'parent@test.cm'],
            [
                'name'      => 'FONO Marie',
                'telephone' => '699123456',
                'ville'     => 'Yaoundé',
                'quartier'  => 'Biyem-Assi',
                'password'  => Hash::make('password'),
            ]
        );
        if (!$parent->hasRole('parent')) {
            $parent->assignRole('parent');
        }

        // ────────────────────────────────────────────
        // 5. DIRECTEUR DE TEST (table `users`, guard `web`, rôle Spatie)
        // ────────────────────────────────────────────
        $directeur = \App\Models\User::firstOrCreate(
            ['email' => 'directeur@test.cm'],
            [
                'name'             => 'MVONDO Jean-Pierre',
                'telephone'        => '677000001',
                'ville'            => 'Yaoundé',
                'password'         => Hash::make('password'),
                'etablissement_id' => $lycee->id,
            ]
        );
        if (!$directeur->hasRole('directeur')) {
            $directeur->assignRole('directeur');
        }

        // ────────────────────────────────────────────
        // RÉCAPITULATIF
        // ────────────────────────────────────────────
        $this->command->info('✅ Seeder de test terminé.');
        $this->command->table(
            ['Module', 'Identifiant', 'Mot de passe', 'Note'],
            [
                ['Super Admin (/admin-ep2026/login)', 'admin@edupay.cm', 'Admin2026!', 'Code 2FA généré à chaque login, voir storage/logs/admin.log'],
                ['Login classique (/connexion)', 'parent@test.cm ou 699123456', 'password', 'Rôle parent'],
                ['Login classique (/connexion)', 'directeur@test.cm ou 677000001', 'password', 'Rôle directeur'],
            ]
        );
        $this->command->info('Code établissement de test (inscription parent étape 2) : LYC-MEL-2026');
        $this->command->warn('Pour le 2FA admin : après "Continuer vers la vérification 2FA", lance "tail -f storage/logs/admin.log" pour voir le code à 6 chiffres.');
    }
}
