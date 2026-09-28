<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use Illuminate\Database\Seeder;

/**
 * Ajoute des établissements actifs supplémentaires pour tester la pagination
 * de l'annuaire public (seuil de 12 par page).
 *
 * Lancer avec : php artisan db:seed --class=EtablissementsPaginationSeeder
 */
class EtablissementsPaginationSeeder extends Seeder
{
    public function run(): void
    {
        $etablissements = [
            ['code' => 'ECO-EDA-2026', 'nom' => 'École Primaire Edéa Centre',       'type' => 'primaire',         'region' => 'littoral',    'ville' => 'Edéa'],
            ['code' => 'COL-BAF-2026', 'nom' => 'Collège Bilingue de Bafoussam',     'type' => 'college',          'region' => 'ouest',       'ville' => 'Bafoussam'],
            ['code' => 'LYC-GAR-2026', 'nom' => 'Lycée Technique de Garoua',         'type' => 'lycee_technique', 'region' => 'nord',        'ville' => 'Garoua'],
            ['code' => 'MAT-DLA-2026', 'nom' => 'Maternelle Les Petits Génies',       'type' => 'maternelle',      'region' => 'littoral',    'ville' => 'Douala'],
            ['code' => 'UNI-BUE-2026', 'nom' => 'Institut Supérieur de Buea',        'type' => 'institut_prive',  'region' => 'sud_ouest',   'ville' => 'Buea'],
            ['code' => 'PRI-BER-2026', 'nom' => 'École Primaire de Bertoua',         'type' => 'primaire',        'region' => 'est',        'ville' => 'Bertoua'],
            ['code' => 'LYC-NGA-2026', 'nom' => 'Lycée Général de Ngaoundéré',       'type' => 'lycee_general',   'region' => 'adamaoua',   'ville' => 'Ngaoundéré'],
            ['code' => 'COL-KRB-2026', 'nom' => 'Collège Catholique de Kribi',       'type' => 'college',         'region' => 'sud',        'ville' => 'Kribi'],
            ['code' => 'GRP-MRA-2026', 'nom' => 'Groupe Scolaire de Maroua',         'type' => 'groupe_scolaire', 'region' => 'extreme_nord','ville' => 'Maroua'],
            ['code' => 'LYC-BAM-2026', 'nom' => 'Lycée Bilingue de Bamenda',        'type' => 'lycee_general',   'region' => 'nord_ouest', 'ville' => 'Bamenda'],
            ['code' => 'PRI-YAO2-2026','nom' => 'École Primaire de Nkolbisson',       'type' => 'primaire',        'region' => 'centre',     'ville' => 'Yaoundé'],
            ['code' => 'COL-DLA2-2026','nom' => 'Collège Privé Bonamoussadi',        'type' => 'college',         'region' => 'littoral',    'ville' => 'Douala'],
        ];

        foreach ($etablissements as $e) {
            Etablissement::firstOrCreate(
                ['code_etablissement' => $e['code']],
                [
                    'nom'                    => $e['nom'],
                    'type'                   => $e['type'],
                    'statut_juridique'       => 'public',
                    'numero_agrement'        => 'AGR-' . strtoupper(\Illuminate\Support\Str::random(8)),
                    'nb_eleves'              => '100_300',
                    'region'                 => $e['region'],
                    'ville'                  => $e['ville'],
                    'telephone'              => '6' . random_int(70000000, 99999999),
                    'email'                  => 'contact@' . \Illuminate\Support\Str::slug($e['nom']) . '.cm',
                    'mobile_money_principal' => 'les_deux',
                    'statut'                 => 'actif',
                    'taux_commission'        => 0.0050,
                ]
            );
        }

        $this->command->info('12 établissements de test créés pour la pagination.');
    }
}
