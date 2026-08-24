@extends('layouts.public')

@section('title', 'Connexion OTP SMS — EduPay Cameroun')

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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div class="form-title">Connexion par OTP SMS</div>
          <div class="form-sub">Recevez un code sécurisé par SMS</div>
        </div>

        <form method="POST" action="{{ route('login.otp.verify') }}">
          @csrf
          <div class="lbl">Téléphone ou adresse email</div>
          <input class="inp" type="text" id="otp-login-input" name="login" value="{{ old('login', session('otp_login')) }}" placeholder="699 123 456 ou nom@email.com" required />
          
          @if(!session('otp_sent'))
            <button type="submit" class="btn-p" style="margin-top:18px;margin-bottom:10px;">Envoyer le code OTP</button>
          @else
            <div class="lbl" style="margin-top:18px;">Code OTP reçu par SMS</div>
            <input class="inp" type="text" name="otp_code" id="otp-code-input" placeholder="000000" maxlength="6" pattern="\d{6}" inputmode="numeric" required />
            <button type="submit" class="btn-p" style="margin-top:18px;margin-bottom:10px;">Vérifier et se connecter</button>
          @endif
        </form>

        <a href="{{ route('login') }}" class="btn-o" style="display:block;text-align:center;">← Retour à la connexion classique</a>
        
        <div class="divider"></div>
        <div style="font-size:12px;color:#888;text-align:center;">Pas encore de compte ? <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);font-weight:600;">Créer un compte parent →</a></div>
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
// ── Format Telephone avec +237 (au blur uniquement) ──
const otpLoginInput = document.getElementById('otp-login-input');

otpLoginInput.addEventListener('blur', (e) => {
  let value = e.target.value.trim();
  if (!value.includes('@') && value.length > 0) {
    let cleaned = value.replace(/[^\d+]/g, '');
    if (cleaned.startsWith('+237')) {
      // déjà formaté
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
  const val = otpLoginInput.value;
  if (val && !val.includes('@') && !val.startsWith('+')) {
    let cleaned = val.replace(/[^\d]/g, '');
    if (/^[67]/.test(cleaned)) otpLoginInput.value = '+237' + cleaned;
    else if (cleaned.startsWith('237')) otpLoginInput.value = '+' + cleaned;
  }
});

// Auto-focus sur code OTP
const otpCodeInput = document.getElementById('otp-code-input');
if (otpCodeInput) {
  window.addEventListener('load', () => {
    otpCodeInput.focus();
  });
}
</script>

@endsection
