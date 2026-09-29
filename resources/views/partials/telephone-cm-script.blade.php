<script>
/**
 * Restriction et aide à la saisie pour les numéros de téléphone camerounais.
 *
 * Principe anti-bug « +237237237 » : l'indicatif n'est JAMAIS traité comme
 * une saisie. À chaque frappe on extrait d'abord les 9 chiffres nationaux
 * (en retirant tout « 237 » résiduel collé par le navigateur, la
 * restauration old() ou un collage), puis on affiche « +237 » + ces 9
 * chiffres. L'indicatif affiché ne peut donc jamais se dupliquer.
 *
 * Autres comportements :
 * - N'autorise que les chiffres et le « + » en tout début
 * - Limite à 9 chiffres après normalisation (hors indicatif 237)
 * - Affiche un compteur « X chiffres restants » en direct
 * - Vérifie que le numéro commence par 6 (sauf si data-allow-fixe="true")
 * - Ne réécrit la valeur QUE si elle diffère, en restaurant le curseur
 */
function initTelephoneCm(selector) {
    document.querySelectorAll(selector).forEach(function(input) {
        // Créer (ou récupérer) le petit indicateur sous le champ
        var hint = input.parentElement.querySelector('.tel-cm-hint');
        if (!hint) {
            hint = document.createElement('div');
            hint.className = 'tel-cm-hint';
            hint.style.cssText = 'font-size:11px;margin-top:-8px;margin-bottom:10px;transition:color .15s;';
            input.insertAdjacentElement('afterend', hint);
        }

        var allowFixe = input.dataset.allowFixe === 'true';

        /**
         * Extrait les 9 chiffres nationaux d'une valeur saisie.
         * Le « 237 » (avec ou sans « + ») est retiré AVANT tout le reste :
         * c'est ce qui empêche l'indicatif de se retrouver dans les chiffres
         * et d'apparaître en double (« +23723761… ») à la frappe.
         */
        function extraireChiffres(value) {
            var chiffres = String(value).replace(/\D/g, '');

            // Retire TOUS les « 237 » résiduels au début (23723761, 23723723761…)
            while (chiffres.startsWith('237')) {
                chiffres = chiffres.slice(3);
            }
            // Anciens préfixes tolérés : 00237, +237 déjà géré par \D
            chiffres = chiffres.replace(/^0+/, '');

            // Jamais plus de 9 chiffres nationaux
            if (chiffres.length > 9) {
                chiffres = chiffres.slice(0, 9);
            }
            return chiffres;
        }

        function majHint(chiffres) {
            var premierValide = allowFixe ? /^[236]/.test(chiffres) : /^6/.test(chiffres);

            if (chiffres.length === 0) {
                hint.textContent = allowFixe
                    ? 'Format : 6XXXXXXXX (mobile) ou 2XXXXXXXX / 3XXXXXXXX (fixe)'
                    : 'Format : 6XXXXXXXX (9 chiffres)';
                hint.style.color = '#999';
            } else if (!premierValide) {
                hint.textContent = allowFixe
                    ? 'Le numéro doit commencer par 6 (mobile), 2 ou 3 (fixe)'
                    : 'Le numéro doit commencer par 6 (mobile camerounais)';
                hint.style.color = 'var(--ep-red, #B91C1C)';
            } else if (chiffres.length < 9) {
                hint.textContent = (9 - chiffres.length) + ' chiffre(s) restant(s)';
                hint.style.color = '#999';
            } else {
                hint.textContent = 'Numero valide';
                hint.style.color = 'var(--ep-teal, #0D9E75)';
            }
        }

        /**
         * Affiche « +237 » + les chiffres, sans toucher au curseur inutilement.
         * Si la valeur change, le curseur est reposé après le même nombre de
         * chiffres nationaux (sinon il sauterait en fin de champ).
         */
        function normaliserAffichage(chiffres, repositionnerCurseur) {
            var formate = chiffres.length > 0 ? '+237' + chiffres : '';
            if (input.value !== formate) {
                var pos = input.selectionStart ?? input.value.length;
                // Nombre de chiffres NATIONAUX avant le curseur dans l'ancienne valeur
                var chiffresAvant = extraireChiffres(input.value.slice(0, pos)).length;
                input.value = formate;
                if (repositionnerCurseur) {
                    var nouvellePos = formate.length; // par défaut : fin du champ
                    if (chiffresAvant < chiffres.length) {
                        nouvellePos = 4 + chiffresAvant; // '+237' = 4 caractères
                    }
                    try { input.setSelectionRange(nouvellePos, nouvellePos); } catch (e) {}
                }
            }
            majHint(chiffres);
        }

        // Affichage initial (utile après une erreur de validation avec old())
        normaliserAffichage(extraireChiffres(input.value), false);

        input.addEventListener('input', function() {
            normaliserAffichage(extraireChiffres(input.value), true);
        });

        // Empêcher la saisie de lettres au clavier (en plus du nettoyage ci-dessus)
        input.addEventListener('keypress', function(e) {
            if (!/[\d+]/.test(e.key)) {
                e.preventDefault();
            }
        });

        // Collage : nettoie ce qui arrive (numéros copiés avec espaces/tirets)
        input.addEventListener('paste', function() {
            setTimeout(function() {
                normaliserAffichage(extraireChiffres(input.value), false);
            }, 0);
        });
    });
}
</script>
