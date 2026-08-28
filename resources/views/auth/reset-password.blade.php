@extends('layouts.public')

@section('title', __('auth.reset_title'))

@section('content')
<div class="video-bg-container" style="min-height:100vh;display:flex;flex-direction:column;"><video class="video-bg" autoplay muted loop playsinline><source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4"></video><div class="video-bg-overlay"></div>
  <div class="form-header">
    <div style="display:flex;align-items:center;gap:9px;"><span style="width:52px;height:52px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.2);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span></div>
    <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:6px 13px;border-radius:20px;font-size:12px;text-decoration:none;">← {{ __('auth.retour_accueil') }}</a>
  </div>
  <div class="form-body">
    <div style="width:100%;max-width:420px;">
      <div class="form-card">
        <div style="text-align:center;margin-bottom:22px;">
          <div style="width:48px;height:48px;background:var(--ep-teal-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/><path d="M12 6v6l4 2"/></svg>
          </div>
          <div class="form-title">{{ __('auth.reset_titre') }}</div>
          <div class="form-sub">{{ __('auth.reset_sub') }}</div>
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
          
          <div class="lbl">{{ __('auth.nouveau_mot_de_passe') }}</div>
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
          <p style="font-size:11px;color:#888;margin:4px 0 12px;">{{ __('auth.8_caracteres_min') }}</p>
          
          <div class="lbl">{{ __('auth.confirmer_mot_de_passe') }}</div>
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
          
          <button type="submit" class="btn-p" style="margin-bottom:10px;margin-top:16px;">{{ __('auth.reinitialiser_mot_de_passe') }}</button>
          <a href="{{ route('login') }}" class="btn-o" style="text-align:center;display:block;text-decoration:none;">{{ __('auth.retour_connexion') }}</a>
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
