{{-- ══ MODAL GLOBAL : Voir mon profil (Établissement) ══ --}}
{{-- S'ouvre par-dessus la page en cours (tableau de bord, etc.) --}}
<div id="modal-profil-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.mon_profil') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-profil-etab')">×</button>
    </div>
    <div class="ep-modal-body">

      {{-- ── Informations personnelles ── --}}
      <div class="seclbl" style="margin-top:0;">{{ __('etablissement.infos_personnelles') }}</div>
      <form method="POST" action="{{ route('etablissement.profil.infos') }}" style="margin-bottom:22px;">
        @csrf @method('PUT')
        <div class="g2" style="margin-bottom:12px;">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
            <input class="inp" name="prenom"
                   value="{{ old('prenom', Auth::user()->prenom ?? explode(' ', Auth::user()->name)[0] ?? '') }}"
                   required />
            @error('prenom')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
            <input class="inp" name="nom"
                   value="{{ old('nom', Auth::user()->nom ?? explode(' ', Auth::user()->name)[1] ?? '') }}"
                   required />
            @error('nom')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
          </div>
        </div>
        <div class="g2" style="margin-bottom:12px;">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_telephone') }}</div>
            <input class="inp" name="telephone"
                   value="{{ old('telephone', Auth::user()->telephone) }}"
                   required />
            @error('telephone')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_email') }}</div>
            <input class="inp" name="email" type="email"
                   value="{{ old('email', Auth::user()->email) }}" />
            @error('email')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
          </div>
        </div>
        <div style="margin-bottom:16px;">
          <div class="lbl">{{ __('etablissement.lbl_ville') }}</div>
          <input class="inp" name="ville" value="{{ old('ville', Auth::user()->ville) }}" />
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:9px 20px;">
          {{ __('etablissement.enregistrer_modifs') }}
        </button>
      </form>

      {{-- ── Changer le mot de passe ── --}}
      <div class="seclbl">{{ __('etablissement.changer_mdp') }}</div>
      <form method="POST" action="{{ route('etablissement.profil.password') }}" autocomplete="off">
        <input type="text" style="display:none;" tabindex="-1" autocomplete="username" />
        <input type="password" style="display:none;" tabindex="-1" autocomplete="new-password" />
        @csrf @method('PUT')

        <div style="margin-bottom:12px;">
          <div class="lbl">{{ __('etablissement.mdp_actuel') }}</div>
          <div style="position:relative;">
            <input class="inp" type="password" id="mp-current-password" name="current_password"
                   required autocomplete="current-password" style="padding-right:42px;" />
            <button type="button" onclick="toggleMP('mp-current-password','mp-eye-cur','mp-eyeoff-cur')" tabindex="-1"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
              <svg id="mp-eye-cur" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="mp-eyeoff-cur" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          @error('current_password')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>

        <div style="margin-bottom:12px;">
          <div class="lbl">{{ __('etablissement.mdp_nouveau') }}</div>
          <div style="position:relative;">
            <input class="inp" type="password" id="mp-new-password" name="password"
                   placeholder="{{ __('etablissement.mdp_min_car_placeholder') }}" required
                   autocomplete="new-password" style="padding-right:42px;" />
            <button type="button" onclick="toggleMP('mp-new-password','mp-eye-new','mp-eyeoff-new')" tabindex="-1"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
              <svg id="mp-eye-new" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="mp-eyeoff-new" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          @error('password')<div style="color:var(--ep-red);font-size:11px;margin-top:4px;">{{ $message }}</div>@enderror
        </div>

        <div style="margin-bottom:16px;">
          <div class="lbl">{{ __('etablissement.mdp_confirm') }}</div>
          <div style="position:relative;">
            <input class="inp" type="password" id="mp-confirm-password" name="password_confirmation"
                   required autocomplete="new-password" style="padding-right:42px;" />
            <button type="button" onclick="toggleMP('mp-confirm-password','mp-eye-conf','mp-eyeoff-conf')" tabindex="-1"
                    style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;">
              <svg id="mp-eye-conf" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg id="mp-eyeoff-conf" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>

        <div style="font-size:11px;color:#888;margin-bottom:14px;line-height:1.5;display:flex;align-items:flex-start;gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2" style="flex-shrink:0;margin-top:1px;">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
            <path d="M7 11V7a5 5 0 0110 0v4"/>
          </svg>
          <span>{{ __('etablissement.mdp_auth_requise') }}</span>
        </div>

        <button type="submit" class="btn-p" style="width:auto;padding:9px 20px;">
          {{ __('etablissement.changer_mdp') }}
        </button>
      </form>

    </div>
  </div>
</div>

<script>
function toggleMP(id, eyeId, eyeOffId) {
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

@if($errors->has('current_password') || $errors->has('password'))
document.addEventListener('DOMContentLoaded', function() {
  epModal.open('modal-profil-etab');
});
@endif
</script>
