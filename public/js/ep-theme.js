/* ============================================================
   EDUPAY CAMEROUN — GESTION DU THÈME CLAIR / SOMBRE (v2 pro)
   - Préférence persistée dans localStorage (clé « ep-theme »)
   - À défaut : suit la préférence système (prefers-color-scheme)
   - Bascule appliquée sur <html data-theme="dark|light">
   - Transition douce : la classe .ep-theme-switching active un
     cross-fade global (défini dans edupay-theme.css), retirée
     une fois la transition terminée pour ne pas ralentir les
     interactions (hover, scroll).
   - Charts Chart.js : re-rendus en fondu (grille, ticks, pointes
     blanches adaptées) via l'événement « ep-themechange ».
   - API publique : window.epTheme.toggle(), .set(), .get()
   ============================================================ */
(function () {
    'use strict';

    var CLE = 'ep-theme';

    /* ── 1. Initialisation immédiate (le script est chargé avec defer) ── */
    function themeActuel() {
        try {
            var stocke = localStorage.getItem(CLE);
            if (stocke === 'dark' || stocke === 'light') return stocke;
        } catch (e) { /* localStorage indisponible (navigation privée) */ }
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    function appliquer(theme, avecTransition) {
        var racine = document.documentElement;
        if (avecTransition) {
            /* Cross-fade global : la classe est posée AVANT le changement
               d'attribut, retirée après la fin réelle de la transition. */
            racine.classList.add('ep-theme-switching');
        }
        racine.setAttribute('data-theme', theme);
        if (avecTransition) {
            window.setTimeout(function () {
                racine.classList.remove('ep-theme-switching');
            }, 600);
        }
        /* Notifier les composants (charts Chart.js notamment) */
        document.dispatchEvent(new CustomEvent('ep-themechange', { detail: { theme: theme } }));
    }

    appliquer(themeActuel(), false);

    /* ── 2. Suivi des changements de préférence système (si l'utilisateur
            n'a jamais choisi explicitement) ── */
    if (window.matchMedia) {
        var mqSombre = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemeChange = function (e) {
            var explicite = null;
            try { explicite = localStorage.getItem(CLE); } catch (err) {}
            if (explicite !== 'dark' && explicite !== 'light') {
                appliquer(e.matches ? 'dark' : 'light', true);
            }
        };
        if (mqSombre.addEventListener) mqSombre.addEventListener('change', onSystemeChange);
        else if (mqSombre.addListener) mqSombre.addListener(onSystemeChange);
    }

    /* ── 3. API publique ── */
    var epTheme = {
        get: themeActuel,
        set: function (theme) {
            if (theme !== 'dark' && theme !== 'light') return;
            try { localStorage.setItem(CLE, theme); } catch (e) {}
            appliquer(theme, true);
        },
        toggle: function () {
            this.set(themeActuel() === 'dark' ? 'light' : 'dark');
        }
    };
    window.epTheme = epTheme;

    /* ── 4. Bascule par bouton : data-action="ep-theme-toggle" ── */
    document.addEventListener('click', function (ev) {
        var bouton = ev.target.closest ? ev.target.closest('[data-action="ep-theme-toggle"]') : null;
        if (bouton) {
            ev.preventDefault();
            epTheme.toggle();
        }
    });

    /* ── 5. Icônes du bouton : sun visible en clair, moon en sombre ──
       Le bouton contient deux <span class="material-symbols-outlined">
       (.ep-tt-soleil / .ep-tt-lune) ; CSS masque/affiche selon le thème. */
    function majIcones(theme) {
        document.querySelectorAll('.ep-theme-toggle').forEach(function (btn) {
            btn.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }
    majIcones(themeActuel());
    document.addEventListener('ep-themechange', function (e) { majIcones(e.detail.theme); });
})();
