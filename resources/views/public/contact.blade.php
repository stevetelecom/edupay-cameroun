@extends('layouts.public')

@section('title', __('public.contact_title'))

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
      <span class="material-symbols-rounded" aria-hidden="true">support_agent</span>
      {{ __('public.contact_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:34px;">
      {{ __('public.contact_hero_h1_line1') }}<br><em>{{ __('public.contact_hero_h1_line2') }}</em>
    </h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;max-width:560px;">{{ __('public.contact_hero_sub') }}</p>
  </div>
</div>

<div class="ep-body2">
  <div class="lp-section" style="padding-top:44px;">
    <form method="POST" action="{{ route('contact.submit') }}">
      @csrf
      <div class="lp-contact-g">

        {{-- ══ Colonne gauche : coordonnées + pourquoi ══ --}}
        <div style="display:grid;gap:18px;">
          <div class="lp-carte-contact reveal-on-scroll">
            <div class="lp-carte-contact-titre">
              <span class="material-symbols-rounded" aria-hidden="true">contacts</span>
              {{ __('public.informations_contact') }}
            </div>
            <div class="lp-coord" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;">
              <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">place</span></span>
              <div>
                <b>{{ __('public.adresse') }}</b>
                <small>{!! __('public.adresse_val') !!}</small>
              </div>
            </div>
            <div class="lp-coord" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">
              <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">call</span></span>
              <div>
                <b>{{ __('public.telephone_label') }}</b>
                <small>+237 654 862 989<br>+237 688 462 229</small>
              </div>
            </div>
            <div class="lp-coord" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">
              <span class="lp-coord-ico"><span class="material-symbols-rounded" aria-hidden="true">email</span></span>
              <div>
                <b>{{ __('public.email_label') }}</b>
                <small>{{ __('public.email_val') }}</small>
              </div>
            </div>
          </div>

          <div class="lp-carte-contact reveal-on-scroll">
            <div class="lp-carte-contact-titre">
              <span class="material-symbols-rounded" aria-hidden="true">help</span>
              {{ __('public.pourquoi_nous_contacter') }}
            </div>
            <p class="lp-coord-desc">{{ __('public.pourquoi_nous_desc') }}</p>
          </div>
        </div>

        {{-- ══ Colonne droite : formulaire v3 ══ --}}
        <div class="lp-carte-contact reveal-on-scroll">
          <div class="lp-carte-contact-titre" style="font-size:17px;">
            <span class="material-symbols-rounded" aria-hidden="true">forward_to_inbox</span>
            {{ __('public.envoyez_message') }}
          </div>
          <p class="lp-coord-desc">{{ __('public.formulaire_desc') }}</p>

          <div class="lp-champs">
            <div>
              <div class="lp-lbl" style="--lp-lbl-c:#0D9E75;">
                <span class="material-symbols-rounded" aria-hidden="true">person</span>
                {{ __('public.nom_complet') }}
              </div>
              <input class="lp-input" style="padding-left:14px;" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('public.votre_nom') }}" />
              @error('name')<div class="lp-err">{{ $message }}</div>@enderror
            </div>

            <div>
              <div class="lp-lbl" style="--lp-lbl-c:#185FA5;">
                <span class="material-symbols-rounded" aria-hidden="true">mail</span>
                {{ __('public.email_label') }}
              </div>
              <input class="lp-input" style="padding-left:14px;" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('public.votre_email') }}" />
              @error('email')<div class="lp-err">{{ $message }}</div>@enderror
            </div>

            <div>
              <div class="lp-lbl" style="--lp-lbl-c:#E8A020;">
                <span class="material-symbols-rounded" aria-hidden="true">phone</span>
                {{ __('public.telephone_label') }}
              </div>
              <input class="lp-input" style="padding-left:14px;" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+237 6XX XXX XXX" />
              @error('phone')<div class="lp-err">{{ $message }}</div>@enderror
            </div>

            <div>
              <div class="lp-lbl" style="--lp-lbl-c:#D94040;">
                <span class="material-symbols-rounded" aria-hidden="true">flag</span>
                {{ __('public.sujet') }}
              </div>
              {{-- Select personnalisé responsive (fonctionne sur mobile) --}}
              <input type="hidden" name="subject" id="subject-input" value="{{ old('subject') }}" />
              <div id="custom-select" style="position:relative;">
                <div id="select-trigger" class="lp-select-trigger" onclick="toggleSelect()" role="button" tabindex="0"
                     onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleSelect();}">
                  <span id="select-label">{{ old('subject') ?: __('public.select_sujet') }}</span>
                  <svg id="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                       stroke-width="2" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </div>
                <div id="select-dropdown" class="lp-select-dd">
                  @foreach([
                    'Intégration établissement' => __('public.sujet_integration'),
                    'Problème de paiement' => __('public.sujet_paiement'),
                    'Partenariat' => __('public.sujet_partenariat'),
                    'Autre question' => __('public.sujet_autre'),
                  ] as $val => $opt)
                  <div class="select-opt"
                       data-value="{{ $val }}"
                       onclick="selectOption(this)">
                    <span class="opt-check" style="{{ old('subject') === $val ? 'border-color:#0D9E75;background:#0D9E75;' : '' }}">
                      @if(old('subject') === $val)
                      <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                      @endif
                    </span>
                    <span style="{{ old('subject') === $val ? 'font-weight:600;' : '' }}">{{ $opt }}</span>
                  </div>
                  @endforeach
                </div>
              </div>
              @error('subject')<div class="lp-err">{{ $message }}</div>@enderror
            </div>

            <div>
              <div class="lp-lbl" style="--lp-lbl-c:#7C3AED;">
                <span class="material-symbols-rounded" aria-hidden="true">chat_bubble</span>
                {{ __('public.message_label') }}
              </div>
              <textarea class="lp-textarea" name="message" placeholder="{{ __('public.message_placeholder') }}">{{ old('message') }}</textarea>
              @error('message')<div class="lp-err">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="lp-btn lp-btn-main" style="width:100%;justify-content:center;padding:13px 20px;">
              {{ __('public.envoyer_message') }}
              <span class="material-symbols-rounded" aria-hidden="true">send</span>
            </button>
          </div>
        </div>

      </div>
    </form>
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
        <a class="footer-link" href="{{ route('about') }}">{{ __('public.footer_a_propos') }}</a>
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

