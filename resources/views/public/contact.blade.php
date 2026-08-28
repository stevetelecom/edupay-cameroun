@extends('layouts.public')

@section('title', __('public.contact_title'))

@section('content')

@include('layouts._navbar_public')
<div class="hero-band">
  <div class="hero-main">
    <div class="hero-tag" style="justify-content:center;display:inline-flex;"> <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span> {{ __('public.contact_hero_tag') }}</div>
    <div class="hero-h1">{{ __('public.contact_hero_h1_line1') }}<br><span style="color:#5DCAA5;">{{ __('public.contact_hero_h1_line2') }}</span></div>
    <div class="hero-sub" style="margin:0 auto;max-width:560px;">{{ __('public.contact_hero_sub') }}</div>
  </div>
</div>

<div class="ep-body2">
  <form method="POST" action="{{ route('contact.submit') }}">
    @csrf
    <div class="g2" style="gap:24px;align-items:flex-start;">
      <div style="display:grid;gap:18px;">
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">{{ __('public.informations_contact') }}</div>
          <div style="display:grid;gap:14px;">
            <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">place</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">{{ __('public.adresse') }}</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">{!! __('public.adresse_val') !!}</div>
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
          </div>
        </div>
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">{{ __('public.pourquoi_nous_contacter') }}</div>
          <div style="font-size:13px;color:#555;line-height:1.8;">
            {{ __('public.pourquoi_nous_desc') }}
          </div>
        </div>
      </div>

      <div class="epcard" style="padding:28px;">
        <div style="font-size:18px;font-weight:700;color:#0B2545;margin-bottom:12px;">{{ __('public.envoyez_message') }}</div>
        <div style="font-size:13px;color:#555;line-height:1.75;margin-bottom:24px;">{{ __('public.formulaire_desc') }}</div>
        <div style="display:grid;gap:18px;">
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">person</span>
              </span>
              {{ __('public.nom_complet') }}
            </div>
            <input class="inp" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('public.votre_nom') }}" />
            @error('name')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:#185FA5;">
                <span class="material-symbols-outlined">email</span>
              </span>
              {{ __('public.email_label') }}
            </div>
            <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('public.votre_email') }}" />
            @error('email')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-gold);">
                <span class="material-symbols-outlined">phone</span>
              </span>
              {{ __('public.telephone_label') }}
            </div>
            <input class="inp" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+237 6XX XXX XXX" />
            @error('phone')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-red);">
                <span class="material-symbols-outlined">flag</span>
              </span>
              {{ __('public.sujet') }}
            </div>
            {{-- Custom select responsive --}}
            <input type="hidden" name="subject" id="subject-input" value="{{ old('subject') }}" />
            <div id="custom-select" style="position:relative;user-select:none;">
              <div id="select-trigger"
                   style="display:flex;justify-content:space-between;align-items:center;
                          padding:11px 14px;border:1px solid #ddd;border-radius:8px;
                          font-size:13px;color:#555;background:#fff;cursor:pointer;
                          transition:border .15s;"
                   onclick="toggleSelect()">
                <span id="select-label">
                  {{ old('subject') ?: __('public.select_sujet') }}
                </span>
                <svg id="select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="#888" stroke-width="2" style="transition:transform .2s;flex-shrink:0;">
                  <polyline points="6 9 12 15 18 9"/>
                </svg>
              </div>
              <div id="select-dropdown"
                   style="display:none;position:absolute;top:calc(100% + 6px);left:0;right:0;
                          background:#fff;border:1px solid #ddd;border-radius:10px;
                          box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:100;overflow:hidden;">
                @foreach([
                  'Intégration établissement' => __('public.sujet_integration'),
                  'Problème de paiement' => __('public.sujet_paiement'),
                  'Partenariat' => __('public.sujet_partenariat'),
                  'Autre question' => __('public.sujet_autre'),
                ] as $val => $opt)
                <div class="select-opt"
                     data-value="{{ $val }}"
                     onclick="selectOption(this)"
                     style="padding:13px 16px;font-size:13px;color:#333;cursor:pointer;
                            border-bottom:1px solid #f5f5f5;display:flex;align-items:center;gap:10px;
                            {{ old('subject') === $val ? 'background:#E0F5EE;color:#085041;font-weight:600;' : '' }}
                            transition:background .15s;">
                  <span class="opt-check" style="width:16px;height:16px;border-radius:50%;
                        border:2px solid {{ old('subject') === $val ? '#0D9E75' : '#ddd' }};
                        background:{{ old('subject') === $val ? '#0D9E75' : '#fff' }};
                        display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    @if(old('subject') === $val)
                    <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    @endif
                  </span>
                  {{ $opt }}
                </div>
                @endforeach
              </div>
            </div>
            @error('subject')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>

          <script>
          function toggleSelect() {
            var dd = document.getElementById('select-dropdown');
            var arrow = document.getElementById('select-arrow');
            var trigger = document.getElementById('select-trigger');
            var open = dd.style.display === 'block';
            dd.style.display = open ? 'none' : 'block';
            arrow.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
            trigger.style.borderColor = open ? '#ddd' : '#0D9E75';
          }
          function selectOption(el) {
            var val = el.getAttribute('data-value');
            document.getElementById('subject-input').value = val;
            document.getElementById('select-label').textContent = val;
            document.getElementById('select-dropdown').style.display = 'none';
            document.getElementById('select-arrow').style.transform = 'rotate(0deg)';
            document.getElementById('select-trigger').style.borderColor = '#0D9E75';
            // Reset all options
            document.querySelectorAll('.select-opt').forEach(function(opt) {
              opt.style.background = '';
              opt.style.color = '#333';
              opt.style.fontWeight = '';
              opt.querySelector('.opt-check').style.borderColor = '#ddd';
              opt.querySelector('.opt-check').style.background = '#fff';
              opt.querySelector('.opt-check').innerHTML = '';
            });
            // Highlight selected
            el.style.background = '#E0F5EE';
            el.style.color = '#085041';
            el.style.fontWeight = '600';
            el.querySelector('.opt-check').style.borderColor = '#0D9E75';
            el.querySelector('.opt-check').style.background = '#0D9E75';
            el.querySelector('.opt-check').innerHTML = '<svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
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
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:#7C3AED;">
                <span class="material-symbols-outlined">chat_bubble</span>
              </span>
              {{ __('public.message_label') }}
            </div>
            <textarea class="textarea" name="message" placeholder="{{ __('public.message_placeholder') }}">{{ old('message') }}</textarea>
            @error('message')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <button class="btn-p" style="width:auto;padding:13px 24px;">{{ __('public.envoyer_message') }}</button>
        </div>
      </div>
    </div>
  </form>
</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div>
      <div class="footer-desc">{{ __('public.footer_school_brief') }}</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
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
      <div class="footer-col-title">{{ __('public.footer_col_informations') }}</div>
      <a class="footer-link" href="{{ route('contact') }}">{{ __('public.footer_contact') }}</a>
      <a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a>
      <a class="footer-link" href="{{ route('cgu') }}">{{ __('public.footer_conditions') }}</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-legal">{{ __('public.footer_legal') }}</div>
    <div class="footer-socials">
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
    </div>
  </div>
</div>

@endsection
