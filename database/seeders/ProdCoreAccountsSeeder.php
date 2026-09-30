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
        // `withTrashed` est indispensable : `Etablissement` utilise SoftDeletes,
        // donc un etablissement supprime logiquement reste invisible a
        // `where(...)->first()` tout en continuant d'occuper son code dans
        // l'index UNIQUE `etablissements_code_etablissement_unique`. Sans ce
        // `withTrashed`, le seeder tente l'INSERT et echoue en SQLSTATE 1062
        // « Duplicate entry 'UD-2026' », ce qui empechait le deploiement du
        // 30/09/2026 alors que la ligne existe bel et bien en base.
        $etablissement = Etablissement::withTrashed()->where('code_etablissement', 'UD-2026')->first();

        if ($etablissement && $etablissement->trashed()) {
            // Un etablissement de demonstration supprime logiquement n'a pas
            // vocation a disparaitre : le compte de direction qui y est rattache
            // ne fonctionnerait plus. On le restaure plutot que de tenter un
            // INSERT, que l'index unique refuserait.
            $etablissement->restore();
            $this->command->warn('Établissement UD-2026 supprimé logiquement — restauré.');
        }

        if (! $etablissement) {
            // La colonne `telephone` est NOT NULL sans valeur par defaut
            // (migration create_etablissements_table). Sans elle, l'INSERT
            // echoue en SQLSTATE 1364 et tout le seeder s'arrete : c'est ce
            // qui est arrive en production le 30/09/2026 a 08:01 et 08:15, ou
            // UD-2026 n'a jamais ete cree et le compte de direction non plus.
            //
            // La valeur par defaut est celle du local (699401234) pour que la
            // production obtienne le MEME etablissement que le developpement.
            // Elle reste surchargeable par .env si un vrai numero doit etre
            // pose. Le seeder ne bloque plus le deploiement sur une valeur
            // manquante : il retombe sur cette valeur en signalant l'ecart.
            $defaut = '699401234';
            $telephoneEtablissement = (string) env('SEED_ETABLISSEMENT_TELEPHONE', $defaut);

            if (! preg_match('/^[236]\d{8}$/', $telephoneEtablissement)) {
                $this->command->warn('SEED_ETABLISSEMENT_TELEPHONE invalide (9 chiffres, debutant par 2, 3 ou 6) — valeur par defaut utilisee : ' . $defaut);
                $telephoneEtablissement = $defaut;
            }

            $etablissement = Etablissement::create([
                'code_etablissement' => 'UD-2026',
                'nom'                 => 'Université de Douala',
                'type'                => 'universite',
                'statut_juridique'    => 'public',
                'statut'              => 'actif',
                'region'              => 'littoral',
                'ville'               => 'Douala',
                'telephone'           => $telephoneEtablissement,
                'email'               => 'contact@univ-douala.cm',
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
                'grace_period_fin'   => now()->addMonth()->addDays(7),
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
            $carine = User::create([
                'email'            => 'bebemakany@gmail.com',
                'prenom'           => 'Carine',
                'nom'              => 'FONO',
                'telephone'        => '654862989',
                'ville'            => 'Douala',
                'password'         => Hash::make($motDePasseDemo),
                'profil'           => 'etudiant',
                'etablissement_id' => $etablissement->id,
            ]);
            $carine->assignRole('eleve');
            $this->command->info('Payeur créé : ' . $carine->email . ' (id ' . $carine->id . ')');
        } else {
            $this->command->info('Payeur déjà présent (id ' . $carine->id . ') — mot de passe et rôle non modifiés');
        }

        // Le dashboard ne decide pas de la vue a partir du role Spatie mais de
        // `users.profil` : `in_array($user->profil, ['eleve', 'etudiant'])`
        // (DashboardController:54 et MesEnfantsController:33). La colonne est un
        // enum dont le defaut est `parent`, et ce seeder ne la renseignait pas :
        // le compte creat par cette commande avec le role `eleve` atterrissait
        // neanmoins sur le dashboard PARENT. C'est ce qui est arrive en
        // production le 30/09/2026.
        //
        // On repare aussi les comptes deja crees plutot que de laisser diverger :
        // l'omission vient de ce seeder, c'est donc a lui de la corriger.
        if ($carine->profil !== 'etudiant') {
            $this->command->warn('Profil « ' . $carine->profil . ' » au lieu de « etudiant » — corrigé (le dashboard affichait la vue parent).');
            $carine->forceFill(['profil' => 'etudiant'])->save();
        }

        // Apprenant lié (non sensible — mise à jour sans risque)
        $apprenant = Apprenant::updateOrCreate(
            [
                'nom'              => 'FONO',
                'prenom'           => 'Carine',
                'etablissement_id' => $etablissement->id,
            ],
            [
                'classe' => 'Master 1 Informatique',
            ]
        );

        // Sans ce lien, `$user->apprenants()` renvoie une collection vide et le
        // dashboard affiche une vue Solo sans aucun dossier. La table pivot
        // `user_apprenant` porte un champ `lien`, a « soi-meme » pour l'eleve
        // qui est son propre dossier (et non « parent »).
        if (! $carine->apprenants()->where('apprenants.id', $apprenant->id)->exists()) {
            $carine->apprenants()->attach($apprenant->id, ['lien' => 'soi-meme']);
            $this->command->info('Dossier apprenant rattaché : apprenant #' . $apprenant->id);
        }

        // ────────────────────────────────────────────
        // 4. RESPONSABLE ÉTABLISSEMENT — Paul ATEBA (directeur)
        // ────────────────────────────────────────────
        $directeur = User::where('email', 'bebewandji2@gmail.com')->first();

        if (! $directeur) {
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
        } else {
            $this->command->info('Directeur déjà présent (id ' . $directeur->id . ') — mot de passe et rôle non modifiés');
        }
    }

    /**
     * Mot de passe des comptes de démonstration EduPay.
     *
     * Ces comptes sont des DONNÉES DE TEST, pas des comptes réels : les
     * adresses `bebemakany@gmail.com` et `bebewandji2@gmail.com` n'existent
     que pour reproduire en test la meme situation qu'en developpement. La
     * version precedente exigeait `SEED_DEMO_PASSWORD` en production et
     * renvoyait `null` sans elle : le seeder sautait silencieusement les deux
     * comptes, et le developpement et la production divergeaient. Un fichier
     * `.env` a ete introduit sur le serveur uniquement pour ca, ce qui est
     * exactement la complication a eviter pour de la donnee de test.
     *
     * La valeur reste surchargeable par `SEED_DEMO_PASSWORD` si un
     * environnement doit imposer autre chose, mais l'absence de variable ne
     * doit plus empecher la creation des comptes.
     *
     * Ce mot de passe est volontairement connu : il ne protege que des profils
     * de test. Il ne doit jamais etre reutilise pour un compte reel.
     */
    private function resoudreMotDePasseDemo(): string
    {
        return (string) (env('SEED_DEMO_PASSWORD') ?: 'Edupay2026!');
    }
}
