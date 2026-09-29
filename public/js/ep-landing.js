// ═══════════════════════════════════════════════════════════════
// EDUPAY CAMEROUN — LANDING PAGE v3 : interactions
// Chargé uniquement par resources/views/public/landing.blade.php.
//
// Effets (tous optionnels et dégradés gracieusement) :
//  1. Tilt 3D de la scène hero (carte bancaire + badges flottants)
//     → la souris fait pivoter les objets (perspective CSS).
//  2. Parallaxe légère des orbes de gravitation au scroll.
//  3. Soumission automatique du formulaire de filtre (débounce)
//     → le filtrage reste SERVEUR (pagination fiable).
//  4. Révélation au scroll des sections (IntersectionObserver),
//     en réutilisant .reveal-on-scroll / scroll-reveal.js si présent.
//
// Accessibilité : tout est désactivé si l'utilisateur demande
// "prefers-reduced-motion", et sur écrans tactiles le tilt tombe
// sur une animation CSS pure (déjà en place dans edupay-landing.css).
// ═══════════════════════════════════════════════════════════════
(function () {
    'use strict';

    // Motion réduit : aucun effet mouvement, on sort tout de suite.
    var motionReduit = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ── 1. TILT 3D DE LA SCÈNE HERO ─────────────────────────────
    var scene  = document.querySelector('.lp-scene');
    var tilt   = document.querySelector('.lp-tilt');

    if (scene && tilt && !motionReduit && window.matchMedia('(pointer: fine)').matches) {
        var enCours = false;

        // requestAnimationFrame : on ne réécrit le transform qu'une
        // fois par frame pour garder 60 fps même sur machines modestes.
        scene.addEventListener('mousemove', function (e) {
            if (enCours) return;
            enCours = true;
            requestAnimationFrame(function () {
                var rect  = scene.getBoundingClientRect();
                // Position relative du curseur : -0.5 … +0.5
                var rx = (e.clientY - rect.top) / rect.height - 0.5;
                var ry = (e.clientX - rect.left) / rect.width - 0.5;
                // Rotation max ±9° : effet présent mais jamais écœurant.
                var angleX = (-rx * 9).toFixed(2);
                var angleY = ( ry * 11).toFixed(2);
                tilt.style.transform =
                    'rotateX(' + angleX + 'deg) rotateY(' + angleY + 'deg)';
                enCours = false;
            });
        });

        // Sortie de souris : retour doux à la position de repos.
        scene.addEventListener('mouseleave', function () {
            tilt.style.transform = 'rotateX(0deg) rotateY(0deg)';
        });
    }

    // ── 2. PARALLAXE DES ORBES AU SCROLL ────────────────────────
    var orbes = document.querySelectorAll('.lp-orb');
    if (orbes.length && !motionReduit) {
        var scrollPrevu = false;
        window.addEventListener('scroll', function () {
            if (scrollPrevu) return;
            scrollPrevu = true;
            requestAnimationFrame(function () {
                var y = window.scrollY;
                // Translation maximale ~90px : subtil, jamais envahissant.
                orbes.forEach(function (orb, i) {
                    var facteur = (i % 2 === 0) ? 0.10 : -0.07;
                    orb.style.transform = 'translateY(' + (y * facteur).toFixed(1) + 'px)';
                });
                scrollPrevu = false;
            });
        }, { passive: true });
    }

    // ── 3. FILTRE ÉTABLISSEMENTS : soumission auto avec débounce ─
    var form     = document.getElementById('etab-filtre-form');
    var champ    = document.getElementById('etab-filter');
    var select   = document.getElementById('type-filter');
    if (form) {
        var minuteur = null;
        var valeurInitiale = champ ? champ.value : '';

        if (champ) {
            champ.addEventListener('input', function () {
                clearTimeout(minuteur);
                minuteur = setTimeout(function () {
                    // On ne soumet que si la valeur a réellement changé.
                    if (champ.value !== valeurInitiale) {
                        valeurInitiale = champ.value;
                        form.submit();
                    }
                }, 650);
            });
        }
        if (select) {
            select.addEventListener('change', function () { form.submit(); });
        }
    }

    // ── 4. RÉVÉLATION AU SCROLL (filet de sécurité) ─────────────
    // scroll-reveal.js s'en charge déjà ; on ne complète que s'il
    // est absent (robustesse si l'asset n'est pas chargé).
    var aReveler = document.querySelectorAll('.reveal-on-scroll:not(.revealed)');
    if (aReveler.length && typeof IntersectionObserver !== 'undefined') {
        var observer = new IntersectionObserver(function (entrees) {
            entrees.forEach(function (entree) {
                if (entree.isIntersecting) {
                    entree.target.classList.add('revealed');
                    observer.unobserve(entree.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        aReveler.forEach(function (el) { observer.observe(el); });
    }
})();
