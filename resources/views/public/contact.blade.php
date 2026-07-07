@extends('layouts.public')

@section('title', 'Contact — EduPay Cameroun')

@section('content')

<div class="hero-band">
  <div class="hero-top">
    <div class="logo-t">Edu<span>Pay</span> Cameroun</div>
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">Accueil</a>
      <a href="{{ route('temoignages') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">Témoignages</a>
      <a href="{{ route('about') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:20px;font-size:12px;text-decoration:none;">À propos</a>
    </div>
  </div>
  <div class="hero-main">
    <div class="hero-tag" style="justify-content:center;display:inline-flex;"> <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span> Contactez notre équipe</div>
    <div class="hero-h1">Une question ?<br><span style="color:#5DCAA5;">Nous sommes là pour vous aider.</span></div>
    <div class="hero-sub" style="margin:0 auto;max-width:560px;">Support établissements, familles et partenariats. Remplissez le formulaire ci-dessous et nous revenons vers vous rapidement.</div>
  </div>
</div>

<div class="ep-body2">
  <form method="POST" action="{{ route('contact.submit') }}">
    @csrf
    <div class="g2" style="gap:24px;align-items:flex-start;">
      <div style="display:grid;gap:18px;">
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">Informations de contact</div>
          <div style="display:grid;gap:14px;">
            <div style="background:#E0F5EE;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">place</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Adresse</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">Yaoundé, Cameroun<br>Quartier Minboman</div>
              </div>
            </div>
            <div style="background:#E8F1FC;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:#185FA5;">
                <span class="material-symbols-outlined">call</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Téléphone</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">+237 654 862 989<br>+237 688 462 229</div>
              </div>
            </div>
            <div style="background:#EFF8F0;border-radius:16px;padding:18px;display:flex;gap:14px;align-items:flex-start;">
              <span class="icon-round" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">email</span>
              </span>
              <div>
                <div style="font-size:13px;font-weight:700;color:#0B2545;">Email</div>
                <div style="font-size:13px;color:#555;line-height:1.6;">contact@mekontso.gsi2026.com</div>
              </div>
            </div>
          </div>
        </div>
        <div class="epcard" style="padding:24px;">
          <div style="font-size:14px;font-weight:700;color:#0B2545;margin-bottom:10px;">Pourquoi nous contacter ?</div>
          <div style="font-size:13px;color:#555;line-height:1.8;">
            Que vous soyez un établissement, un parent ou un partenaire, notre équipe vous accompagne sur l'intégration, les paiements, les relances et les solutions sur mesure.
          </div>
        </div>
      </div>

      <div class="epcard" style="padding:28px;">
        <div style="font-size:18px;font-weight:700;color:#0B2545;margin-bottom:12px;">Envoyez-nous un message</div>
        <div style="font-size:13px;color:#555;line-height:1.75;margin-bottom:24px;">Remplissez le formulaire ci-dessous et nous reviendrons vers vous dans les plus brefs délais.</div>
        <div style="display:grid;gap:18px;">
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-teal);">
                <span class="material-symbols-outlined">person</span>
              </span>
              Nom complet
            </div>
            <input class="inp" type="text" name="name" value="{{ old('name') }}" placeholder="Votre nom" />
            @error('name')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:#185FA5;">
                <span class="material-symbols-outlined">email</span>
              </span>
              Email
            </div>
            <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="votre@email.com" />
            @error('email')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-gold);">
                <span class="material-symbols-outlined">phone</span>
              </span>
              Téléphone
            </div>
            <input class="inp" type="tel" name="phone" value="{{ old('phone') }}" placeholder="+237 6XX XXX XXX" />
            @error('phone')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <div style="display:grid;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;color:#0B2545;">
              <span class="icon-round icon-sm" style="background:var(--ep-red);">
                <span class="material-symbols-outlined">flag</span>
              </span>
              Sujet
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
                  {{ old('subject') ?: 'Sélectionnez un sujet' }}
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
                  'Intégration établissement',
                  'Problème de paiement',
                  'Partenariat',
                  'Autre question'
                ] as $opt)
                <div class="select-opt"
                     data-value="{{ $opt }}"
                     onclick="selectOption(this)"
                     style="padding:13px 16px;font-size:13px;color:#333;cursor:pointer;
                            border-bottom:1px solid #f5f5f5;display:flex;align-items:center;gap:10px;
                            {{ old('subject') === $opt ? 'background:#E0F5EE;color:#085041;font-weight:600;' : '' }}
                            transition:background .15s;">
                  <span class="opt-check" style="width:16px;height:16px;border-radius:50%;
                        border:2px solid {{ old('subject') === $opt ? '#0D9E75' : '#ddd' }};
                        background:{{ old('subject') === $opt ? '#0D9E75' : '#fff' }};
                        display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;">
                    @if(old('subject') === $opt)
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
              Message
            </div>
            <textarea class="textarea" name="message" placeholder="Écrivez votre message ici...">{{ old('message') }}</textarea>
            @error('message')<div style="font-size:12px;color:#d94040;">{{ $message }}</div>@enderror
          </div>
          <button class="btn-p" style="width:auto;padding:13px 24px;">Envoyer le message</button>
        </div>
      </div>
    </div>
  </form>
</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">Edu<span>Pay</span> Cameroun</div>
      <div class="footer-desc">La première plateforme de paiement scolaire made in Cameroon.</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">Produit</div>
      <a class="footer-link" href="{{ route('landing') }}">Accueil</a>
      <a class="footer-link" href="{{ route('about') }}">À propos</a>
      <a class="footer-link" href="{{ route('temoignages') }}">Témoignages</a>
    </div>
    <div>
      <div class="footer-col-title">Établissements</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">Inscription</a>
      <a class="footer-link" href="{{ route('guide') }}">Guide</a>
      <a class="footer-link" href="{{ route('support') }}">Support</a>
    </div>
    <div>
      <div class="footer-col-title">Informations</div>
      <a class="footer-link" href="{{ route('contact') }}">Contact</a>
      <a class="footer-link" href="{{ route('confidentialite') }}">Politique de confidentialité</a>
      <a class="footer-link" href="{{ route('cgu') }}">Conditions d'utilisation</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-legal">© 2026 EduPay Cameroun — Tous droits réservés</div>
    <div class="footer-socials">
      <div class="social-btn">in</div>
      <div class="social-btn">X</div>
      <div class="social-btn">W</div>
      <div class="social-btn">f</div>
    </div>
  </div>
</div>

@endsection
