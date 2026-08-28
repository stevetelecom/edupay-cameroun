@extends('layouts.public')

@section('title', __('public.confid_title'))

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">{{ __('public.confid_hero_tag') }}</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">{!! __('public.confid_hero_h1') !!}</div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">{{ __('public.confid_derniere_maj') }}</div>
  </div>
</div>

<div class="ep-body2">

  <div class="epcard" style="margin-bottom:16px;background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);">
    <div style="font-size:12px;color:#0F6E56;line-height:1.7;">{{ __('public.confid_intro') }}</div>
  </div>

  <div class="seclbl" style="margin-top:4px;">{{ __('public.confid_s1_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {!! __('public.confid_s1_texte') !!}
    </div>
  </div>

  <div class="seclbl">{{ __('public.confid_s2_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {{ __('public.confid_s2_texte') }}
    </div>
  </div>

  <div class="seclbl">{{ __('public.confid_s3_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {!! __('public.confid_s3_texte') !!}
    </div>
  </div>

  <div class="seclbl">{{ __('public.confid_s4_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {{ __('public.confid_s4_texte') }}
    </div>
  </div>

  <div class="seclbl">{{ __('public.confid_s5_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {{ __('public.confid_s5_texte') }}
    </div>
  </div>

  <div class="seclbl">{{ __('public.confid_s6_titre') }}</div>
  <div class="epcard" style="margin-bottom:14px;">
    <div style="font-size:13px;color:#555;line-height:1.8;">
      {!! __('public.confid_s6_texte', ['link' => '<a href="'.route('contact').'" style="color:var(--ep-teal);font-weight:600;">'.__('public.confid_s6_link_text').'</a>']) !!}
    </div>
  </div>

  <div class="epcard" style="background:#FEF9EC;border-color:rgba(232,160,32,.25);">
    <div style="font-size:12px;color:#854F0B;line-height:1.7;">{{ __('public.confid_avis') }}</div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">{{ __('public.footer_school_brief') }}</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_produit') }}</div><a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a><a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a><a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_legal') }}</div><a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">{{ __('public.footer_legal') }}</div><div class="footer-socials">
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
