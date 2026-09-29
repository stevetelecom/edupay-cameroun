/* ============================================================
   EDUPAY — Interactions bandeaux v2 (accordéon animé au clic)
   Style maquette admin.html : chevron qui pivote, contenu qui
   se déplie en douceur (hauteur animée), feedback de clic.
   Chargé sur les layouts payeur/etablissement/admin.
   ============================================================ */
(function () {
    'use strict';

    /**
     * Accordéon fluide : anime max-height de 0 vers scrollHeight
     * puis bascule en "none" une fois déplié (pour rester responsive).
     */
    function basculer(bandeau) {
        var contenu = bandeau.querySelector('.ep-bandeau-contenu');
        if (!contenu) return;

        var ouvert = bandeau.classList.contains('ouvert');

        if (ouvert) {
            // Fermeture : repasser par une hauteur fixe pour animer, puis 0
            contenu.style.maxHeight = contenu.scrollHeight + 'px';
            requestAnimationFrame(function () {
                contenu.style.maxHeight = '0px';
            });
            bandeau.classList.remove('ouvert');
        } else {
            // Ouverture : optionnel, referme les autres bandeaux de la page
            var tous = document.querySelectorAll('.ep-bandeau.ouvert');
            tous.forEach(function (autre) {
                if (autre !== bandeau) {
                    var c = autre.querySelector('.ep-bandeau-contenu');
                    if (c) c.style.maxHeight = '0px';
                    autre.classList.remove('ouvert');
                }
            });
            contenu.style.maxHeight = contenu.scrollHeight + 'px';
            bandeau.classList.add('ouvert');
            // Une fois l'animation finie, laisser libre (contenu responsive)
            contenu.addEventListener('transitionend', function once(e) {
                if (e.propertyName === 'max-height' && bandeau.classList.contains('ouvert')) {
                    contenu.style.maxHeight = 'none';
                }
                contenu.removeEventListener('transitionend', once);
            });
        }
    }

    // Délégation : marche aussi pour les bandeaux injectés en AJAX
    document.addEventListener('click', function (e) {
        var chevron = e.target.closest('.ep-bandeau-chevron');
        if (chevron) {
            e.preventDefault();
            basculer(chevron.closest('.ep-bandeau'));
            return;
        }
        // Clic sur la zone titre = bascule aussi (sauf liens/boutons internes)
        var bandeau = e.target.closest('.ep-bandeau.repliable');
        if (bandeau && !e.target.closest('a, button:not(.ep-bandeau-chevron), form, select, input')) {
            basculer(bandeau);
        }
    });

    /* ── Feedback de clic global : petite impulsion (style maquette) ── */
    document.addEventListener('pointerdown', function (e) {
        var cible = e.target.closest('.kpi, .epcard, .ep-bandeau');
        if (!cible) return;
        cible.style.transition = 'transform .12s ease';
        cible.style.transform = 'scale(.985)';
        setTimeout(function () {
            cible.style.transform = '';
        }, 130);
    });
})();
