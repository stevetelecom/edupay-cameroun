@extends('layouts.public')

@section('title', 'EduPay Cameroun — Paiement électronique des frais scolaires')

@section('content')

{{-- ══ HERO BAND ══ --}}
@include('layouts._navbar_public')
<div class="hero-band">
  <div class="hero-main">
    <div class="hero-tag">
      <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span>
      Plateforme 100% camerounaise · EdTech × FinTech
    </div>
    <div class="hero-h1">Payez les frais scolaires<br>en <em>2 minutes</em>,<br>depuis votre téléphone.</div>
    <div class="hero-sub">EduPay Cameroun connecte les établissements scolaires aux familles via MTN MoMo, Orange Money et carte bancaire. Zéro file d'attente. Reçu PDF immédiat.</div>
    <div class="hero-btns">
      <a href="{{ route('register.parent.step1') }}" class="hbtn-main">Créer mon compte payeur</a>
      <a href="{{ route('register.ecole.step1') }}" class="hbtn-ghost">Inscrire mon établissement</a>
    </div>
  </div>
  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-v">{{ $stats['nb_etablissements'] > 0 ? $stats['nb_etablissements'] : '0' }}</div>
      <div class="hstat-l">Établissements partenaires</div>
    </div>
    <div class="hstat">
      <div class="hstat-v">{{ $stats['nb_apprenants'] > 0 ? number_format($stats['nb_apprenants'],0,',',' ') : '0' }}</div>
      <div class="hstat-l">Apprenants inscrits</div>
    </div>
    <div class="hstat">
      <div class="hstat-v">{{ $stats['nb_paiements'] > 0 ? $stats['nb_paiements'] : '0' }}</div>
      <div class="hstat-l">Paiements validés</div>
    </div>
    <div class="hstat">
      <div class="hstat-v">99,5%</div>
      <div class="hstat-l">Uptime garanti</div>
    </div>
  </div>
</div>

