<?php

namespace App\Support;

/**
 * Normalisation des textes libres saisi par un humain
 * (motif de remboursement, reponse admin, objet et description d'une
 * reclamation).
 *
 * Ces textes sont stockes tels quels puis relus dans le back-office, le
 * back-office mobile et les e-mails. Sans normalisation :
 * - les espaces de fin et les sauts de ligne parasites finissent dans les
 *   SMS et les e-mails ("Solde  " / "Motif\n\n\n"),
 * - les caracteres de controle invisibles (NUL, BEL, vertical tab) peuvent
 *   casser la mise en page ou le rendu,
 * - deux saisies differentes pour la meme raison produisent deux lignes en
 *   base.
 *
 * La longueur reste du ressort de la validation : on normalise, on ne
 * tronque jamais en silence.
 */
final class TexteLibre
{
    /**
     * Normalise un texte libre saisi par un utilisateur.
     *
     * @param  string|null  $valeur
     * @param  bool  $multiligne  conserve les retours a la ligne (reponses, descriptions)
     * @return string|null  null si la valeur est vide apres normalisation
     */
    public static function normaliser(?string $valeur, bool $multiligne = false): ?string
    {
        if ($valeur === null) {
            return null;
        }

        // 1. Caracteres de controle invisibles, sauf \n \r \t.
        $propre = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valeur) ?? '';

        if ($multiligne) {
            // 2. Espace autour des lignes, 3 lignes vides max, plus de 2 lignes vides.
            $propre = preg_replace('/[ \t]+/', ' ', $propre) ?? $propre;
            $propre = preg_replace('/[ \t]*\R[ \t]*/', "\n", $propre) ?? $propre;
            $propre = preg_replace("/\n{3,}/", "\n\n", $propre) ?? $propre;
        } else {
            // Texte sur une ligne : toute espace (dont tabulation) devient un espace.
            $propre = preg_replace('/\s+/u', ' ', $propre) ?? $propre;
        }

        $propre = trim($propre);

        return $propre === '' ? null : $propre;
    }

    /**
     * Normalise un texte libre en le rendant obligatoire.
     * A utiliser apres la validation : une valeur vide ne doit jamais
     * atteindre la base pour un champ qui porte une decision (motif de
     * refus, reponse a une reclamation).
     */
    public static function normaliserObligatoire(?string $valeur, bool $multiligne = false): ?string
    {
        return self::normaliser($valeur, $multiligne);
    }
}
