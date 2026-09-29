/* ============================================================
   EDUPAY CAMEROUN — COMPATIBILITÉ MODALS (facade + polyfill)
   Problème : les vues legacy (admins, abonnements, utilisateurs
   internes...) appellent ouvrirModal()/fermerModal() ou
   epModal.open()/close() alors que ces fonctions sont déclarées
   dans les layouts avec var/const SANS être exposées sur window,
   ou sont écrasées par les scripts inline des pages.

   Solution : ce fichier charge en PREMIER (defer) sur tous les
   layouts, expose une façade window.epModal fonctionnelle et
   fournit les helpers globaux. Les déclarations ultérieures des
   layouts ne cassent rien : window.epModal est réassigné à la
   même API. Les appels onclick des pages fonctionnent alors
   partout, y compris sur les modals Tailwind (.hidden).
   ============================================================ */
(function () {
    'use strict';

    /* Ouvre un modal par id. Supporte :
       - overlay .ep-modal-overlay (classe .open)
       - modals Tailwind legacy (.hidden + display flex) */
    function ouvrir(id) {
        var el = document.getElementById(id);
        if (!el) { console.warn('[epModal] modal introuvable :', id); return; }
        if (el.classList.contains('ep-modal-overlay')) {
            el.classList.add('open');
        } else {
            el.classList.remove('hidden');
            el.style.display = 'flex';
        }
        document.body.style.overflow = 'hidden';
    }

    function fermer(id) {
        var el = document.getElementById(id);
        if (!el) return;
        if (el.classList.contains('ep-modal-overlay')) {
            el.classList.remove('open');
        } else {
            el.classList.add('hidden');
            el.style.display = 'none';
        }
        var encoreOuvert = document.querySelector('.ep-modal-overlay.open, .fixed.inset-0:not(.hidden)');
        if (!encoreOuvert) document.body.style.overflow = '';
    }

    function fermerTout() {
        document.querySelectorAll('.ep-modal-overlay.open').forEach(function (el) {
            el.classList.remove('open');
        });
        document.querySelectorAll('[id^="modal-"]').forEach(function (el) {
            if (!el.classList.contains('ep-modal-overlay')) {
                el.classList.add('hidden');
                el.style.display = 'none';
            }
        });
        document.body.style.overflow = '';
    }

    /* API façade identique à celle des layouts */
    var epModal = {
        open: ouvrir,
        close: fermer,
        closeAll: fermerTout
    };

    /* Expose immédiatement : les onclick inline des pages y accèdent */
    window.epModal = epModal;

    /* Helpers globaux attendus par les vues legacy admin */
    window.ouvrirModal = ouvrir;
    window.fermerModal = fermer;
    window.ouvrirModalAdmin = ouvrir;

    /* Fermeture par overlay + Échap, une seule fois, en capture.
       La phase « capture » garantit l'exécution avant les handlers
       inline potentiellement bloquants. */
    document.addEventListener('click', function (e) {
        var overlay = e.target;
        if (overlay.classList &&
            overlay.classList.contains('ep-modal-overlay') &&
            overlay.classList.contains('open')) {
            var id = overlay.id;
            if (id) fermer(id);
        }
    }, true);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') fermerTout();
    });

    /* Boutons déclaratifs [data-modal-open] / [data-modal-close] :
       délégation (compatible contenu injecté dynamiquement) */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-modal-open]') : null;
        if (btn) { ouvrir(btn.getAttribute('data-modal-open')); return; }
        btn = e.target.closest ? e.target.closest('[data-modal-close]') : null;
        if (btn) { fermer(btn.getAttribute('data-modal-close')); }
    }, true);
})();
