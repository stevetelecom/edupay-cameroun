@extends('layouts.public')

@section('title', __('public.support_title'))

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">{{ __('public.support_hero_tag') }}</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">{!! __('public.support_hero_h1') !!}</div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">{{ __('public.support_hero_sub') }}</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">{{ __('public.questions_frequentes') }}</div>
  <div style="display:grid;gap:10px;margin-bottom:24px;">

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_1_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_1_a') }}</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_2_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_2_a') }}</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_3_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_3_a') }}</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_4_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_4_a') }}</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_5_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_5_a') }}</div>
    </div>

    <div class="epcard">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;color:#0B2545;">{{ __('public.faq_6_q') }}</div>
      <div style="font-size:13px;color:#666;line-height:1.7;">{{ __('public.faq_6_a') }}</div>
    </div>

  </div>

  <div class="seclbl">{{ __('public.contactez_directement') }}</div>
  <div class="g2" style="margin-bottom:8px;">

    <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
      <span class="icon-round" style="background:var(--ep-teal);">
        <span class="material-symbols-outlined">place</span>
      </span>
      <div>
        <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('public.adresse') }}</div>
        <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('public.adresse_val') }}</div>
      </div>
    </div>

    <div style="background:#E8F1FC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
      <span class="icon-round" style="background:#185FA5;">
        <span class="material-symbols-outlined">call</span>
      </span>
      <div>
        <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('public.telephone_label') }}</div>
        <div style="font-size:13px;color:#555;line-height:1.6;">+237 654 862 989<br>+237 688 462 229</div>
      </div>
    </div>

    <div style="background:#EFF8F0;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
      <span class="icon-round" style="background:var(--ep-teal);">
        <span class="material-symbols-outlined">email</span>
      </span>
      <div>
        <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('public.email_label') }}</div>
        <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('public.email_val') }}</div>
      </div>
    </div>

    <div style="background:#FEF3DC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
      <span class="icon-round" style="background:var(--ep-gold);">
        <span class="material-symbols-outlined">schedule</span>
      </span>
      <div>
        <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('public.disponibilite') }}</div>
        <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('public.disponibilite_val') }}</div>
      </div>
    </div>

  </div>

  <div style="text-align:center;margin-top:20px;">
    <a href="{{ route('contact') }}" style="background:#0D9E75;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:12px 26px;border-radius:10px;display:inline-block;">{{ __('public.envoyer_un_message') }} →</a>
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
