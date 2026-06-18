@extends('layouts.public')

@section('title', 'Connexion — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">
  <div class="form-header">
    <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> Cameroun</div>
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
        </div>

        @if($errors->any())
        <div style="background:#FBEAEA;border-left:3px solid #D94040;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
          {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
          @csrf
          <div class="lbl">Téléphone ou adresse email</div>
          <input class="inp" type="text" name="login" value="{{ old('login') }}" placeholder="699 123 456 ou nom@email.com" required />
          <div class="lbl">Mot de passe</div>
          <input class="inp" type="password" name="password" placeholder="••••••••" required />
          <div style="display:flex;justify-content:space-between;margin-bottom:18px;">
            <label style="font-size:12px;color:#888;display:flex;align-items:center;gap:7px;cursor:pointer;">
              <input type="checkbox" name="remember" /> Rester connecté
            </label>
            <span style="font-size:12px;color:var(--ep-teal);cursor:pointer;">Mot de passe oublié ?</span>
          </div>
          <button type="submit" class="btn-p" style="margin-bottom:10px;">Se connecter</button>
        </form>
        <a href="{{ route('login.otp') }}" class="btn-o">Connexion par OTP SMS</a>
        <div class="divider"></div>
        <div style="font-size:12px;color:#888;text-align:center;">Pas encore de compte ? <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);font-weight:600;">Créer un compte parent →</a></div>
      </div>

      <div class="epcard" style="background:#f8f9fa;margin-top:10px;">
        <div style="font-size:12px;color:#888;text-align:center;margin-bottom:10px;">Vous représentez un établissement ?</div>
        <a href="{{ route('etablissement.dashboard') }}" class="btn-o">Accès back-office établissement →</a>
        <div style="font-size:12px;color:#888;text-align:center;margin-top:10px;">Pas encore inscrit ? <a href="{{ route('register.ecole.step1') }}" style="color:var(--ep-teal);font-weight:600;">Inscrire mon établissement →</a></div>
      </div>
    </div>
  </div>
  <div style="background:var(--ep-navy);padding:14px 28px;display:flex;justify-content:space-between;align-items:center;">
    <div style="font-size:11px;color:rgba(255,255,255,.35);">© 2026 EduPay Cameroun · Connexion chiffrée TLS 1.3</div>
    <div style="display:flex;gap:8px;"><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span></div>
  </div>
</div>
@endsection
