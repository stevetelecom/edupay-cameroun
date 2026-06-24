@extends('layouts.public')

@section('title', 'Réinitialiser le mot de passe — EduPay Cameroun')

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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="form-title">Nouveau mot de passe</div>
          <div class="form-sub">Créez un nouveau mot de passe sécurisé</div>
        </div>

        @if($errors->any())
        <div style="background:#FBEAEA;border-left:3px solid #D94040;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
          @foreach($errors->all() as $error)
            <p style="margin:0 0 6px;">{{ $error }}</p>
          @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('password.reset') }}">
          @csrf
          <input type="hidden" name="email" value="{{ $email }}">
          <input type="hidden" name="token" value="{{ $token }}">
          
          <div class="lbl">Nouveau mot de passe</div>
          <div style="position:relative;">
            <input 
              class="inp" 
              type="password" 
              id="password" 
              name="password" 
              placeholder="••••••••" 
              required 
              style="padding-right:40px;"
              minlength="8"
            />
            <button type="button" onclick="togglePwd('password')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#888;display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          <p style="font-size:11px;color:#888;margin:4px 0 12px;">Au moins 8 caractères</p>
          
          <div class="lbl">Confirmer le mot de passe</div>
          <div style="position:relative;">
            <input 
              class="inp" 
              type="password" 
              id="password_confirmation" 
              name="password_confirmation" 
              placeholder="••••••••" 
              required 
              style="padding-right:40px;"
              minlength="8"
            />
            <button type="button" onclick="togglePwd('password_confirmation')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;color:#888;display:flex;align-items:center;justify-content:center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
          </div>
          
          <button type="submit" class="btn-p" style="margin-bottom:10px;margin-top:16px;">Réinitialiser le mot de passe</button>
          <a href="{{ route('login') }}" class="btn-o" style="text-align:center;display:block;text-decoration:none;">← Retour à la connexion</a>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
  body { 
    --ep-navy: #0B2545; 
    --ep-teal: #0D9E75; 
    --ep-teal-lt: #E8F9F5;
    --ep-gold: #E8A020; 
    --ep-red: #D94040;
  }
</style>

<script>
function togglePwd(id) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
  } else {
    input.type = 'password';
  }
}
</script>
@endsection
