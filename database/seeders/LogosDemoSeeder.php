<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Génère des logos SVG de démo pour les établissements qui n'en ont pas,
 * afin de vérifier l'affichage des cartes de l'annuaire public et des
 * fiches publiques avec de vrais logos en base.
 *
 * Chaque logo est un monogramme SVG aux couleurs EduPay (navy / teal / or),
 * stocké via le disque 'public' dans logos/ — exactement le même dossier
 * que celui utilisé par l'upload réel (ParametreController::update et
 * RegisterEcolController::storeStep3).
 *
 * Lancer avec : php artisan db:seed --class=LogosDemoSeeder
 */
class LogosDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Palette par type d'établissement (cohérente avec les avatars de la landing)
        $palettes = [
            'maternelle'      => ['#F5B93F', '#C9860E'],
            'primaire'        => ['#0D9E75', '#085041'],
            'college'         => ['#1F6FB2', '#123C66'],
            'lycee_general'   => ['#7C3AED', '#4C1D95'],
            'lycee_technique' => ['#D94040', '#7E1F1A'],
            'institut_prive'  => ['#0A8562', '#064C39'],
            'universite'      => ['#16406E', '#0B2545'],
            'groupe_scolaire' => ['#E8A020', '#8B5E10'],
        ];

        $generes = 0;

        Etablissement::whereNull('logo')->orWhere('logo', '')->get()->each(
            function (Etablissement $etab) use ($palettes, &$generes) {
                [$c1, $c2] = $palettes[$etab->type] ?? ['#0D9E75', '#0A8562'];

                // Initiale(s) du nom : 1 ou 2 lettres (ex. « LB » pour Lycée Bilingue)
                $mots   = preg_split('/\s+/', trim($etab->nom)) ?: [];
                $sigle  = mb_strtoupper(mb_substr($mots[0] ?? 'E', 0, 1));
                if (isset($mots[1]) && mb_strlen($mots[1]) > 2) {
                    $sigle .= mb_strtoupper(mb_substr($mots[1], 0, 1));
                }

                // Monogramme SVG : fond dégradé + sigle blanc centré
                $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$c1}"/>
      <stop offset="1" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="240" height="240" rx="48" fill="url(#g)"/>
  <circle cx="196" cy="44" r="64" fill="#ffffff" opacity=".08"/>
  <text x="120" y="120" font-family="Poppins,Arial,sans-serif" font-size="92"
        font-weight="800" fill="#ffffff" text-anchor="middle"
        dominant-baseline="central">{$sigle}</text>
</svg>
SVG;

                // Écriture dans le disque public, dossier logos/ (identique à l'upload)
                $nomFichier = 'logos/' . Str::slug($etab->nom) . '-' . $etab->id . '.svg';
                \Illuminate\Support\Facades\Storage::disk('public')->put($nomFichier, $svg);

                $etab->update(['logo' => $nomFichier]);
                $generes++;
            }
        );

        $this->command?->info("Logos de démo générés : {$generes}");
    }
}
