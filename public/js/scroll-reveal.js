// ── Animations d'entrée au scroll (fade-in-up) ──
// Usage: ajouter la classe "reveal-on-scroll" à n'importe quel élément.
// Optionnel: data-reveal-delay="100" (ms) pour décaler l'animation.

document.addEventListener('DOMContentLoaded', function() {
  var revealEls = document.querySelectorAll('.reveal-on-scroll');
  if (!revealEls.length) return;

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        var el    = entry.target;
        var delay = parseInt(el.getAttribute('data-reveal-delay') || '0', 10);

        setTimeout(function() {
          el.classList.add('revealed');
        }, delay);

        observer.unobserve(el);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  revealEls.forEach(function(el) {
    observer.observe(el);
  });
});

// ── Auto-stagger : ajoute un délai croissant aux enfants directs d'un conteneur ──
// Usage: <div data-reveal-stagger="80"> <div class="reveal-on-scroll">...</div> ... </div>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-reveal-stagger]').forEach(function(container) {
    var step = parseInt(container.getAttribute('data-reveal-stagger'), 10) || 80;
    var children = container.querySelectorAll(':scope > .reveal-on-scroll');
    children.forEach(function(child, idx) {
      if (!child.hasAttribute('data-reveal-delay')) {
        child.setAttribute('data-reveal-delay', String(idx * step));
      }
    });
  });
});
