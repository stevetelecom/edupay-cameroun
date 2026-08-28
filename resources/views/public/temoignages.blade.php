@extends('layouts.public')

@section('title', __('public.temoignages_title'))

@section('content')

@include('layouts._navbar_public')
<div class="hero-band">
  <div style="padding:32px 28px 24px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">{{ __('public.temo_hero_tag') }}</div>
    <div style="font-size:26px;font-weight:700;color:#fff;margin:10px 0 8px;">{{ __('public.temo_hero_h1_line1') }}<br>{{ __('public.temo_hero_h1_line2') }} <em style="font-style:normal;color:#5DCAA5;">{{ __('public.temo_hero_h1_em') }}</em></div>
    <div style="display:flex;justify-content:center;gap:24px;margin-top:18px;flex-wrap:wrap;" data-stats-container>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.etabs_actifs') }}</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.apprenants_inscrits') }}</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.paiements_valides') }}</div>
      </div>
      <div style="text-align:center;">
        <div style="font-size:22px;font-weight:700;color:#5DCAA5;" class="stat-counter" data-count="{{ $stats['montant_total'] }}" data-suffix=" FCFA">0 FCFA</div>
        <div style="font-size:11px;color:rgba(255,255,255,.5);">{{ __('public.fcfa_collectes') }}</div>
      </div>
    </div>
    <div style="margin-top:14px;font-size:11px;color:rgba(255,255,255,.4);font-style:italic;">
      {{ __('public.temo_pilote') }}
    </div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">{{ __('public.directeurs_admins') }}</div>
  <div class="g2" style="margin-bottom:20px;">

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_1_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-teal-lt);color:#085041;width:44px;height:44px;">DM</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_1_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_1_ecole') }}</div><span class="pill pg" style="font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_1_pill') }}</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_2_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-gold-lt);color:#8B5E10;width:44px;height:44px;">CF</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_2_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_2_ecole') }}</div><span class="pill pa" style="font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_2_pill') }}</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★☆</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_3_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-blue-lt);color:#1A4F8A;width:44px;height:44px;">PN</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_3_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_3_ecole') }}</div><span class="pill pb" style="font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_3_pill') }}</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_4_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-purple-lt);color:#5B21B6;width:44px;height:44px;">AN</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_4_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_4_ecole') }}</div><span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_4_pill') }}</span></div>
      </div>
    </div>

  </div>

  <div class="seclbl">{{ __('public.parents_etudiants') }}</div>
  <div class="g2" style="margin-bottom:20px;">

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_5_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-teal-lt);color:#085041;width:44px;height:44px;">BT</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_5_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_5_ecole') }}</div><span class="pill pg" style="font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_5_pill') }}</span></div>
      </div>
    </div>

    <div class="testi-card">
      <div class="stars">★★★★★</div>
      <div class="testi-quote">"</div>
      <div class="testi-text">{{ __('public.temo_6_texte') }}</div>
      <div class="testi-author">
        <div class="av" style="background:var(--ep-gold-lt);color:#8B5E10;width:44px;height:44px;">KA</div>
        <div><div style="font-size:13px;font-weight:700;">{{ __('public.temo_6_auteur') }}</div><div style="font-size:11px;color:#888;">{{ __('public.temo_6_ecole') }}</div><span class="pill pa" style="font-size:10px;margin-top:4px;display:inline-block;">{{ __('public.temo_6_pill') }}</span></div>
      </div>
    </div>

  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:32px 28px;text-align:center;margin-bottom:4px;">
    <div style="font-size:20px;font-weight:700;color:#fff;margin-bottom:8px;">{{ __('public.rejoignez_etabs') }}</div>
    <div style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:22px;">{{ __('public.inscription_gratuite_support') }}</div>
    <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
      <a href="{{ route('register.parent.step1') }}" class="hbtn-main">{{ __('public.cta_creer_compte_payeur') }}</a>
      <a href="{{ route('register.ecole.step1') }}" class="hbtn-ghost">{{ __('public.cta_inscrire_etablissement') }}</a>
    </div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">{{ __('public.footer_school_brief') }}</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_produit') }}</div><a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a><a class="footer-link" href="{{ route('about') }}">{{ __('public.footer_a_propos') }}</a><a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a><a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_legal') }}</div><a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">{{ __('public.footer_legal_brief') }}</div><div class="footer-socials">
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="LinkedIn" title="LinkedIn" style="background:#0A66C2;border-color:#0A66C2;color:#fff;">
    <i class="fa-brands fa-linkedin-in"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="X (Twitter)" title="X" style="background:#000;border-color:#000;color:#fff;">
    <i class="fa-brands fa-x-twitter"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="WhatsApp" title="WhatsApp" style="background:#25D366;border-color:#25D366;color:#fff;">
    <i class="fa-brands fa-whatsapp"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook" title="Facebook" style="background:#1877F2;border-color:#1877F2;color:#fff;">
    <i class="fa-brands fa-facebook-f"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram" title="Instagram" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);border:none;color:#fff;">
    <i class="fa-brands fa-instagram"></i>
  </a>
</div></div>
</div>

@endsection
