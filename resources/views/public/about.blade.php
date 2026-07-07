@extends('layouts.public')

@section('title', 'À propos — EduPay Cameroun')

@section('content')

@include('layouts._navbar_public')
<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">Notre histoire &amp; notre mission</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">À propos d'<em style="font-style:normal;color:#5DCAA5;">EduPay Cameroun</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:500px;margin:0 auto;line-height:1.7;">Nés d'un constat terrain, nous construisons l'infrastructure de paiement scolaire que le Cameroun méritait depuis longtemps.</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">Notre mission</div>
  <div class="mission-card" style="margin-bottom:16px;">
    <div style="font-size:15px;font-weight:700;margin-bottom:8px;">Digitaliser la gestion financière des établissements scolaires camerounais</div>
    <div style="font-size:13px;color:#555;line-height:1.75;">Le projet EduPay Cameroun est né d'un constat terrain effectué auprès de plusieurs dizaines d'établissements scolaires : la quasi-totalité gère encore les paiements de façon entièrement manuelle, en espèces, avec tous les risques que cela implique — détournements, files d'attente, perte de reçus, impossibilité de suivi en temps réel.</div>
  </div>

  <div class="seclbl">Nos valeurs</div>
  <div class="g2" style="margin-bottom:20px;">
    <div class="value-card" style="border-left-color:var(--ep-teal);"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">Accessibilité</div><div style="font-size:12px;color:#666;line-height:1.6;">Conçu pour fonctionner sur 2G/3G, en zone rurale, sur tout smartphone Android ou iOS.</div></div>
    <div class="value-card" style="border-left-color:var(--ep-gold);"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">Sécurité absolue</div><div style="font-size:12px;color:#666;line-height:1.6;">Chaque transaction est chiffrée, tracée et conforme aux normes COBAC, BEAC et PCI-DSS.</div></div>
    <div class="value-card" style="border-left-color:#185FA5;"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">Ancrage local</div><div style="font-size:12px;color:#666;line-height:1.6;">Pensé pour le contexte africain, intégrant nativement MTN Mobile Money et Orange Money.</div></div>
    <div class="value-card" style="border-left-color:#7C3AED;"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">Impact social</div><div style="font-size:12px;color:#666;line-height:1.6;">Réduire les fraudes scolaires, améliorer le recouvrement, faciliter l'accès à l'éducation.</div></div>
  </div>

  <div class="seclbl">Contexte au Cameroun</div>
  <div class="g4" style="margin-bottom:20px;">
    <div class="kpi"><div class="kval">30 000+</div><div class="klbl">Établissements d'enseignement</div></div>
    <div class="kpi"><div class="kval">6 000 000</div><div class="klbl">Apprenants du maternel au sup.</div></div>
    <div class="kpi"><div class="kval">12 000 000</div><div class="klbl">Abonnés Mobile Money (2024)</div></div>
    <div class="kpi"><div class="kval">45%</div><div class="klbl">Taux pénétration smartphone</div></div>
  </div>

  <div class="seclbl">EduPay en chiffres</div>
  <div class="g4" style="margin-bottom:20px;">
    <div class="kpi" style="background:var(--ep-teal-lt);border:1px solid rgba(13,158,117,.15);">
      <div class="kval" style="color:var(--ep-teal);">{{ $stats['nb_etablissements'] }}</div>
      <div class="klbl">Établissements actifs</div>
    </div>
    <div class="kpi" style="background:var(--ep-blue-lt);border:1px solid rgba(24,95,165,.15);">
      <div class="kval" style="color:#185FA5;">{{ number_format($stats['nb_apprenants'], 0, ',', ' ') }}</div>
      <div class="klbl">Apprenants inscrits</div>
    </div>
    <div class="kpi" style="background:var(--ep-gold-lt);border:1px solid rgba(232,160,32,.15);">
      <div class="kval" style="color:#854F0B;">{{ number_format($stats['nb_paiements'], 0, ',', ' ') }}</div>
      <div class="klbl">Paiements validés</div>
    </div>
    <div class="kpi" style="background:#F3F4F6;border:1px solid #E5E7EB;">
      <div class="kval" style="color:#374151;">
        @if($stats['montant_total'] >= 1000000)
          {{ number_format($stats['montant_total'] / 1000000, 1, ',', ' ') }}M
        @else
          {{ number_format($stats['montant_total'], 0, ',', ' ') }}
        @endif
      </div>
      <div class="klbl">FCFA collectés</div>
    </div>
  </div>

  <div class="seclbl">L'équipe projet — Groupes 14 &amp; 15 · GSI · ESTLC Ambam</div>
  <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;">
    <div class="team-card"><div class="team-av" style="background:var(--ep-teal-lt);color:#085041;">MO</div><div style="font-size:12px;font-weight:700;">MEKONTSO OLIVIER</div><div style="font-size:10px;color:#888;margin-top:3px;">Chef de projet</div><span class="pill pg" style="margin-top:6px;font-size:10px;">Lead</span></div>
    <div class="team-card"><div class="team-av" style="background:var(--ep-blue-lt);color:#1A4F8A;">WN</div><div style="font-size:12px;font-weight:700;">WANDJI NGUELE</div><div style="font-size:10px;color:#888;margin-top:3px;">Dev Back-end</div><span class="pill pb" style="margin-top:6px;font-size:10px;">API</span></div>
    <div class="team-card"><div class="team-av" style="background:var(--ep-purple-lt);color:#5B21B6;">EB</div><div style="font-size:12px;font-weight:700;">EBODE BIKORO</div><div style="font-size:10px;color:#888;margin-top:3px;">Dev Front-end</div><span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;margin-top:6px;font-size:10px;">UI</span></div>
    <div class="team-card"><div class="team-av" style="background:var(--ep-gold-lt);color:#8B5E10;">MN</div><div style="font-size:12px;font-weight:700;">MAKUETA NGAMBA</div><div style="font-size:10px;color:#888;margin-top:3px;">Dev École</div><span class="pill pa" style="margin-top:6px;font-size:10px;">Back-office</span></div>
    <div class="team-card"><div class="team-av" style="background:var(--ep-red-lt);color:#9B2C2C;">MN</div><div style="font-size:12px;font-weight:700;">MAFFO NDJOUMESSI</div><div style="font-size:10px;color:#888;margin-top:3px;">QA / DevOps</div><span class="pill pr" style="margin-top:6px;font-size:10px;">Tests</span></div>
  </div>

  <div class="seclbl">Feuille de route 2026</div>
  <div class="epcard" style="margin-bottom:4px;">
    <div class="row"><div style="display:flex;align-items:center;gap:12px;"><span class="pill pg">Phase 1 — Terminée</span><span>Cadrage, CDC, maquettes UI/UX</span></div><span style="font-size:12px;color:#999;">Fév. 2026</span></div>
    <div class="row"><div style="display:flex;align-items:center;gap:12px;"><span class="pill pa">Phase 2 — En cours</span><span>Développement MVP (Laravel + Livewire)</span></div><span style="font-size:12px;color:#999;">Mars–Avr. 2026</span></div>
    <div class="row"><div style="display:flex;align-items:center;gap:12px;"><span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;">Phase 3</span><span>Intégrations MTN MoMo &amp; Orange Money</span></div><span style="font-size:12px;color:#999;">Mai 2026</span></div>
    <div class="row"><div style="display:flex;align-items:center;gap:12px;"><span class="pill pr">Phase 4</span><span>Pilote — 5 à 10 établissements partenaires</span></div><span style="font-size:12px;color:#999;">Juin 2026</span></div>
    <div class="row"><div style="display:flex;align-items:center;gap:12px;"><span class="pill pb">Phase 5</span><span>Déploiement général — Cameroun</span></div><span style="font-size:12px;color:#999;">Juil. 2026+</span></div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo">Edu<span>Pay</span> Cameroun</div><div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">Produit</div><a class="footer-link" href="{{ route('landing') }}">Accueil</a><a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a></div>
    <div><div class="footer-col-title">Établissements</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a><a class="footer-link" href="{{ route('support') }}">Support</a></div>
    <div><div class="footer-col-title">Légal</div><a class="footer-link" href="{{ route('confidentialite') }}">Confidentialité</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div><div class="footer-socials"><div class="social-btn">in</div><div class="social-btn">X</div><div class="social-btn">W</div><div class="social-btn">f</div></div></div>
</div>

@endsection
