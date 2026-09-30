@extends('layouts.public')

@section('title', __('public.confid_title'))

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
      <span class="material-symbols-rounded" aria-hidden="true">shield_lock</span>
      {{ __('public.confid_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:32px;">{{ __('public.confid_hero_h1') }}</h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;max-width:520px;">{{ __('public.confid_derniere_maj') }}</p>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ INTRO v3 ══ --}}
  <div class="lp-section" style="padding-top:40px;">
    <div class="lp-legal-note reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">verified_user</span>
      <span>{{ __('public.confid_intro') }}</span>
    </div>
  </div>

  {{-- ══ SECTIONS LÉGALES v3 ══ --}}
  <div class="lp-section">

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">database</span></span>
        {{ __('public.confid_s1_titre') }}
      </div>
      <div class="lp-legal-carte">{!! __('public.confid_s1_texte') !!}</div>
    </div>

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">settings</span></span>
        {{ __('public.confid_s2_titre') }}
      </div>
      <div class="lp-legal-carte">{{ __('public.confid_s2_texte') }}</div>
    </div>

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">lock</span></span>
        {{ __('public.confid_s3_titre') }}
      </div>
      <div class="lp-legal-carte">{!! __('public.confid_s3_texte') !!}</div>
    </div>

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#4C1D95;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">share</span></span>
        {{ __('public.confid_s4_titre') }}
      </div>
      <div class="lp-legal-carte">{{ __('public.confid_s4_texte') }}</div>
    </div>

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#085041;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">rule</span></span>
        {{ __('public.confid_s5_titre') }}
      </div>
      <div class="lp-legal-carte">{{ __('public.confid_s5_texte') }}</div>
    </div>

    <div class="lp-legal-sec reveal-on-scroll" style="--lp-couleur:#D94040;--lp-couleur2:#7E1F1A;">
      <div class="lp-legal-titre">
        <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">contact_mail</span></span>
        {{ __('public.confid_s6_titre') }}
      </div>
      {{-- Lien construit côté serveur (aucune entrée utilisateur) : pas d'échappement --}}
      <div class="lp-legal-carte">{!! __('public.confid_s6_texte', ['link' => '<a href="'.route('contact').'">'.__('public.confid_s6_link_text').'</a>']) !!}</div>
    </div>

  </div>

  {{-- ══ AVIS v3 ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-legal-note note-or reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
      <span>{{ __('public.confid_avis') }}</span>
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
        <a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support') }}</a>
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
