@extends('layouts.public')

@section('title', 'Connexion — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">
  <div class="form-header">
    <div style="display:flex;align-items:center;gap:9px;"><span style="width:52px;height:52px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.2);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span></div>
    <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:6px 13px;border-radius:20px;font-size:12px;text-decoration:none;">← Accueil</a>
  </div>
  <div class="form-body">
    <div style="width:100%;max-width:420px;">
      <div class="form-card">
        <div style="text-align:center;margin-bottom:22px;">
          <div style="width:48px;height:48px;background:var(--ep-teal-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
          </div>
          <div class="form-title">Connexion à EduPay</div>
          <div class="form-sub">Accédez à votre espace sécurisé</div>
          @if(request('role') === 'etablissement')
            <div style="font-size:12px;color:var(--ep-teal);margin-top:8px;">Connectez-vous avec vos identifiants d'établissement.</div>
          @endif
        </div>

        <form method="POST" action="{{ route('login.post') }}">
          @csrf
          <div class="lbl">Téléphone ou adresse email</div>
          <input class="inp" type="text" id="login-input" name="login" value="{{ old('login') }}" placeholder="699 123 456 ou nom@email.com" required />
          
          <div class="lbl">Mot de passe</div>
          <div style="position:relative;">
            <input class="inp" type="password" id="password-input" name="password" placeholder="••••••••" required style="padding-right:40px;" />
            <button type="button" id="toggle-password" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#888;display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          
          <div style="display:flex;justify-content:space-between;margin-bottom:18px;">
            <label style="font-size:12px;color:#888;display:flex;align-items:center;gap:7px;cursor:pointer;">
              <input type="checkbox" name="remember" /> Rester connecté
            </label>
            <a href="{{ route('password.forgot') }}" style="font-size:12px;color:var(--ep-teal);cursor:pointer;text-decoration:none;">Mot de passe oublié ?</a>
          </div>
          <button type="submit" class="btn-p" style="margin-bottom:10px;">Se connecter</button>
          <input type="hidden" name="login_type" value="{{ request('role') }}" />
        </form>
        @unless(request('role') === 'etablissement')
          <a href="{{ route('login.otp') }}" class="btn-o">Connexion par OTP SMS</a>
          <div class="divider"></div>
          <div style="font-size:12px;color:#888;text-align:center;">Pas encore de compte ? <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);font-weight:600;">Créer un compte parent →</a></div>
        @endunless
      </div>

      <div class="epcard" style="background:#f8f9fa;margin-top:10px;">
        <div style="font-size:12px;color:#888;text-align:center;margin-bottom:10px;">Vous représentez un établissement ?</div>
        <a href="{{ route('login', ['role' => 'etablissement']) }}" class="btn-o">Accès back-office établissement →</a>
        <div style="font-size:12px;color:#888;text-align:center;margin-top:10px;">Pas encore inscrit ? <a href="{{ route('register.ecole.step1') }}" style="color:var(--ep-teal);font-weight:600;">Inscrire mon établissement →</a></div>
      </div>
    </div>
  </div>
  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Connexion chiffrée TLS 1.3</div>
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