{{-- ══ JS du select sujet personnalisé (ouvrir/fermer/choisir) ══ --}}
<script>
function toggleSelect() {
  var dd = document.getElementById('select-dropdown');
  var arrow = document.getElementById('select-arrow');
  var trigger = document.getElementById('select-trigger');
  var open = dd.style.display === 'block';
  dd.style.display = open ? 'none' : 'block';
  arrow.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
  trigger.style.borderColor = open ? '' : 'var(--lp-teal)';
  trigger.style.boxShadow = open ? '' : '0 0 0 3px rgba(13,158,117,.14)';
}
function selectOption(el) {
  var val = el.getAttribute('data-value');
  document.getElementById('subject-input').value = val;
  document.getElementById('select-label').textContent = val;
  document.getElementById('select-dropdown').style.display = 'none';
  document.getElementById('select-arrow').style.transform = 'rotate(0deg)';
  document.getElementById('select-trigger').style.borderColor = 'var(--lp-teal)';
  document.getElementById('select-trigger').style.boxShadow = '';
  // Réinitialiser toutes les options
  document.querySelectorAll('.select-opt').forEach(function(opt) {
    opt.querySelector('.opt-check').style.borderColor = '';
    opt.querySelector('.opt-check').style.background = '';
    opt.querySelector('.opt-check').innerHTML = '';
    opt.lastElementChild.style.fontWeight = '';
  });
  // Surligner l'option choisie
  var chk = el.querySelector('.opt-check');
  chk.style.borderColor = '#0D9E75';
  chk.style.background = '#0D9E75';
  chk.innerHTML = '<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
  el.lastElementChild.style.fontWeight = '600';
}
// Fermer si clic ailleurs
document.addEventListener('click', function(e) {
  var cs = document.getElementById('custom-select');
  if (cs && !cs.contains(e.target)) {
    document.getElementById('select-dropdown').style.display = 'none';
    document.getElementById('select-arrow').style.transform = 'rotate(0deg)';
  }
});
</script>

@endsection
