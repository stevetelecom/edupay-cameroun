@extends('layouts.public')

@section('title', __('public.guide_title'))

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
      <span class="material-symbols-rounded" aria-hidden="true">menu_book</span>
      {{ __('public.guide_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:32px;">{!! __('public.guide_hero_h1') !!}</h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;max-width:520px;">{{ __('public.guide_hero_sub') }}</p>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ LES 6 ÉTAPES v3 : timeline numérotée animée ══ --}}
  <div class="lp-section" style="padding-top:44px;">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">route</span>
      {{ __('public.guide_6_etapes') }}
    </div>
    <div class="lp-etapes" data-reveal-stagger="70">

      @php
        $etapes = [
            1 => ['c1' => '#0D9E75', 'c2' => '#085041', 'ico' => 'app_registration'],
            2 => ['c1' => '#E8A020', 'c2' => '#C9860E', 'ico' => 'fact_check'],
            3 => ['c1' => '#1F6FB2', 'c2' => '#123C66', 'ico' => 'cloud_upload'],
            4 => ['c1' => '#7C3AED', 'c2' => '#4C1D95', 'ico' => 'manage_accounts'],
            5 => ['c1' => '#0D9E75', 'c2' => '#0A8562', 'ico' => 'payments'],
            6 => ['c1' => '#D94040', 'c2' => '#7E1F1A', 'ico' => 'query_stats'],
        ];
      @endphp

      @foreach($etapes as $n => $cfg)
      <div class="lp-etape reveal-on-scroll" style="--lp-couleur: {{ $cfg['c1'] }}; --lp-couleur2: {{ $cfg['c2'] }};">
        <div class="lp-etape-num">{{ $n }}</div>
        <div style="flex:1;">
          <div class="lp-etape-titre">
            <span class="material-symbols-rounded" aria-hidden="true">{{ $cfg['ico'] }}</span>
            {{ __("public.etape{$n}_titre") }}
          </div>
          <div class="lp-etape-desc">{{ __("public.etape{$n}_desc") }}</div>
          @if($n === 1)
            <a href="{{ route('register.ecole.step1') }}" class="lp-etape-lien">
              {{ __('public.inscrire_etablissement_lien') }}
              <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
            </a>
          @endif
        </div>
      </div>
      @endforeach

    </div>
  </div>

  {{-- ══ FONCTIONNALITÉS BACK-OFFICE v3 : 8 cartes avec pastilles ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">dashboard_customize</span>
      {{ __('public.fonctionnalites_backoffice') }}
    </div>
    <div class="lp-feats" style="grid-template-columns:repeat(4,1fr);gap:14px;" data-reveal-stagger="60">
      @php
        $fonctions = [
            ['apprenants',    'person_search',    '#0D9E75', '#085041'],
            ['frais',         'request_quote',    '#E8A020', '#C9860E'],
            ['paiements',     'payments',         '#1F6FB2', '#123C66'],
            ['impayes',       'notifications_active', '#7C3AED', '#4C1D95'],
            ['rapports',      'summarize',        '#0D9E75', '#0A8562'],
            ['remboursements','currency_exchange','#E8A020', '#8B5E10'],
            ['multisites',    'domain',           '#1F6FB2', '#185FA5'],
            ['users',         'group_add',        '#7C3AED', '#6D28D9'],
        ];
      @endphp
      @foreach($fonctions as [$cle, $ico, $c1, $c2])
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur: {{ $c1 }}; --lp-couleur2: {{ $c2 }};">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">{{ $ico }}</span></div>
        <b>{{ __("public.feat_{$cle}_titre") }}</b>
        <small>{{ __("public.feat_{$cle}_desc") }}</small>
      </div>
      @endforeach
    </div>
  </div>

  {{-- ══ CTA AIDE v3 ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-cta-final reveal-on-scroll" style="max-width:760px;margin:0 auto;text-align:center;justify-content:center;">
      <div style="margin:0 auto;">
        <h3>{{ __('public.besoin_aide') }}</h3>
        <p style="margin-left:auto;margin-right:auto;">{{ __('public.support_repond_desc') }}</p>
      </div>
      <div style="width:100%;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;position:relative;z-index:1;">
        <a href="{{ route('support') }}" class="lp-btn lp-btn-main">
          {{ __('public.voir_faq') }}
          <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
        </a>
        <a href="{{ route('contact') }}" class="lp-btn lp-btn-ghost">{{ __('public.contacter_support') }}</a>
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
        <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
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
      <div class="footer-legal">{{ __('public.footer_legal') }}</div>
      @include('partials.footer-socials')
    </div>
  </div>
</footer>

@endsection
