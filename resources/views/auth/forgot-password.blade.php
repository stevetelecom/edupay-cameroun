@extends('layouts.public')

@section('title', 'Mot de passe oublié — EduPay Cameroun')

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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          </div>
          <div class="form-title">Réinitialiser votre mot de passe</div>
          <div class="form-sub">Entrez votre adresse email pour recevoir un code de vérification</div>
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
          <div class="lbl">Adresse email</div>
          <input 
            class="inp" 
            type="email" 
            name="email" 
            value="{{ old('email') }}" 
            placeholder="votre@email.com"
            required 
            autofocus
          />
          
          <button type="submit" class="btn-p" style="margin-bottom:10px;">Envoyer le code</button>
          <a href="{{ route('login') }}" class="btn-o" style="text-align:center;display:block;text-decoration:none;">← Retour à la connexion</a>
        </form>
      </div>

      <div style="text-align:center;margin-top:20px;font-size:12px;color:#888;">
        <p>
          Vous n'avez pas encore de compte ? 
          <a href="{{ route('register.parent.step1') }}" style="color:var(--ep-teal);text-decoration:none;font-weight:500;">S'inscrire</a>
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
