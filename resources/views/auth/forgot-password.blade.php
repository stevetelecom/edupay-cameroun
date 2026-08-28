@extends('layouts.public')

@section('title', __('auth.forgot_title'))

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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <div class="form-title">{{ __('auth.forgot_titre') }}</div>
          <div class="form-sub">{{ __('auth.forgot_sub') }}</div>
        </div>

        @if($errors->any())
        <div style="background:#FBEAEA;border-left:3px solid #D94040;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
          {{ $errors->first() }}
        </div>
        @endif

        @if(session('success'))
        <div style="background:#E8F9F5;border-left:3px solid #0D9E75;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#0B5C3B;">
          {{ session('success') }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.send.code') }}">
          @csrf
          <div class="lbl">{{ __('auth.adresse_email') }}</div>
          <input 
            class="inp" 
            type="email" 
            name="email" 
            value="{{ old('email') }}" 
            placeholder="{{ __('auth.email_placeholder') }}"
            required 
            autofocus
          />
          
          <button type="submit" class="btn-p" style="margin-bottom:10px;">{{ __('auth.envoyer_code') }}</button>
          <a href="{{ route('login') }}" class="btn-o" style="text-align:center;display:block;text-decoration:none;">{{ __('auth.retour_connexion') }}</a>
        </form>
      </div>

      <div style="text-align:center;margin-top:20px;font-size:12px;color:#888;">
        <p>
          {{ __('auth.pas_encore_compte_question') }} 
          <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);text-decoration:none;font-weight:500;">{{ __('auth.s_inscrire') }}</a>
        </p>
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
@endsection
