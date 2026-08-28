@extends('layouts.public')

@section('title', __('public.tarifs_title'))

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div class="hero-main">
    <div class="hero-tag" style="justify-content:center;display:inline-flex;">
      <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span>
      {{ __('public.tarifs_hero_tag') }}
    </div>
    <div class="hero-h1">@lang('public.tarifs_hero_h1_line1')<br><span style="color:#5DCAA5;">@lang('public.tarifs_hero_h1_em')</span></div>
    <div class="hero-sub" style="margin:0 auto;max-width:560px;">
      {{ __('public.tarifs_hero_sub') }}
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
          <span style="font-size:12px;font-weight:500;color:#999;">{{ __('public.fcfa_mois') }}</span>
        </div>
        <div style="font-size:13px;color:#555;line-height:2.2;">
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:#9CA3AF;">group</span>
            {{ $plan['max_apprenants'] === -1 ? __('public.apprenants_illimites') : $plan['max_apprenants'].' '.__('public.apprenants_max') }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:#9CA3AF;">sms</span>
            {{ $plan['sms_mensuel'] === -1 ? __('public.sms_illimites') : $plan['sms_mensuel'].' '.__('public.sms_par_mois') }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:{{ $plan['multi_sites'] ? '#0D9E75' : '#D1D5DB' }};">apartment</span>
            {{ $plan['multi_sites'] ? __('public.multi_sites_inclus') : __('public.multi_sites_non_inclus') }}
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:17px;color:{{ $plan['exports_cobac'] ? '#0D9E75' : '#D1D5DB' }};">bar_chart</span>
            Exports COBAC {{ $plan['exports_cobac'] ? __('public.exports_inclus') : __('public.exports_non_inclus') }}
          </div>
        </div>
        <a href="{{ route('register.ecole.step1') }}" class="btn-p" style="margin-top:20px;display:block;text-align:center;text-decoration:none;">
          {{ __('public.choisir_plan') }} {{ $plan['nom'] }}
        </a>
      </div>
    @endforeach
  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:28px;text-align:center;max-width:700px;margin:0 auto;">
    <div style="font-size:15px;color:#fff;margin-bottom:6px;font-weight:600;">{{ __('public.question_formules') }}</div>
    <div style="font-size:13px;color:rgba(255,255,255,.55);margin-bottom:18px;">{{ __('public.formules_desc') }}</div>
    <a href="{{ route('contact') }}" class="hbtn-main">{{ __('public.contacter_equipe') }}</a>
  </div>

</div>

{{-- Footer identique aux autres pages publiques --}}
<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">Edu<span>Pay</span> Cameroun</div>
      <div class="footer-desc">{{ __('public.footer_school_brief') }}</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_produit') }}</div>
      <a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a>
      <a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a>
      <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_legal') }}</div>
      <a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a>
      <a class="footer-link" href="{{ route('cgu') }}">CGU</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-legal">{{ __('public.footer_legal_brief') }}</div>
    <div class="footer-socials">
      <div class="social-btn">in</div><div class="social-btn">X</div>
      <div class="social-btn">W</div><div class="social-btn">f</div>
    </div>
  </div>
</div>

@endsection
