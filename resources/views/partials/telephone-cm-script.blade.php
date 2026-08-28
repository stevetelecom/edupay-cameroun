<script>
/**
 * Restriction et aide à la saisie pour les numéros de téléphone camerounais.
 * - N'autorise que les chiffres et le "+" en tout début
 * - Limite à 9 chiffres après normalisation (hors indicatif 237)
 * - Affiche un compteur "X chiffres restants" en direct
 * - Vérifie que le numéro commence par 6 (sauf si data-allow-fixe="true")
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

        function extraireChiffres(value) {
            var digits = value.replace(/\D/g, '');
            if (digits.startsWith('237') && digits.length > 9) {
                digits = digits.slice(3);
            }
            if (digits.length > 9) {
                digits = digits.slice(-9);
            }
            return digits;
        }

        function majHint(digits) {
            var premierValide = allowFixe ? /^[236]/.test(digits) : /^6/.test(digits);

            if (digits.length === 0) {
                hint.textContent = allowFixe
                    ? 'Format : 6XXXXXXXX (mobile) ou 2XXXXXXXX / 3XXXXXXXX (fixe)'
                    : 'Format : 6XXXXXXXX (9 chiffres)';
                hint.style.color = '#999';
            } else if (!premierValide) {
                hint.textContent = allowFixe
                    ? 'Le numéro doit commencer par 6 (mobile), 2 ou 3 (fixe)'
                    : 'Le numéro doit commencer par 6 (mobile camerounais)';
                hint.style.color = 'var(--ep-red, #B91C1C)';
            } else if (digits.length < 9) {
                hint.textContent = (9 - digits.length) + ' chiffre(s) restant(s)';
                hint.style.color = '#999';
            } else {
                hint.textContent = '✓ Numéro valide';
                hint.style.color = 'var(--ep-teal, #0D9E75)';
            }
        }

        // Affichage initial (utile après une erreur de validation avec old())
        majHint(extraireChiffres(input.value));

        input.addEventListener('input', function() {
            var digits = extraireChiffres(input.value);
            input.value = '+237' + digits;
            // Si le champ est vidé, ne pas forcer le +237 tout seul
            if (digits.length === 0) input.value = '';
            majHint(digits);
        });

        // Empêcher la saisie de lettres au clavier (en plus du nettoyage ci-dessus)
        input.addEventListener('keypress', function(e) {
            if (!/[\d+]/.test(e.key)) {
                e.preventDefault();
            }
        });
    });
}
</script>
