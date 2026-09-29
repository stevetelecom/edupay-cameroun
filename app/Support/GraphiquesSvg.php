<?php

namespace App\Support;

/**
 * Rendu SVG pour les graphiques des rapports PDF.
 *
 * DomPDF n'exécute pas JavaScript : les graphiques Chart.js du
 * dashboard ne peuvent pas être capturés côté client. Cette classe
 * génère donc des SVG statiques (texte + formes simples) que DomPDF
 * restitue nativement, avec les couleurs EduPay.
 *
 * Deux figures :
 *  - Venn          : répartition des paiements par moyen (cercles
 *                    semi-transparents + cœur « multi-moyens »)
 *  - Barres jumelles : recouvrement par classe (attendu vs encaissé)
 */
class GraphiquesSvg
{
    /* Palette EduPay (identique au dashboard) */
    private const COULEURS = [
        'mtn_momo'     => '#FFCC00',
        'orange_money' => '#FF6600',
        'carte'        => '#1F6FB2',
    ];

    private const NAVY = '#0B2545';
    private const GRIS = '#5A6472';
    private const TEAL = '#0D9E75';
    private const GOLD = '#E8A020';

    /**
     * Diagramme de Venn : apprenants exclusifs par moyen + cœur multi-moyens.
     *
     * @param array $venn        ex. ['mtn_momo' => 12, 'orange_money' => 8, 'carte' => 3]
     * @param int   $multiMoyens nombre d'apprenants ayant payé avec 2+ moyens
     * @param array $labels      libellés par mode (traduits), ex. ['mtn_momo' => 'MTN MoMo']
     * @param int   $largeur     largeur du SVG en pixels
     * @param int   $hauteur     hauteur du SVG en pixels
     */
    public static function venn(array $venn, int $multiMoyens, array $labels = [], int $largeur = 560, int $hauteur = 300): string
    {
        // Modes présents (compteur > 0), dans l'ordre du dashboard
        $modes = array_values(array_filter(
            ['mtn_momo', 'orange_money', 'carte'],
            fn ($m) => ($venn[$m] ?? 0) > 0
        ));
        $n = count($modes);

        // Rien à dessiner : message centré
        if ($n === 0 && $multiMoyens === 0) {
            return self::cadre($largeur, $hauteur, __('admin.pdf_aucune_donnee'));
        }

        $cx = $largeur / 2;
        $cy = $hauteur / 2 + 6;
        $r  = min($largeur, $hauteur) * 0.26;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $largeur . '" height="' . $hauteur . '" viewBox="0 0 ' . $largeur . ' ' . $hauteur . '">';

        if ($n === 1) {
            $positions = [[$cx, $cy]];
        } elseif ($n === 2) {
            $positions = [[$cx - $r * 0.62, $cy], [$cx + $r * 0.62, $cy]];
        } else {
            $positions = [
                [$cx, $cy - $r * 0.55],
                [$cx - $r * 0.60, $cy + $r * 0.42],
                [$cx + $r * 0.60, $cy + $r * 0.42],
            ];
        }

        // Cercles semi-transparents
        foreach ($modes as $i => $mode) {
            [$x, $y] = $positions[$i];
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . $r . '" fill="' . self::COULEURS[$mode] . '" fill-opacity="0.45" stroke="#fff" stroke-width="2.5"/>';
        }

        // Cœur multi-moyens
        if ($multiMoyens > 0) {
            $rCoeur = $n === 3 ? $r * 0.42 : $r * 0.5;
            $svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $rCoeur . '" fill="' . self::GOLD . '" fill-opacity="0.55"/>';
            $svg .= self::texte($cx, $cy + 5, (string) $multiMoyens, 15, '#7A5205', 800);
        }

        // Compteurs exclusifs, décalés vers l'extérieur de chaque cercle
        foreach ($modes as $i => $mode) {
            [$x, $y] = $positions[$i];
            $tx = $cx + ($x - $cx) * 1.5;
            $ty = $cy + ($y - $cy) * 1.5;
            $svg .= self::texte($tx, $ty + 6, (string) $venn[$mode], 17, self::NAVY, 700);
        }

        // Légende dessous : pastille + libellé + effectif
        $yLeg = $hauteur - 26;
        $largeurLeg = 0;
        $positionsLeg = [];
        foreach ($modes as $mode) {
            $libelle = $labels[$mode] ?? $mode;
            $positionsLeg[] = [$mode, $libelle, $largeurLeg];
            // ~6.5 px par caractère en 11px + pastille 18px
            $largeurLeg += 18 + strlen($libelle) * 6.5 + 30;
        }
        if ($multiMoyens > 0) {
            $libelleMulti = __('etablissement.venn_multi_moyens');
            $positionsLeg[] = ['__multi', $libelleMulti, $largeurLeg];
            $largeurLeg += 18 + strlen($libelleMulti) * 6.5 + 30;
        }
        $xLeg = max(16, ($largeur - $largeurLeg) / 2);
        foreach ($positionsLeg as [$mode, $libelle, $dec]) {
            $couleur = $mode === '__multi' ? self::GOLD : self::COULEURS[$mode];
            $svg .= '<circle cx="' . ($xLeg + $dec + 7) . '" cy="' . ($yLeg - 4) . '" r="6" fill="' . $couleur . '"/>';
            $svg .= self::texte($xLeg + $dec + 18, $yLeg, $libelle . ' (' . ($mode === '__multi' ? $multiMoyens : $venn[$mode]) . ')', 11, self::GRIS, 600);
        }

        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Barres jumelles : attendu vs encaissé par classe.
     *
     * @param array $classes ex. [['nom' => 'CM2', 'attendu' => 500000, 'paye' => 350000], ...]
     */
    public static function barresClasses(array $classes, int $largeur = 640, int $hauteur = 320): string
    {
        $classes = array_values(array_filter($classes, fn ($c) => ($c['attendu'] ?? 0) > 0 || ($c['paye'] ?? 0) > 0));
        if (empty($classes)) {
            return self::cadre($largeur, $hauteur, __('admin.pdf_aucune_donnee'));
        }

        $padG = 64;   // marge gauche (étiquettes Y)
        $padB = 26;   // marge basse (noms de classes)
        $padH = 18;   // marge haute
        $zoneL = $largeur - $padG - 10;
        $zoneH = $hauteur - $padH - $padB;

        $max = max(array_merge(
            array_column($classes, 'attendu'),
            array_column($classes, 'paye')
        ));
        $max = max($max, 1);

        // Pas de l'axe Y « rond » (1, 2, 2.5, 5 × 10^n) pour 4 graduations
        $pasBrut = $max / 4;
        $puissance = pow(10, floor(log10($pasBrut)));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($m * $puissance >= $pasBrut) { $pas = $m * $puissance; break; }
        }
        $maxAxe = $pas * ceil($max / $pas);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $largeur . '" height="' . $hauteur . '" viewBox="0 0 ' . $largeur . ' ' . $hauteur . '">';

        // Grille horizontale + étiquettes Y
        $nbGrad = (int) round($maxAxe / $pas);
        for ($g = 0; $g <= $nbGrad; $g++) {
            $v = $pas * $g;
            $y = $padH + $zoneH - ($v / $maxAxe) * $zoneH;
            $svg .= '<line x1="' . $padG . '" y1="' . $y . '" x2="' . ($padG + $zoneL) . '" y2="' . $y . '" stroke="#E4E9EE" stroke-width="1"/>';
            $svg .= self::texte($padG - 6, $y + 4, self::fmt($v), 10, self::GRIS, 500, 'end');
        }

        // Barres : largeur calculée pour rester lisible même avec 15 classes
        $nb = count($classes);
        $pasClasse = $zoneL / $nb;
        $lBarre = max(8, min(26, $pasClasse * 0.32));
        foreach ($classes as $i => $c) {
            $xc = $padG + $pasClasse * $i + $pasClasse / 2;
            $hAtt = ($c['attendu'] / $maxAxe) * $zoneH;
            $hPay = ($c['paye'] / $maxAxe) * $zoneH;

            $svg .= '<rect x="' . ($xc - $lBarre - 1) . '" y="' . ($padH + $zoneH - $hAtt) . '" width="' . $lBarre . '" height="' . max($hAtt, 1) . '" rx="3" fill="' . self::NAVY . '" fill-opacity="0.35"/>';
            $svg .= '<rect x="' . ($xc + 1) . '" y="' . ($padH + $zoneH - $hPay) . '" width="' . $lBarre . '" height="' . max($hPay, 1) . '" rx="3" fill="' . self::TEAL . '"/>';

            // Nom de classe sous l'axe (tronqué si serré)
            $nom = mb_strlen($c['nom']) > 10 ? mb_substr($c['nom'], 0, 9) . '…' : $c['nom'];
            $svg .= self::texte($xc, $padH + $zoneH + 14, $nom, 10, self::GRIS, 600, 'middle');
        }

        // Légende
        $yLeg = $hauteur - 6;
        $svg .= '<rect x="' . $padG . '" y="' . ($yLeg - 8) . '" width="12" height="8" rx="2" fill="' . self::NAVY . '" fill-opacity="0.35"/>';
        $svg .= self::texte($padG + 18, $yLeg, __('etablissement.pdf_legende_attendu'), 11, self::GRIS, 600);
        $svg .= '<rect x="' . ($padG + 110) . '" y="' . ($yLeg - 8) . '" width="12" height="8" rx="2" fill="' . self::TEAL . '"/>';
        $svg .= self::texte($padG + 128, $yLeg, __('etablissement.pdf_legende_encaisse'), 11, self::GRIS, 600);

        $svg .= '</svg>';
        return $svg;
    }

