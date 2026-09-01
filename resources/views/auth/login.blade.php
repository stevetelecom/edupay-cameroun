@extends('layouts.public')

@section('title', __('auth.login_title'))

@section('content')
<div class="video-bg-container" style="min-height:100vh;display:flex;flex-direction:column;"><video class="video-bg" autoplay muted loop playsinline><source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4"></video><div class="video-bg-overlay"></div>
  <div class="form-header">
    <div style="display:flex;align-items:center;gap:9px;"><span style="width:52px;height:52px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.2);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span><span style="font-size:16px;font-weight:800;color:#fff;letter-spacing:-.01em;">Edu<span style="color:#5DCAA5;">Pay</span></span></div>
    <div style="display:flex;align-items:center;gap:10px;">
      <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:6px 13px;border-radius:20px;font-size:12px;text-decoration:none;">{{ __('auth.retour_accueil') }}</a>
      <form method="POST" action="{{ route('locale.switch') }}" style="display:inline-flex;align-items:center;">
        @csrf
        <select name="locale" onchange="this.form.submit()" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:6px 10px;font-size:12px;font-weight:500;cursor:pointer;outline:none;">
          <option value="fr" {{ app()->getLocale()==='fr' ? 'selected' : '' }}>🇫🇷 FR</option>
          <option value="en" {{ app()->getLocale()==='en' ? 'selected' : '' }}>🇬🇧 EN</option>
        </select>
      </form>
    </div>
  </div>
  <div class="form-body">
    <div style="width:100%;max-width:420px;">
      <div class="form-card">
        <div style="text-align:center;margin-bottom:22px;">
          <div style="width:48px;height:48px;background:var(--ep-teal-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          <div class="form-title">{{ __('auth.login_titre') }}</div>
          <div class="form-sub">{{ __('auth.login_sub') }}</div>
          @if(request('role') === 'etablissement')
            <div style="font-size:12px;color:var(--ep-teal);margin-top:8px;">{{ __('auth.login_etab_note') }}</div>
          @endif
        </div>

        <form method="POST" action="{{ route('login.post') }}">
          @csrf
          <div class="lbl">{{ __('auth.telephone_ou_email') }}</div>
          <input class="inp" type="text" id="login-input" name="login" value="{{ old('login') }}" placeholder="{{ __('auth.login_placeholder') }}" required />
          
          <div class="lbl">{{ __('auth.mot_de_passe') }}</div>
          <div style="position:relative;">
            <input class="inp" type="password" id="password-input" name="password" placeholder="{{ __('auth.password_placeholder') }}" required style="padding-right:40px;" />
            <button type="button" id="toggle-password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#888;display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          
          <div style="display:flex;justify-content:space-between;margin-bottom:18px;">
            <label style="font-size:12px;color:#888;display:flex;align-items:center;gap:7px;cursor:pointer;">
              <input type="checkbox" name="remember" /> {{ __('auth.rester_connecte') }}
            </label>
            <a href="{{ route('password.forgot') }}" style="font-size:12px;color:var(--ep-teal);cursor:pointer;text-decoration:none;">{{ __('auth.mot_de_passe_oublie') }}</a>
          </div>
          <button type="submit" class="btn-p" style="margin-bottom:10px;">{{ __('auth.se_connecter') }}</button>
          <input type="hidden" name="login_type" value="{{ request('role') }}" />
        </form>
        @unless(request('role') === 'etablissement')
          <a href="{{ route('login.otp') }}" class="btn-o">{{ __('auth.login_otp') }}</a>
          <div class="divider"></div>
          <a href="{{ route('login.google') }}" class="btn-google" style="display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:12px;border:1px solid #ddd;border-radius:10px;background:#fff;color:#333;font-size:13px;font-weight:600;margin-bottom:10px;text-decoration:none;box-sizing:border-box;">
            <svg width="18" height="18" viewBox="0 0 24 24" style="flex-shrink:0;"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            {{ __('auth.continue_avec_google') }}
          </a>
          <div style="font-size:12px;color:#888;text-align:center;">{{ __('auth.pas_encore_compte') }} <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);font-weight:600;">{{ __('auth.creer_compte_parent') }}</a></div>
        @endunless
      </div>

      <div class="epcard" style="background:#f8f9fa;margin-top:10px;">
        <div style="font-size:12px;color:#888;text-align:center;margin-bottom:10px;">{{ __('auth.representez_etablissement') }}</div>
        <a href="{{ route('login', ['role' => 'etablissement']) }}" class="btn-o">{{ __('auth.acces_backoffice') }}</a>
        <div style="font-size:12px;color:#888;text-align:center;margin-top:10px;">{{ __('auth.pas_encore_inscrit') }} <a href="{{ route('register.ecole.step1') }}" style="color:var(--ep-teal);font-weight:600;">{{ __('auth.inscrire_etablissement') }}</a></div>
      </div>
    </div>
  </div>
  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">{{ __('auth.footer_connexion_chiffree') }}</div>
    <div style="display:flex;gap:8px;"><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span></div>
  </div>
</div>

<script>
// ── Toggle Mot de passe (oeil) ──
const togglePasswordBtn = document.getElementById('toggle-password');
const passwordInput = document.getElementById('password-input');
const eyeIcon = document.getElementById('eye-icon');

togglePasswordBtn.addEventListener('click', (e) => {
  e.preventDefault();
  const isPassword = passwordInput.type === 'password';
  passwordInput.type = isPassword ? 'text' : 'password';
  
  // Changer l'icône d'oeil (ouvert/fermé)
  if (isPassword) {
    eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
});

// ── Format Telephone avec +237 (au blur uniquement) ──
const loginInput = document.getElementById('login-input');

loginInput.addEventListener('blur', (e) => {
  let value = e.target.value.trim();
  if (!value.includes('@') && value.length > 0) {
    let cleaned = value.replace(/[^\d+]/g, '');
    if (cleaned.startsWith('+237')) {
      // deja formate
    } else if (cleaned.startsWith('237')) {
      cleaned = '+' + cleaned;
    } else if (/^[67]/.test(cleaned)) {
      cleaned = '+237' + cleaned;
    }
    e.target.value = cleaned;
  }
});

// Au chargement formater si old('login') est un tel
window.addEventListener('load', () => {
  const val = loginInput.value;
  if (val && !val.includes('@') && !val.startsWith('+')) {
    let cleaned = val.replace(/[^\d]/g, '');
    if (/^[67]/.test(cleaned)) loginInput.value = '+237' + cleaned;
    else if (cleaned.startsWith('237')) loginInput.value = '+' + cleaned;
  }
});
</script>

@endsection
