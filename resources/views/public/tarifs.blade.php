@extends('layouts.public')

@section('title', __('public.tarifs_title'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/edupay-landing.css') }}">
<style>
.lp-page .ep-body2 { background: transparent; }
</style>
@endpush

@section('content')

@include('layouts._navbar_public')

<div class="lp lp-page">
{{-- ══ HERO SECONDAIRE v3 ══ --}}
<div class="lp-hero-sec">
  <div class="lp-orb lp-orb-a" aria-hidden="true"></div>
  <div class="lp-orb lp-orb-b" aria-hidden="true"></div>
  <div class="lp-hero-sec-inner">
    <div class="lp-tag" style="justify-content:center;">
      <span class="lp-pulse" aria-hidden="true"></span>
      <span class="material-symbols-rounded" aria-hidden="true">sell</span>
      {{ __('public.tarifs_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:34px;">
      @lang('public.tarifs_hero_h1_line1')<br><em>@lang('public.tarifs_hero_h1_em')</em>
    </h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;max-width:560px;">{{ __('public.tarifs_hero_sub') }}</p>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ PLANS D'ABONNEMENT v3 ══ --}}
  <div class="lp-section" style="padding-top:44px;">
    <div class="lp-plans" data-reveal-stagger="80">
      @foreach($plans as $key => $plan)
        @php
          // Dégradé cohérent avec la couleur du plan (modèle Abonnement::PLANS)
          $gradients = [
              '#0D9E75' => ['#0D9E75', '#085041'],
              '#185FA5' => ['#1F6FB2', '#123C66'],
              '#E8A020' => ['#E8A020', '#C9860E'],
          ];
          [$c1, $c2] = $gradients[$plan['couleur']] ?? [$plan['couleur'], $plan['couleur']];
        @endphp
        <div class="lp-plan reveal-on-scroll" style="--lp-couleur: {{ $plan['couleur'] }}; --lp-couleur-ico1: {{ $c1 }}; --lp-couleur-ico2: {{ $c2 }};">
          @if($key === 'standard')
            <span class="lp-plan-badge"><span class="material-symbols-rounded" style="font-size:11px;vertical-align:-2px;">star</span> {{ __('public.plan_populaire') }}</span>
          @endif

          <div class="lp-plan-ico" style="background:linear-gradient(135deg, {{ $c1 }}, {{ $c2 }});">
            <span class="material-symbols-rounded">{{ $key === 'basique' ? 'school' : ($key === 'standard' ? 'domain' : 'account_balance') }}</span>
          </div>

          <div class="lp-plan-nom">{{ $plan['nom'] }}</div>
          <div class="lp-plan-prix">
            {{ number_format($plan['montant'], 0, ',', ' ') }}
            <small>{{ __('public.fcfa_mois') }}</small>
          </div>

          <div class="lp-plan-features">
            <div class="lp-plan-feature">
              <span class="material-symbols-rounded">group</span>
              {{ $plan['max_apprenants'] === -1 ? __('public.apprenants_illimites') : __('public.apprenants_max', ['max' => $plan['max_apprenants']]) }}
            </div>
            <div class="lp-plan-feature">
              <span class="material-symbols-rounded">sms</span>
              {{ $plan['sms_mensuel'] === -1 ? __('public.sms_illimites') : __('public.sms_par_mois', ['nb' => $plan['sms_mensuel']]) }}
            </div>
            <div class="lp-plan-feature {{ $plan['multi_sites'] ? 'inclus' : 'exclu' }}">
              <span class="material-symbols-rounded">{{ $plan['multi_sites'] ? 'check_circle' : 'cancel' }}</span>
              {{ $plan['multi_sites'] ? __('public.multi_sites_inclus') : __('public.multi_sites_non_inclus') }}
            </div>
            <div class="lp-plan-feature {{ $plan['exports_cobac'] ? 'inclus' : 'exclu' }}">
              <span class="material-symbols-rounded">{{ $plan['exports_cobac'] ? 'check_circle' : 'cancel' }}</span>
              {{ $plan['exports_cobac'] ? __('public.exports_inclus') : __('public.exports_non_inclus') }}
            </div>
          </div>

          <a href="{{ route('register.ecole.step1') }}" class="lp-btn lp-btn-main" style="width:100%;padding:12px 20px;font-size:13px;">
            {{ __('public.choisir_plan') }} {{ $plan['nom'] }}
            <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
          </a>
        </div>
      @endforeach
    </div>
  </div>

  {{-- ══ CTA QUESTION + régulateur ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-cta-final reveal-on-scroll" style="max-width:760px;margin:0 auto;text-align:center;justify-content:center;">
      <div style="margin:0 auto;">
        <h3>{{ __('public.question_formules') }}</h3>
        <p style="margin-left:auto;margin-right:auto;">{{ __('public.formules_desc') }}</p>
      </div>
      <div style="width:100%;display:flex;justify-content:center;position:relative;z-index:1;">
        <a href="{{ route('contact') }}" class="lp-btn lp-btn-main">
          {{ __('public.contacter_equipe') }}
          <span class="material-symbols-rounded" aria-hidden="true">forum</span>
        </a>
      </div>
    </div>
    <div class="lp-regulateur reveal-on-scroll">
      <span><span class="material-symbols-rounded" aria-hidden="true">gavel</span>{{ __('public.regulateur_loi') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">account_balance</span>{{ __('public.regulateur_cobac') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">verified_user</span>{{ __('public.regulateur_beac') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">enhanced_encryption</span>{{ __('public.regulateur_donnees') }}</span>
    </div>
  </div>

</div>{{-- /.ep-body2 --}}
</div>{{-- /.lp --}}

{{-- ══ FOOTER PRO v3 (identique aux autres pages publiques) ══ --}}
<footer class="ep-footer lp-footer">
  <div class="lp-footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-logo" style="display:flex;align-items:center;gap:10px;">
          <span style="width:46px;height:46px;border-radius:13px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.2);">
            <img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" />
          </span>
          <span>Edu<span style="color:#5DCAA5;">Pay</span></span>
        </div>
        <div class="footer-desc">{{ __('public.footer_school_brief') }}</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;">
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">lock</span>TLS 1.3</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">verified_user</span>PCI-DSS</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">account_balance</span>COBAC</span>
        </div>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_produit') }}</div>
        <a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a>
        <a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a>
        <a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide') }}</a>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
        <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a>
        <a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support') }}</a>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_contact') }}</div>
        <a class="footer-link" href="mailto:{{ config('mail.contact_address', 'contact@edupay.cm') }}" style="text-transform:none;letter-spacing:0;">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">mail</span>
          {{ config('mail.contact_address', 'contact@edupay.cm') }}
        </a>
        <a class="footer-link" href="{{ route('contact') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">forum</span>
          {{ __('public.footer_contact') }}
        </a>
        <a class="footer-link" href="{{ route('confidentialite') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">policy</span>
          {{ __('public.footer_confidentialite') }}
        </a>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-legal">{{ __('public.footer_legal_brief') }}</div>
      @include('partials.footer-socials')
    </div>
  </div>
</footer>

@endsection
