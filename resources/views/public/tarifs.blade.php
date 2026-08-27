@extends('layouts.public')

@section('title', 'Tarifs — EduPay Cameroun')

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div class="hero-main">
    <div class="hero-tag" style="justify-content:center;display:inline-flex;">
      <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span>
      Nos formules
    </div>
    <div class="hero-h1">Une tarification<br><span style="color:#5DCAA5;">simple et transparente.</span></div>
    <div class="hero-sub" style="margin:0 auto;max-width:560px;">
      Choisissez la formule adaptée à la taille de votre établissement. Sans engagement, activation sous 24h.
    </div>
  </div>
</div>

<div class="ep-body2">

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;max-width:900px;margin:0 auto 32px;">
    @foreach($plans as $key => $plan)
      <div class="epcard" style="border-top:4px solid {{ $plan['couleur'] }};padding:24px;">
        <div style="font-size:16px;font-weight:700;color:#111;margin-bottom:4px;">{{ $plan['nom'] }}</div>
        <div style="font-size:28px;font-weight:700;color:{{ $plan['couleur'] }};margin-bottom:16px;">
          {{ number_format($plan['montant'], 0, ',', ' ') }}
          <span style="font-size:12px;font-weight:500;color:#999;">FCFA / mois</span>
        </div>
        <div style="font-size:13px;color:#555;line-height:2.2;">
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:#9CA3AF;">group</span>
            {{ $plan['max_apprenants'] === -1 ? 'Apprenants illimités' : $plan['max_apprenants'].' apprenants max' }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:#9CA3AF;">sms</span>
            {{ $plan['sms_mensuel'] === -1 ? 'SMS illimités' : $plan['sms_mensuel'].' SMS / mois' }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:{{ $plan['multi_sites'] ? '#0D9E75' : '#D1D5DB' }};">apartment</span>
            Multi-sites {{ $plan['multi_sites'] ? 'inclus' : 'non inclus' }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:{{ $plan['exports_cobac'] ? '#0D9E75' : '#D1D5DB' }};">bar_chart</span>
            Exports COBAC {{ $plan['exports_cobac'] ? 'inclus' : 'non inclus' }}
          </div>
        </div>
        <a href="{{ route('register.ecole.step1') }}" class="btn-p" style="margin-top:20px;display:block;text-align:center;text-decoration:none;">
          Choisir {{ $plan['nom'] }}
        </a>
      </div>
    @endforeach
  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:28px;text-align:center;max-width:700px;margin:0 auto;">
    <div style="font-size:15px;color:#fff;margin-bottom:6px;font-weight:600;">Une question sur nos formules ?</div>
    <div style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:18px;">Notre équipe vous accompagne dans le choix du plan adapté à votre établissement.</div>
    <a href="{{ route('contact') }}" class="hbtn-main">Contacter notre équipe</a>
  </div>

</div>

{{-- Footer identique aux autres pages publiques --}}
<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">Edu<span>Pay</span> Cameroun</div>
      <div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">Produit</div>
      <a class="footer-link" href="{{ route('landing') }}">Accueil</a>
      <a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a>
      <a class="footer-link" href="{{ route('tarifs') }}">Tarifs</a>
    </div>
    <div>
      <div class="footer-col-title">Établissements</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a>
    </div>
    <div>
      <div class="footer-col-title">Légal</div>
      <a class="footer-link" href="#">Confidentialité</a>
      <a class="footer-link" href="#">CGU</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-legal">© 2026 EduPay Cameroun</div>
    <div class="footer-socials">
      <div class="social-btn">in</div><div class="social-btn">X</div>
      <div class="social-btn">W</div><div class="social-btn">f</div>
    </div>
  </div>
</div>

@endsection
