<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Crée le compte Super Admin EduPay (MEKONTSO OLIVIER STEVE).
     *
     * IMPORTANT : Changer le mot de passe après le premier déploiement.
     */
    public function run(): void
    {
        // Créer les rôles admin plateforme (guard admin)
        $roleSuperAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'admin']
        );
        Role::firstOrCreate(
            ['name' => 'superviseur', 'guard_name' => 'admin']
        );
        Role::firstOrCreate(
            ['name' => 'comptable_plateforme', 'guard_name' => 'admin']
        );

        // Créer le compte Super Admin
        $admin = Admin::firstOrCreate(
            ['email' => 'moffosteve2@gmail.com'],
            [
                'prenom'    => 'Olivier',
                'nom'       => 'MEKONTSO',
                'email'     => 'moffosteve2@gmail.com',
                'telephone' => '690000000',   // À remplacer par le vrai numéro (format 9 chiffres, sans +237)
                'password'  => Hash::make('Admin@EduPay2026!'),  // À changer en production
                'est_actif' => true,
            ]
        );

        $admin->assignRole($roleSuperAdmin);

        $this->command->info('Super Admin créé : moffosteve2@gmail.com');
        $this->command->warn('IMPORTANT : Changez le mot de passe en production !');
    }
}