    /** Élément <text> SVG avec police et ancrage. */
    private static function texte(float $x, float $y, string $contenu, int $taille, string $couleur, int $graisse = 500, string $ancre = 'middle'): string
    {
        return '<text x="' . $x . '" y="' . $y . '" text-anchor="' . $ancre . '" '
            . 'font-family="Helvetica, Arial, sans-serif" font-size="' . $taille . '" '
            . 'font-weight="' . $graisse . '" fill="' . $couleur . '">'
            . htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8') . '</text>';
    }

    /** Cadre vide avec message centré (cas « aucune donnée »). */
    private static function cadre(int $largeur, int $hauteur, string $message): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $largeur . '" height="' . $hauteur . '" viewBox="0 0 ' . $largeur . ' ' . $hauteur . '">'
            . '<rect x="1" y="1" width="' . ($largeur - 2) . '" height="' . ($hauteur - 2) . '" rx="8" fill="#F4F6F8" stroke="#E4E9EE"/>'
            . self::texte($largeur / 2, $hauteur / 2 + 4, $message, 12, '#999', 500)
            . '</svg>';
    }

    /** Format compact des montants pour l'axe Y : 1,2 M / 350 k. */
    private static function fmt(float $v): string
    {
        if ($v >= 1000000) return rtrim(rtrim(number_format($v / 1000000, 1, ',', ''), '0'), ',') . ' M';
        if ($v >= 1000)    return rtrim(rtrim(number_format($v / 1000, 0, ',', ''), '0'), ',') . ' k';
        return (string) (int) $v;
    }
}
