@extends('layouts.public')

@section('title', __('public.support_title'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/edupay-landing.css') }}">
<style>
/* Corps des pages secondaires : suit le thème clair/sombre */
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
      <span class="material-symbols-rounded" aria-hidden="true">headset_mic</span>
      {{ __('public.support_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:34px;">
      {{ __('public.support_hero_h1') }}
    </h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;max-width:520px;">{{ __('public.support_hero_sub') }}</p>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ FAQ v3 : grille de cartes .lp-valeur avec pastilles dégradées ══ --}}
  <div class="lp-section" style="padding-top:44px;">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">quiz</span>
      {{ __('public.questions_frequentes') }}
    </div>
    <h2 class="lp-sectitre reveal-on-scroll">{{ __('public.questions_frequentes') }}</h2>

    <div class="lp-feats lp-faq" data-reveal-stagger="60">
      @php
        // 6 questions : pastille colorée par thème (teal, bleu, or, violet, teal, rouge)
        $faqs = [
            1 => ['ico' => 'payments',          'c1' => '#0D9E75', 'c2' => '#0A8562'],
            2 => ['ico' => 'credit_card',       'c1' => '#1F6FB2', 'c2' => '#123C66'],
            3 => ['ico' => 'receipt_long',      'c1' => '#E8A020', 'c2' => '#C9860E'],
            4 => ['ico' => 'event_repeat',      'c1' => '#7C3AED', 'c2' => '#4C1D95'],
            5 => ['ico' => 'date_range',        'c1' => '#0D9E75', 'c2' => '#085041'],
            6 => ['ico' => 'encrypted',         'c1' => '#D94040', 'c2' => '#7E1F1A'],
        ];
      @endphp
      @foreach($faqs as $n => $cfg)
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur: {{ $cfg['c1'] }}; --lp-couleur2: {{ $cfg['c2'] }};">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">{{ $cfg['ico'] }}</span></div>
        <b>{{ __("public.faq_{$n}_q") }}</b>
        <small>{{ __("public.faq_{$n}_a") }}</small>
      </div>
      @endforeach
    </div>
  </div>

  {{-- ══ COORDONNÉES v3 : 4 cartes pastilles dégradées ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">contact_support</span>
      {{ __('public.contactez_directement') }}
    </div>

    <div class="lp-coords" data-reveal-stagger="60">
      <div class="lp-carte-contact reveal-on-scroll">
        <div class="lp-coord" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;padding:0;border:none;background:transparent;">
          <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">place</span></span>
          <div>
            <b>{{ __('public.adresse') }}</b>
            <small>{{ __('public.adresse_val') }}</small>
          </div>
        </div>
      </div>
      <div class="lp-carte-contact reveal-on-scroll">
        <div class="lp-coord" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;padding:0;border:none;background:transparent;">
          <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">call</span></span>
          <div>
            <b>{{ __('public.telephone_label') }}</b>
            <small>+237 654 862 989<br>+237 688 462 229</small>
          </div>
        </div>
      </div>
      <div class="lp-carte-contact reveal-on-scroll">
        <div class="lp-coord" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;padding:0;border:none;background:transparent;">
          <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">mail</span></span>
          <div>
            <b>{{ __('public.email_label') }}</b>
            <small>{{ __('public.email_val') }}</small>
          </div>
        </div>
      </div>
      <div class="lp-carte-contact reveal-on-scroll">
        <div class="lp-coord" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;padding:0;border:none;background:transparent;">
          <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">schedule</span></span>
          <div>
            <b>{{ __('public.disponibilite') }}</b>
            <small>{{ __('public.disponibilite_val') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ CTA CONTACT v3 ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-cta-final reveal-on-scroll" style="max-width:760px;margin:0 auto;text-align:center;justify-content:center;">
      <div style="margin:0 auto;">
        <h3>{{ __('public.contact_hero_h1_line1') }} {{ __('public.contact_hero_h1_line2') }}</h3>
        <p style="margin-left:auto;margin-right:auto;">{{ __('public.support_hero_sub') }}</p>
      </div>
      <div style="width:100%;display:flex;justify-content:center;position:relative;z-index:1;">
        <a href="{{ route('contact') }}" class="lp-btn lp-btn-main">
          {{ __('public.envoyer_un_message') }}
          <span class="material-symbols-rounded" aria-hidden="true">send</span>
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
        <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
        <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a>
        <a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide') }}</a>
        <a class="footer-link" href="{{ route('contact') }}">{{ __('public.footer_contact') }}</a>
      </div>
    <div>
        <div class="footer-col-title">{{ __('public.footer_col_contact') }}</div>
        <a class="footer-link" href="mailto:{{ config('mail.contact_address', 'contact@edupay.cm') }}" style="text-transform:none;letter-spacing:0;">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">mail</span>
          {{ config('mail.contact_address', 'contact@edupay.cm') }}
        </a>
        <a class="footer-link" href="{{ route('confidentialite') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">policy</span>
          {{ __('public.footer_confidentialite') }}
        </a>
        <a class="footer-link" href="{{ route('cgu') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">description</span>
          {{ __('public.footer_conditions') }}
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
