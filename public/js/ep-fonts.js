// ═══════════════════════════════════════════════════════════════
// EDUPAY CAMEROUN — Garde-fou des polices d'icônes
//
// Les icônes Material Symbols sont rendues par LIGATURES : le texte
// « home » devient l'icône maison uniquement quand la police est
// chargée. Tant qu'elle ne l'est pas, le navigateur afficherait le
// nom de l'icône en clair (« arrow_forward »), ce qui casse le
// design — exactement le bug observé quand le CDN Google Fonts est
// bloqué ou lent.
//
// Stratégie (edupay-landing.css / ep-fonts.css masquent les icônes
// par défaut via visibility:hidden) :
//   1. document.fonts.check() teste la disponibilité réelle ;
//   2. dès qu'elle est prête, la classe `ep-fonts-ok` est posée sur
//      <html> et toutes les icônes deviennent visibles ;
//   3. timeout de sécurité à 3 s : si la police n'arrive toujours
//      pas (hors-ligne complet), on révèle quand même pour ne pas
//      laisser des trous vides — le texte s'affichera, ce qui reste
//      mieux que des boutons sans contenu.
// ═══════════════════════════════════════════════════════════════
(function () {
    'use strict';

    var pose = false;

    function reveler() {
        if (pose) return;
        pose = true;
        document.documentElement.classList.add('ep-fonts-ok');
    }

    // Police principale : Outlined est utilisée partout (layouts).
    // Rounded sert sur la landing et quelques en-têtes.
    var polices = ['24px Material Symbols Outlined', '24px Material Symbols Rounded'];

    function verifier() {
        var toutesPretes = polices.every(function (f) {
            try { return document.fonts.check(f); } catch (e) { return true; }
        });
        if (toutesPretes) { reveler(); return true; }
        return false;
    }

    // 1. Test immédiat (les polices sont peut-être déjà en cache).
    if (verifier()) return;

    // 2. Écoute du chargement asynchrone (event standard CSS Font Loading API).
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () { verifier(); });
        // Re-test périodique léger : fonts.ready peut résoudre avant que
        // toutes les familles déclarées aient terminé de se charger.
        var essais = 0;
        var interval = setInterval(function () {
            essais++;
            if (verifier() || essais > 30) { // ~3 s max (100 ms × 30)
                clearInterval(interval);
                reveler(); // timeout de sécurité : jamais de trous définitifs
            }
        }, 100);
    } else {
        // API absente (vieux navigateur) : on révèle direct, comportement
        // identique à l'ancien rendu CDN.
        window.addEventListener('load', reveler);
    }

    // 3. Filet absolu : au chargement complet, on révèle quoi qu'il arrive.
    window.addEventListener('load', function () {
        setTimeout(reveler, 300);
    });
})();
