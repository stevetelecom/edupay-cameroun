// ── Compteurs animés (déclenchés à l'apparition dans le viewport) ──
// Usage: <div class="stat-counter" data-count="123" data-decimals="0" data-suffix="">0</div>

function animateStatCounter(el) {
  var target   = parseFloat(el.getAttribute('data-count')) || 0;
  var decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
  var suffix   = el.getAttribute('data-suffix') || '';
  var duration = 1400; // ms
  var startTs  = null;

  function easeOutExpo(t) {
    return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
  }

  function step(ts) {
    if (!startTs) startTs = ts;
    var progress = Math.min((ts - startTs) / duration, 1);
    var eased    = easeOutExpo(progress);
    var current  = eased * target;

    var display = decimals > 0
      ? current.toFixed(decimals).replace('.', ',')
      : Math.floor(current).toLocaleString('fr-FR');

    el.textContent = display + suffix;

    if (progress < 1) {
      requestAnimationFrame(step);
    } else {
      var finalDisplay = decimals > 0
        ? target.toFixed(decimals).replace('.', ',')
        : Math.floor(target).toLocaleString('fr-FR');
      el.textContent = finalDisplay + suffix;
    }
  }

  requestAnimationFrame(step);
}

document.addEventListener('DOMContentLoaded', function() {
  // Cible tous les conteneurs de stats marqués sur la page
  var containers = document.querySelectorAll('[data-stats-container]');

  containers.forEach(function(container) {
    var counters = container.querySelectorAll('.stat-counter[data-count]');
    var hasAnimated = false;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting && !hasAnimated) {
          hasAnimated = true;
          counters.forEach(function(el) {
            animateStatCounter(el);
          });
          observer.disconnect();
        }
      });
    }, { threshold: 0.3 });

    observer.observe(container);
  });
});
