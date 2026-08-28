@extends('layouts.public')

@section('title', __('public.guide_title'))

@section('content')

@include('layouts._navbar_public')

<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">{{ __('public.guide_hero_tag') }}</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">{!! __('public.guide_hero_h1') !!}</div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:520px;margin:0 auto;line-height:1.7;">{{ __('public.guide_hero_sub') }}</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">{{ __('public.guide_6_etapes') }}</div>
  <div style="display:grid;gap:12px;margin-bottom:24px;">

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-teal);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape1_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape1_desc') }}</div>
        <a href="{{ route('register.ecole.step1') }}" style="font-size:12px;color:var(--ep-teal);font-weight:600;text-decoration:none;display:inline-block;margin-top:6px;">{{ __('public.inscrire_etablissement_lien') }} →</a>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-gold);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape2_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape2_desc') }}</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#185FA5;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape3_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape3_desc') }}</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#7C3AED;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">4</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape4_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape4_desc') }}</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:var(--ep-teal);color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">5</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape5_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape5_desc') }}</div>
      </div>
    </div>

    <div class="epcard" style="display:flex;gap:16px;align-items:flex-start;">
      <div style="width:32px;height:32px;border-radius:50%;background:#E8A020;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">6</div>
      <div>
        <div style="font-size:14px;font-weight:700;margin-bottom:3px;">{{ __('public.etape6_titre') }}</div>
        <div style="font-size:13px;color:#666;line-height:1.6;">{{ __('public.etape6_desc') }}</div>
      </div>
    </div>

  </div>

  <div class="seclbl">{{ __('public.fonctionnalites_backoffice') }}</div>
  <div class="g4" style="margin-bottom:24px;">
    <div class="value-card" style="border-left-color:var(--ep-teal);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_apprenants_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_apprenants_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-gold);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_frais_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_frais_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:#185FA5;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_paiements_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_paiements_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:#7C3AED;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_impayes_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_impayes_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-teal);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_rapports_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_rapports_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:var(--ep-gold);">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_remboursements_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_remboursements_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:#185FA5;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_multisites_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_multisites_desc') }}</div>
    </div>
    <div class="value-card" style="border-left-color:#7C3AED;">
      <div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.feat_users_titre') }}</div>
      <div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.feat_users_desc') }}</div>
    </div>
  </div>

  <div style="background:#0B2545;border-radius:var(--radius-lg);padding:28px;text-align:center;">
    <div style="font-size:17px;font-weight:700;color:#fff;margin-bottom:6px;">{{ __('public.besoin_aide') }}</div>
    <div style="font-size:13px;color:rgba(255,255,255,.6);margin-bottom:18px;">{{ __('public.support_repond_desc') }}</div>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <a href="{{ route('support') }}" style="background:#0D9E75;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:11px 22px;border-radius:10px;">{{ __('public.voir_faq') }} →</a>
      <a href="{{ route('contact') }}" style="background:transparent;color:#fff;text-decoration:none;font-size:13px;font-weight:600;padding:11px 22px;border-radius:10px;border:1px solid rgba(255,255,255,.25);">{{ __('public.contacter_support') }}</a>
    </div>
  </div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">{{ __('public.footer_school_brief') }}</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_produit') }}</div><a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a><a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a><a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support') }}</a></div>
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
