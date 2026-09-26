<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TraductionsPlaceholdersTest extends TestCase
{
    private function lang(string $locale, string $cle, array $params = []): string
    {
        $groupe = explode('.', $cle)[0];
        $lignes = require __DIR__.'/../../resources/lang/'.$locale.'/'.$groupe.'.php';

        $this->assertArrayHasKey(explode('.', $cle)[1], $lignes, "Cle de traduction absente : {$cle} ({$locale})");

        // comme Laravel : placeholders plus longs d'abord, sinon :n ecraserait :nb
        uksort($params, fn ($a, $b) => strlen($b) <=> strlen($a));

        return str_replace(
            array_map(fn ($p) => ':'.$p, array_keys($params)),
            array_map(fn ($v) => (string) $v, array_values($params)),
            $lignes[explode('.', $cle)[1]]
        );
    }

    public function test_rendu_des_placeholders_corriges(): void
    {
        $this->assertSame('Reste : 15 000 FCFA', $this->lang('fr', 'payeur.reste_fcfa', ['montant' => '15 000']));
        $this->assertSame('3 jours restants', $this->lang('fr', 'etablissement.jours_restants', ['count' => 3]));
        $this->assertSame('2 enfant(s) suivi(s)', $this->lang('fr', 'payeur.n_enfants_suivis', ['count' => 2]));
        $this->assertSame('75 % réglé', $this->lang('fr', 'payeur.pct_regle', ['pct' => 75]));
        $this->assertSame('Tranche 2 sur 3', $this->lang('fr', 'payeur.pay_tranche_suivante', ['n' => 2, 'nb' => 3]));
        $this->assertSame('500 apprenants max', $this->lang('fr', 'etablissement.apprenants_max', ['count' => 500]));
        $this->assertSame('Matricule : AB-1234', $this->lang('fr', 'etablissement.matricule_apos', ['matricule' => 'AB-1234']));

        $this->assertSame('Remaining: 15,000 FCFA', $this->lang('en', 'payeur.reste_fcfa', ['montant' => '15,000']));
        $this->assertSame('3 days remaining', $this->lang('en', 'etablissement.jours_restants', ['count' => 3]));
        $this->assertSame('75 % paid', $this->lang('en', 'payeur.pct_regle', ['pct' => 75]));
        $this->assertSame('Instalment 2 of 3', $this->lang('en', 'payeur.pay_tranche_suivante', ['n' => 2, 'nb' => 3]));
    }

    public function test_aucun_placeholder_affiche_brut_dans_les_vues(): void
    {
        $problemes = [];

        foreach (['fr', 'en'] as $locale) {
            foreach ($this->placeholdersParCle($locale) as $cle => $attendus) {
                foreach ($this->appels()[$cle] ?? [] as $params) {
                    foreach (array_diff($attendus, $params) as $manquant) {
                        $problemes[] = "{$locale} : {$cle} attend :{$manquant}";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $problemes,
            "Placeholders affiches brut dans l'interface :\n".implode("\n", array_unique($problemes))
        );
    }

    public function test_chaque_valeur_fournie_est_affichee_par_la_cle(): void
    {
        $problemes = [];

        foreach (['fr', 'en'] as $locale) {
            $cles = $this->placeholdersParCle($locale);

            foreach ($this->appels() as $cle => $listeParams) {
                if (str_starts_with($cle, 'validation.') || array_key_exists($cle, $cles) === false) {
                    continue;
                }

                foreach ($listeParams as $params) {
                    foreach (array_diff($params, $cles[$cle]) as $ignore) {
                        $problemes[] = "{$locale} : {$cle} ignore la valeur {$ignore}";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $problemes,
            "Valeurs envoyees mais jamais affichees :\n".implode("\n", array_unique($problemes))
        );
    }

    private function placeholdersParCle(string $locale): array
    {
        $cles = [];

        foreach (glob(__DIR__.'/../../resources/lang/'.$locale.'/*.php') as $fichier) {
            $groupe = basename($fichier, '.php');

            preg_match_all(
                "/'([\w]+)'\s*=>\s*(?:'((?:[^'\\\\]|\\\\.)*)'|\"((?:[^\"\\\\]|\\\\.)*)\")/s",
                file_get_contents($fichier),
                $m,
                PREG_SET_ORDER
            );

            foreach ($m as $match) {
                $valeur = $match[2] !== '' ? $match[2] : ($match[3] ?? '');
                preg_match_all('/:(\w+)/', $valeur, $trouves);
                $cles[$groupe.'.'.$match[1]] = $trouves[1];
            }
        }

        return $cles;
    }

    private function appels(): array
    {
        $appels = [];
        $fichiers = array_merge(
            glob(__DIR__.'/../../resources/views/**/*.blade.php') ?: [],
            glob(__DIR__.'/../../app/**/*.php') ?: []
        );

        foreach ($fichiers as $fichier) {
            preg_match_all(
                "/(?:__|@lang|trans)\(\s*'([\w.]+)'\s*,\s*\[(.*?)\]\s*\)/s",
                file_get_contents($fichier),
                $m,
                PREG_SET_ORDER
            );

            foreach ($m as $match) {
                preg_match_all("/'([\w]+)'\s*=>/", $match[2], $params);
                $appels[$match[1]][] = $params[1];
            }
        }

        return $appels;
    }
}