{{-- ══ BODY ══ --}}
<div class="ep-body2">


  {{-- ══ SECTION : Établissements partenaires ══ --}}
  <div style="margin-bottom:32px;">
    <div class="seclbl">Nos établissements partenaires</div>
    <div style="font-size:13px;color:#888;margin-bottom:20px;text-align:center;">
      {{ $stats['nb_etablissements'] }} établissement{{ $stats['nb_etablissements'] > 1 ? 's' : '' }}
      nous font confiance pour la collecte de leurs frais scolaires.
    </div>

    {{-- Filtre rapide --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
      <input type="text" id="etab-filter"
             placeholder="Rechercher un établissement..."
             oninput="filtrerEtabsPublic()"
             style="flex:1;min-width:200px;padding:10px 14px;border:1px solid #ddd;
                    border-radius:8px;font-size:13px;outline:none;" />
      <select id="type-filter" onchange="filtrerEtabsPublic()"
              style="padding:10px 14px;border:1px solid #ddd;border-radius:8px;
                     font-size:13px;background:#fff;outline:none;">
        <option value="">Tous les types</option>
        <option value="maternelle">Maternelle</option>
        <option value="primaire">Primaire</option>
        <option value="college">Collège</option>
        <option value="lycee_general">Lycée général</option>
        <option value="lycee_technique">Lycée technique</option>
        <option value="universite">Université</option>
        <option value="institut">Institut</option>
      </select>
    </div>

    {{-- Grille établissements --}}
    <div id="etabs-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;">
      @forelse($etablissements as $etab)
      <div class="etab-card-pub"
           data-nom="{{ strtolower($etab->nom) }}"
           data-ville="{{ strtolower($etab->ville ?? '') }}"
           data-type="{{ strtolower($etab->type ?? '') }}"
           style="background:#fff;border:1px solid #eee;border-radius:12px;
                  padding:16px;text-align:center;transition:box-shadow .15s;
                  cursor:default;">
        {{-- Logo ou avatar --}}
        @if($etab->logo)
          <img src="{{ asset('storage/'.$etab->logo) }}"
               alt="{{ $etab->nom }}"
               style="width:56px;height:56px;border-radius:10px;object-fit:cover;
                      margin:0 auto 10px;display:block;border:1px solid #eee;" />
        @else
          <div style="width:56px;height:56px;border-radius:10px;
                      background:var(--ep-teal-lt);display:flex;align-items:center;
                      justify-content:center;margin:0 auto 10px;
                      font-size:22px;font-weight:700;color:var(--ep-teal);">
            {{ strtoupper(substr($etab->nom, 0, 1)) }}
          </div>
        @endif

        <div style="font-size:13px;font-weight:700;color:#1a1a2e;margin-bottom:4px;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
             title="{{ $etab->nom }}">
          {{ $etab->nom }}
        </div>
        <div style="font-size:11px;color:#888;margin-bottom:8px;">
          📍 {{ $etab->ville ?? '—' }}
        </div>
        <span style="font-size:10px;padding:3px 8px;border-radius:20px;
                     background:var(--ep-teal-lt);color:#085041;font-weight:500;">
          {{ ucfirst(str_replace('_', ' ', $etab->type ?? 'Établissement')) }}
        </span>
      </div>
      @empty
      <div style="grid-column:1/-1;text-align:center;color:#aaa;padding:40px 0;font-size:13px;">
        Aucun établissement partenaire pour le moment. Soyez le premier !
        <div style="margin-top:12px;">
          <a href="{{ route('register.ecole.step1') }}" class="hbtn-main"
             style="font-size:13px;padding:10px 20px;">
            Inscrire mon établissement →
          </a>
        </div>
      </div>
      @endforelse
    </div>

    {{-- Voir plus si beaucoup d'établissements --}}
    @if($etablissements->count() > 12)
    <div style="text-align:center;margin-top:16px;">
      <button onclick="toggleTousEtabs(this)"
              style="background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);
                     padding:10px 24px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;">
        Voir tous les {{ $etablissements->count() }} établissements
      </button>
    </div>
    @endif
  </div>

  <div class="seclbl" style="margin-top:4px;">Pourquoi choisir EduPay ?</div>
  <div class="feat-grid" style="margin-bottom:24px;">

    <div class="feat-card">
      <div class="feat-line" style="background:var(--ep-teal);"></div>
      <div class="feat-icon" style="background:var(--ep-teal-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
      </div>
      <div class="feat-title">Mobile Money natif</div>
      <div class="feat-desc">Intégration directe MTN Mobile Money & Orange Money Cameroun. Confirmation USSD instantanée.</div>
    </div>

    <div class="feat-card">
      <div class="feat-line" style="background:var(--ep-gold);"></div>
      <div class="feat-icon" style="background:var(--ep-gold-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div class="feat-title">Reçu PDF automatique</div>
      <div class="feat-desc">Chaque paiement validé génère un reçu signé électroniquement, envoyé par email et SMS.</div>
    </div>

    <div class="feat-card">
      <div class="feat-line" style="background:#185FA5;"></div>
      <div class="feat-icon" style="background:var(--ep-blue-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <div class="feat-title">Dashboard temps réel</div>
      <div class="feat-desc">Directeurs et comptables suivent encaissements, impayés et relances depuis un seul écran.</div>
    </div>

    <div class="feat-card">
      <div class="feat-line" style="background:#7C3AED;"></div>
      <div class="feat-icon" style="background:var(--ep-purple-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="feat-title">Sécurité PCI-DSS</div>
      <div class="feat-desc">Chiffrement TLS 1.3, authentification 2FA, conformité COBAC/BEAC et protection anti-fraude.</div>
    </div>

    <div class="feat-card">
      <div class="feat-line" style="background:var(--ep-red);"></div>
      <div class="feat-icon" style="background:var(--ep-red-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#D94040" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="feat-title">Paiement fractionné</div>
      <div class="feat-desc">Payez en 2 ou 3 tranches selon l'échéancier de l'établissement. Rappels SMS automatiques.</div>
    </div>

    <div class="feat-card">
      <div class="feat-line" style="background:var(--ep-teal);"></div>
      <div class="feat-icon" style="background:var(--ep-teal-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
      <div class="feat-title">Multi-établissements</div>
      <div class="feat-desc">Un parent peut gérer plusieurs enfants dans plusieurs écoles depuis un seul compte EduPay.</div>
    </div>

  </div>

  <div class="seclbl">Conçu pour tout le système éducatif</div>
  <div class="g4" style="margin-bottom:24px;">
    <div class="epcard" style="text-align:center;border-top:3px solid var(--ep-teal);">
      <div style="width:36px;height:36px;background:var(--ep-teal-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">Maternelle &amp; Primaire</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">Inscription, frais scolaires, cantine</div>
    </div>
    <div class="epcard" style="text-align:center;border-top:3px solid var(--ep-gold);">
      <div style="width:36px;height:36px;background:var(--ep-gold-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">Collèges &amp; Lycées</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">Scolarité, examens, internat</div>
    </div>
    <div class="epcard" style="text-align:center;border-top:3px solid #185FA5;">
      <div style="width:36px;height:36px;background:var(--ep-blue-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">Universités &amp; Instituts</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">Frais d'inscription, concours</div>
    </div>
    <div class="epcard" style="text-align:center;border-top:3px solid #7C3AED;">
      <div style="width:36px;height:36px;background:var(--ep-purple-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">Parents &amp; Étudiants</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">Paiement 24h/24 depuis partout</div>
    </div>
  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:4px;">
    <div>
      <div style="font-size:18px;font-weight:600;color:#fff;margin-bottom:5px;">Votre établissement n'est pas encore sur EduPay ?</div>
      <div style="font-size:13px;color:rgba(255,255,255,.55);">Inscription gratuite · Onboarding en 24h · Support dédié · Aucun engagement</div>
    </div>
    <a href="{{ route('register.ecole.step1') }}" style="background:var(--ep-teal);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;">Inscrire mon établissement →</a>
  </div>

</div>

{{-- ══ FOOTER ══ --}}
<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">Edu<span>Pay</span> Cameroun</div>
      <div class="footer-desc">La première plateforme de paiement électronique des frais de scolarité pensée pour les réalités camerounaises. Mobile Money · Sécurisé · Accessible.</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">Produit</div>
      <a class="footer-link" href="{{ route('landing') }}">Fonctionnalités</a>
      <a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a>
      <a class="footer-link" href="#">Tarifs</a>
      <a class="footer-link" href="#">API Développeurs</a>
    </div>
    <div>
      <div class="footer-col-title">Établissements</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscrire mon école</a>
      <a class="footer-link" href="#">Back-office</a>
      <a class="footer-link" href="{{ route('guide') }}">Guide d'utilisation</a>
      <a class="footer-link" href="{{ route('support') }}">Support dédié</a>
    </div>
    <div>
      <div class="footer-col-title">Informations</div>
      <a class="footer-link" href="{{ route('about') }}">À propos</a>
      <a class="footer-link" href="#">Contact</a>
      <a class="footer-link" href="#">Politique de confidentialité</a>
      <a class="footer-link" href="#">Conditions d'utilisation</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div>
      <div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés · Réf. CDC-EDUPAY-CM-2026-001</div>
      <div class="certif">
        <span class="cert-badge">MTN Mobile Money Partner</span>
        <span class="cert-badge">Orange Money Intégré</span>
        <span class="cert-badge">CinetPay Certifié</span>
        <span class="cert-badge">COBAC Conforme</span>
      </div>
    </div>
    <div class="footer-socials">
      <div class="social-btn">in</div><div class="social-btn">X</div>
      <div class="social-btn">W</div><div class="social-btn">f</div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
.etab-card-pub:hover {
    box-shadow: 0 4px 20px rgba(13,158,117,.12);
    border-color: var(--ep-teal-mid) !important;
    transform: translateY(-2px);
}
</style>
@endpush

@push('scripts')
<script>
// ── Filtre établissements publics ──
var allCards = null;

function filtrerEtabsPublic() {
    if (!allCards) allCards = document.querySelectorAll('.etab-card-pub');
    var q    = (document.getElementById('etab-filter').value || '').toLowerCase().trim();
    var type = (document.getElementById('type-filter').value || '').toLowerCase().trim();
    allCards.forEach(function(card) {
        var nom   = card.dataset.nom   || '';
        var ville = card.dataset.ville || '';
        var t     = card.dataset.type  || '';
        var matchQ    = !q    || nom.includes(q) || ville.includes(q);
        var matchType = !type || t === type;
        card.style.display = (matchQ && matchType) ? '' : 'none';
    });
}

// ── Afficher / masquer tous les établissements ──
var etabsLimites = false;
function toggleTousEtabs(btn) {
    if (!allCards) allCards = document.querySelectorAll('.etab-card-pub');
    etabsLimites = !etabsLimites;
    allCards.forEach(function(card, idx) {
        if (idx >= 12) card.style.display = etabsLimites ? '' : 'none';
    });
    btn.textContent = etabsLimites
        ? 'Réduire la liste'
        : 'Voir tous les établissements';
}

// ── Limiter à 12 au chargement si > 12 ──
document.addEventListener('DOMContentLoaded', function() {
    allCards = document.querySelectorAll('.etab-card-pub');
    if (allCards.length > 12) {
        allCards.forEach(function(card, idx) {
            if (idx >= 12) card.style.display = 'none';
        });
    }
});
</script>
@endpush

