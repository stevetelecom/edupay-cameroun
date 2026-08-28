@extends('layouts.public')

@section('title', __('auth.verify_title') . ' — EduPay Cameroun')

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
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 9h6M9 15h6"/></svg>
          </div>
          <div class="form-title">{{ __('auth.verify_titre') }}</div>
          <div class="form-sub">{{ __('auth.verify_sub') }}<br><strong style="color:#0B2545;">{{ $email }}</strong></div>
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

        <form method="POST" action="{{ route('password.verify.code') }}">
          @csrf
          <input type="hidden" name="email" value="{{ $email }}">

          <div class="lbl" style="text-align:center;margin-bottom:10px;">{{ __('auth.code_verification') }}</div>
          <input type="hidden" name="code" id="code-hidden" />
          <div style="display:flex;gap:8px;justify-content:center;margin-bottom:8px;">
            @for($i = 1; $i <= 6; $i++)
            <input type="text"
                   id="otp-{{ $i }}"
                   maxlength="1"
                   inputmode="numeric"
                   pattern="[0-9]"
                   autocomplete="off"
                   style="width:42px;height:50px;text-align:center;font-size:20px;
                          font-weight:700;border:2px solid #ddd;border-radius:10px;
                          font-family:monospace;outline:none;transition:border .15s;
                          -webkit-appearance:none;"
                   onfocus="this.style.borderColor='#0D9E75'"
                   onblur="this.style.borderColor='#ddd'"
            />
            @endfor
          </div>
          <p style="font-size:11px;color:#888;margin:8px 0 16px;text-align:center;">{{ __('auth.entrez_code_6') }}</p>

          <button type="submit" class="btn-p" style="margin-bottom:10px;">{{ __('auth.verifier_code') }}</button>

          <form method="POST" action="{{ route('password.resend.code') }}" style="display:inline-width:100%;">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <button type="submit" class="btn-o" style="margin:0;width:100%;text-decoration:none;">{{ __('auth.renvoyer_code') }}</button>
          </form>
        </form>

        <p style="text-align:center;font-size:11px;color:#888;margin-top:16px;">
          <a href="{{ route('password.forgot') }}" style="color:var(--ep-teal);text-decoration:none;">{{ __('auth.utiliser_autre_email') }}</a>
        </p>

        <script>
        (function() {
            var inputs = [1,2,3,4,5,6].map(function(i){ return document.getElementById('otp-'+i); });
            inputs[0].focus();

            inputs.forEach(function(inp, idx) {
                inp.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    if (this.value && idx < 5) inputs[idx+1].focus();
                    updateHidden();
                });
                inp.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !this.value && idx > 0) {
                        inputs[idx-1].focus();
                        inputs[idx-1].value = '';
                        updateHidden();
                    }
                });
                inp.addEventListener('paste', function(e) {
                    e.preventDefault();
                    var text = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g,'');
                    text.split('').slice(0,6).forEach(function(ch, i) {
                        if (inputs[i]) inputs[i].value = ch;
                    });
                    inputs[Math.min(text.length, 5)].focus();
                    updateHidden();
                });
            });

            function updateHidden() {
                document.getElementById('code-hidden').value = inputs.map(function(i){ return i.value; }).join('');
            }
        })();
        </script>
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
