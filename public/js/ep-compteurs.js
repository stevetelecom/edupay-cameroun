/* ============================================================
   EDUPAY — Compteurs animés (count-up) des KPI
   Anime les .kval des cartes .kpi de 0 vers leur valeur :
   easing easeOutCubic, format FR (espace milliers, virgule
   décimale), suffixe % conservé, déclenchement à l'apparition
   (IntersectionObserver), respect de prefers-reduced-motion.
   Requiert data-ep-count sur l'élément .kval (ajouté dans les
   vues) : sans l'attribut, la valeur reste statique.
   Chargé avec defer sur les layouts payeur/etablissement/admin.
   ============================================================ */
(function () {
    'use strict';

    var DUREE_DEFAUT = 1100; // ms — assez long pour être perçu, assez court pour ne pas gêner

    var reduireMouvement = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /**
     * Formate un nombre en conventions FR : 1 284 500 et 12,5.
     */
    function formaterFR(valeur, decimales) {
        var options = {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales,
        };
        return valeur.toLocaleString('fr-FR', options);
    }

    /**
     * Anime un élément de 0 vers sa valeur cible.
     * Cible = contenu initial (posé par Blade), ex. "1 284 500" ou "12,5%".
     */
    function animerCompteur(el) {
        if (el.dataset.epCountFait) return; // jamais deux fois
        el.dataset.epCountFait = '1';

        // Texte brut sans espaces fines/insécables ni séparateurs
        var brut = (el.textContent || '').replace(/[\s\u00A0\u202F]/g, '');
        var suffixe = '';
        var cible;
        var decimales = 0;

        // Suffixe non numérique (% par ex.) conservé tel quel
        var match = brut.match(/^([\d.,]+)(.*)$/);
        if (!match) return; // contenu non numérique : ne rien faire
        cible = parseFloat(match[1].replace(',', '.'));
        suffixe = match[2];
        if (isNaN(cible)) return;

        decimales = (match[1].split(',')[1] || '').length;

        if (reduireMouvement || cible === 0) {
            el.textContent = formaterFR(cible, decimales) + suffixe;
            return;
        }

        var debut = null;

        function pas(temps) {
            if (debut === null) debut = temps;
            var progression = Math.min((temps - debut) / DUREE_DEFAUT, 1);
            // easeOutCubic : démarre vite, ralentit en fin — effet premium
            var aisance = 1 - Math.pow(1 - progression, 3);
            el.textContent = formaterFR(cible * aisance, decimales) + suffixe;
            if (progression < 1) {
                requestAnimationFrame(pas);
            } else {
                el.textContent = formaterFR(cible, decimales) + suffixe; // valeur exacte à la fin
            }
        }

        requestAnimationFrame(pas);
    }

    /**
     * Observe les compteurs et les lance quand ils entrent dans l'écran.
     */
    function observerCompteurs() {
        var compteurs = document.querySelectorAll('[data-ep-count]');
        if (!compteurs.length) return;

        if (!('IntersectionObserver' in window) || reduireMouvement) {
            compteurs.forEach(animerCompteur);
            return;
        }

        var observateur = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (entree) {
                if (entree.isIntersecting) {
                    animerCompteur(entree.target);
                    observateur.unobserve(entree.target);
                }
            });
        }, { threshold: 0.4 });

        compteurs.forEach(function (el) { observateur.observe(el); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', observerCompteurs);
    } else {
        observerCompteurs();
    }
})();
