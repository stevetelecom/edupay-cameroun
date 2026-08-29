<!DOCTYPE html><html lang="fr" class="h-full">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>{{ $pageTitle }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="h-full bg-gray-100 flex flex-col items-center justify-center font-sans antialiased">
<div class="mb-6 text-center">
  <div class="text-2xl font-bold text-[#0B2545] tracking-tight">Edu<span class="text-[#0D9E75]">Pay</span> Cameroun</div>
  <div class="text-xs text-gray-400 mt-1">{{ __('auth.reset_title') }}</div>
</div>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-sm">
  <div class="flex justify-center mb-5">
    <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
      <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
      </svg>
    </div>
  </div>
  <h1 class="text-lg font-bold text-center text-gray-900 mb-1">{{ __('auth.reset_title') }}</h1>
  <p class="text-sm text-gray-500 text-center mb-6">{{ __('admin.entrez_code_choisissez') }}</p>
  @if($errors->any())
  <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
  </div>
  @endif
  <form method="POST" action="{{ route('admin.password.reset') }}">
    @csrf
    {{-- 6 cases OTP --}}
    <div class="mb-5">
      <label class="block text-xs font-medium text-gray-600 mb-2 text-center">{{ __('admin.code_recu_email') }}</label>
      <input type="hidden" name="code" id="reset-code-hidden" />
      <div style="display:flex;gap:8px;justify-content:center;margin-bottom:6px;">
        @for($i = 1; $i <= 6; $i++)
        <input type="text" id="reset-otp-{{ $i }}" maxlength="1" inputmode="numeric"
               style="width:44px;height:52px;text-align:center;font-size:22px;font-weight:700;
                      border:2px solid #ddd;border-radius:10px;font-family:monospace;outline:none;
                      transition:border .15s;-webkit-appearance:none;"
               onfocus="this.style.borderColor='#0D9E75'"
               onblur="this.style.borderColor='#ddd'" />
        @endfor
      </div>
      @error('code')<p class="text-xs text-red-500 mt-1 text-center">{{ $message }}</p>@enderror
    </div>
    {{-- Nouveau mot de passe --}}
    <div class="mb-4">
      <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.nouveau_mot_de_passe') }}</label>
      <div style="position:relative;">
        <input type="password" id="password" name="password" required minlength="10"
               style="padding-right:44px;"
               class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        <button type="button" onclick="toggleP('password','eye1','eyeoff1')" tabindex="-1"
                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
          <svg id="eye1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg id="eyeoff1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
      </div>
      @error('password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="mb-5">
      <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.confirmer_mot_de_passe') }}</label>
      <div style="position:relative;">
        <input type="password" id="password_confirmation" name="password_confirmation" required
               style="padding-right:44px;"
               class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        <button type="button" onclick="toggleP('password_confirmation','eye2','eyeoff2')" tabindex="-1"
                style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
          <svg id="eye2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <svg id="eyeoff2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
      </div>
      @error('password_confirmation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
      {{ __('auth.reinitialiser_mot_de_passe') }}
    </button>
  </form>
  <div class="mt-4 text-center">
    <a href="{{ route('admin.login') }}" class="text-xs text-gray-400 hover:text-gray-600">{{ __('auth.retour_connexion') }}</a>
  </div>
</div>
<div class="mt-6 text-xs text-gray-400 text-center">{{ __('admin.copyright_tls_court') }}</div>
<script>
function toggleP(id,e1,e2){
  var i=document.getElementById(id);
  var show=i.type==='password';
  i.type=show?'text':'password';
  document.getElementById(e1).style.display=show?'none':'block';
  document.getElementById(e2).style.display=show?'block':'none';
}
(function(){
  var inputs=[1,2,3,4,5,6].map(function(i){return document.getElementById('reset-otp-'+i);});
  inputs[0].focus();
  inputs.forEach(function(inp,idx){
    inp.addEventListener('input',function(){
      this.value=this.value.replace(/[^0-9]/g,'');
      if(this.value&&idx<5)inputs[idx+1].focus();
      update();
    });
    inp.addEventListener('keydown',function(e){
      if(e.key==='Backspace'&&!this.value&&idx>0){inputs[idx-1].focus();inputs[idx-1].value='';update();}
    });
    inp.addEventListener('paste',function(e){
      e.preventDefault();
      var t=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'');
      t.split('').slice(0,6).forEach(function(ch,i){if(inputs[i])inputs[i].value=ch;});
      inputs[Math.min(t.length,5)].focus();update();
    });
  });
  function update(){document.getElementById('reset-code-hidden').value=inputs.map(function(i){return i.value;}).join('');}
})();
</script>
</body></html>
