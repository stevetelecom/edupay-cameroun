@extends('layouts.etablissement')
@section('title', 'Mon profil')

@section('content')

<div style="max-width:640px;">

  {{-- ── Informations personnelles ── --}}
  <div class="epcard" style="margin-bottom:16px;">
    <div class="seclbl" style="margin-top:0;">Informations personnelles</div>

    <form method="POST" action="{{ route('etablissement.profil.infos') }}">
      @csrf @method('PUT')
      <div class="g2" style="margin-bottom:12px;">
        <div>
          <div class="lbl">Prénom *</div>
          <input class="inp" name="prenom"
                 value="{{ old('prenom', $user->prenom ?? explode(' ', $user->name)[0] ?? '') }}"
                 required />
          @error('prenom')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>
        <div>
          <div class="lbl">Nom *</div>
          <input class="inp" name="nom"
                 value="{{ old('nom', $user->nom ?? explode(' ', $user->name)[1] ?? '') }}"
                 required />
          @error('nom')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>
      </div>
      <div class="g2" style="margin-bottom:12px;">
        <div>
          <div class="lbl">Téléphone *</div>
          <input class="inp" name="telephone"
                 value="{{ old('telephone', $user->telephone) }}"
                 required />
          @error('telephone')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>
        <div>
          <div class="lbl">Email</div>
          <input class="inp" name="email" type="email"
                 value="{{ old('email', $user->email) }}" />
          @error('email')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>
      </div>
      <div style="margin-bottom:16px;">
        <div class="lbl">Ville</div>
        <input class="inp" name="ville" value="{{ old('ville', $user->ville) }}" />
      </div>
      <button type="submit" class="btn-p" style="width:auto;padding:9px 20px;">
        Enregistrer les modifications
      </button>
    </form>
  </div>

  {{-- ── Changer le mot de passe ── --}}
  <div class="epcard">
    <div class="seclbl" style="margin-top:0;">Changer le mot de passe</div>

    <form method="POST" action="{{ route('etablissement.profil.password') }}">
      @csrf @method('PUT')

      <div style="margin-bottom:12px;">
        <div class="lbl">Mot de passe actuel *</div>
        <div style="position:relative;">
          <input class="inp" type="password" id="current_password" name="current_password"
                 required style="padding-right:42px;" />
          <button type="button" onclick="toggleP('current_password','eye-cur','eyeoff-cur')" tabindex="-1"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
            <svg id="eye-cur" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeoff-cur" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div style="margin-bottom:12px;">
        <div class="lbl">Nouveau mot de passe *</div>
        <div style="position:relative;">
          <input class="inp" type="password" id="new_password" name="password"
                 placeholder="Min. 8 caractères" required style="padding-right:42px;" />
          <button type="button" onclick="toggleP('new_password','eye-new','eyeoff-new')" tabindex="-1"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
            <svg id="eye-new" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeoff-new" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div style="margin-bottom:16px;">
        <div class="lbl">Confirmer le nouveau mot de passe *</div>
        <div style="position:relative;">
          <input class="inp" type="password" id="confirm_password" name="password_confirmation"
                 required style="padding-right:42px;" />
          <button type="button" onclick="toggleP('confirm_password','eye-conf','eyeoff-conf')" tabindex="-1"
                  style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
            <svg id="eye-conf" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg id="eyeoff-conf" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-p" style="width:auto;padding:9px 20px;">
        Changer le mot de passe
      </button>
    </form>
  </div>

</div>

<script>
function toggleP(id, eyeId, eyeOffId) {
  var inp = document.getElementById(id);
  var eye = document.getElementById(eyeId);
  var eyeOff = document.getElementById(eyeOffId);
  if (inp.type === 'password') {
    inp.type = 'text';
    eye.style.display = 'none';
    eyeOff.style.display = 'block';
  } else {
    inp.type = 'password';
    eye.style.display = 'block';
    eyeOff.style.display = 'none';
  }
}

function showToast(message, type) {
  var bg = type === 'error' ? '#FBEAEA' : '#E1F5EE';
  var border = type === 'error' ? '#D94040' : '#5DCAA5';
  var color = type === 'error' ? '#9B2C2C' : '#085041';
  var icon = type === 'error'
    ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
    : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>';

  var toast = document.createElement('div');
  toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:' + bg + ';' +
    'border:1px solid ' + border + ';border-radius:8px;padding:14px 18px;' +
    'font-size:13px;color:' + color + ';display:flex;align-items:center;gap:8px;' +
    'box-shadow:0 8px 24px rgba(0,0,0,.12);max-width:380px;' +
    'animation:slideIn .3s ease-out;';
  toast.innerHTML = icon + '<span>' + message + '</span>';
  document.body.appendChild(toast);

  setTimeout(function() {
    toast.style.transition = 'opacity .3s, transform .3s';
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(20px)';
    setTimeout(function() { toast.remove(); }, 300);
  }, 4000);
}

var styleEl = document.createElement('style');
styleEl.textContent = '@keyframes slideIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }';
document.head.appendChild(styleEl);

@if(session('success'))
  showToast(@json(session('success')), 'success');
@endif

@if(session('success_password'))
  showToast(@json(session('success_password')), 'success');
@endif

@if($errors->has('current_password'))
  showToast(@json($errors->first('current_password')), 'error');
@endif

@if($errors->has('password'))
  showToast(@json($errors->first('password')), 'error');
@endif
</script>

@endsection
