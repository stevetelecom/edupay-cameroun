@extends('layouts.public')

@section('title', __('public.temoignages_title'))

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
{{-- ══ HERO SECONDAIRE v3 : orbes + badge + stats glassmorphism ══ --}}
<div class="lp-hero-sec">
  <div class="lp-orb lp-orb-a" aria-hidden="true"></div>
  <div class="lp-orb lp-orb-b" aria-hidden="true"></div>
  <div class="lp-hero-sec-inner">
    <div class="lp-tag" style="justify-content:center;">
      <span class="lp-pulse" aria-hidden="true"></span>
      <span class="material-symbols-rounded" aria-hidden="true">forum</span>
      {{ __('public.temo_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:32px;">
      {{ __('public.temo_hero_h1_line1') }}<br>
      {{ __('public.temo_hero_h1_line2') }} <em>{{ __('public.temo_hero_h1_em') }}</em>
    </h1>
    {{-- Stats glassmorphism --}}
    <div class="lp-hero-sec-stats" data-stats-container>
      <div class="lp-hero-sec-stat">
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.etabs_actifs') }}</div>
      </div>
      <div class="lp-hero-sec-stat">
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.apprenants_inscrits') }}</div>
      </div>
      <div class="lp-hero-sec-stat">
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.paiements_valides') }}</div>
      </div>
      <div class="lp-hero-sec-stat">
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['montant_total'] }}" data-suffix=" FCFA">0 FCFA</div>
        <div class="lp-stat-l">{{ __('public.fcfa_collectes') }}</div>
      </div>
    </div>
    <div style="margin-top:16px;font-size:11px;color:rgba(255,255,255,.45);font-style:italic;">
      {{ __('public.temo_pilote') }}
    </div>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ DIRECTEURS & ADMINISTRATEURS v3 ══ --}}
  <div class="lp-section" style="padding-top:44px;">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">badge</span>
      {{ __('public.directeurs_admins') }}
    </div>
    <div class="lp-feats" style="grid-template-columns:1fr 1fr;" data-reveal-stagger="70">

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#9FE1CB;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_1_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;">DM</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_1_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_1_ecole') }}</div>
            <span class="lp-temo-badge"><span class="material-symbols-rounded" style="font-size:12px;">school</span>{{ __('public.temo_1_pill') }}</span>
          </div>
        </div>
      </div>

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#F5C86A;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_2_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">CF</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_2_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_2_ecole') }}</div>
            <span class="lp-temo-badge" style="background:var(--ep-gold-lt);color:#8B5E10;"><span class="material-symbols-rounded" style="font-size:12px;">savings</span>{{ __('public.temo_2_pill') }}</span>
          </div>
        </div>
      </div>

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#9FC4DB;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded etoile-vide">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_3_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">PN</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_3_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_3_ecole') }}</div>
            <span class="lp-temo-badge" style="background:var(--ep-blue-lt);color:#1A4F8A;"><span class="material-symbols-rounded" style="font-size:12px;">account_balance</span>{{ __('public.temo_3_pill') }}</span>
          </div>
        </div>
      </div>

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#C4B5FD;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_4_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#7C3AED;--lp-couleur2:#4C1D95;">AN</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_4_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_4_ecole') }}</div>
            <span class="lp-temo-badge" style="background:var(--ep-purple-lt);color:#5B21B6;"><span class="material-symbols-rounded" style="font-size:12px;">history_edu</span>{{ __('public.temo_4_pill') }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- ══ PARENTS & ÉTUDIANTS v3 ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">family_restroom</span>
      {{ __('public.parents_etudiants') }}
    </div>
    <div class="lp-feats" style="grid-template-columns:1fr 1fr;" data-reveal-stagger="70">

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#9FE1CB;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_5_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#0D9E75;--lp-couleur2:#085041;">BT</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_5_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_5_ecole') }}</div>
            <span class="lp-temo-badge"><span class="material-symbols-rounded" style="font-size:12px;">family_restroom</span>{{ __('public.temo_5_pill') }}</span>
          </div>
        </div>
      </div>

      <div class="lp-temoignage reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#F5C86A;">
        <div class="lp-temo-etoiles">
          <span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span><span class="material-symbols-rounded">star</span>
        </div>
        <p class="lp-temo-texte">{{ __('public.temo_6_texte') }}</p>
        <div class="lp-temo-auteur">
          <div class="lp-temo-avatar" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">KA</div>
          <div>
            <div class="lp-temo-nom">{{ __('public.temo_6_auteur') }}</div>
            <div class="lp-temo-ecole">{{ __('public.temo_6_ecole') }}</div>
            <span class="lp-temo-badge" style="background:var(--ep-gold-lt);color:#8B5E10;"><span class="material-symbols-rounded" style="font-size:12px;">person</span>{{ __('public.temo_6_pill') }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- ══ CTA FINAL v3 + régulateur ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-cta-final reveal-on-scroll" style="text-align:center;justify-content:center;">
      <div style="margin:0 auto;">
        <h3>{{ __('public.rejoignez_etabs') }}</h3>
        <p style="margin-left:auto;margin-right:auto;">{{ __('public.inscription_gratuite_support') }}</p>
      </div>
      <div style="width:100%;display:flex;justify-content:center;gap:12px;flex-wrap:wrap;position:relative;z-index:1;">
        <a href="{{ route('register.parent.step1') }}" class="lp-btn lp-btn-main">
          {{ __('public.cta_creer_compte_payeur') }}
          <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
        </a>
        <a href="{{ route('register.ecole.step1') }}" class="lp-btn lp-btn-ghost">{{ __('public.cta_inscrire_etablissement') }}</a>
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

{{-- ══ FOOTER PRO v3 (identique à la landing) ══ --}}
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
        <a class="footer-link" href="{{ route('about') }}">{{ __('public.footer_a_propos') }}</a>
        <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
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
