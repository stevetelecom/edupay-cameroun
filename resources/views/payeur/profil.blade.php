@extends('layouts.payeur')

@section('title', __('payeur.profil_titre'))

@section('content')

<div style="font-size:17px;font-weight:700;margin-bottom:16px;">{{ __('payeur.profil_prefs_titre') }}</div>

<div class="g2">

    {{-- ── Informations personnelles ── --}}
    <div class="epcard">
        <div class="seclbl" style="margin-top:0;">{{ __('payeur.profil_infos_personnelles') }}</div>

        @if($errors->has('prenom') || $errors->has('nom') || $errors->has('telephone') || $errors->has('email'))
            <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
                @foreach($errors->all() as $error)
                    <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('payeur.profil.infos') }}">
            @csrf
            @method('PUT')

            <div class="lbl">{{ __('messages.prenom') }}</div>
            <input class="inp" name="prenom" value="{{ old('prenom', Auth::user()->prenom) }}" required />

            <div class="lbl">{{ __('messages.nom') }}</div>
            <input class="inp" name="nom" value="{{ old('nom', Auth::user()->nom) }}" required />

            <div class="lbl">{{ __('messages.telephone') }}</div>
            <input class="inp tel-cm-input" data-allow-fixe="false" name="telephone" value="{{ old('telephone', Auth::user()->telephone) }}" placeholder="6XXXXXXXX" required />

            <div class="lbl">{{ __('payeur.profil_email') }}</div>
            <input class="inp" type="email" name="email" value="{{ old('email', Auth::user()->email) }}" />

            <button type="submit" class="btn-p" style="margin-top:4px;width:auto;padding:9px 18px;font-size:12px;">
                {{ __('messages.enregistrer') }}
            </button>
        </form>
    </div>

    {{-- ── Notifications ── --}}
    <div class="epcard">
        <div class="seclbl" style="margin-top:0;">{{ __('payeur.profil_notifications') }}</div>

        <form method="POST" action="{{ route('payeur.profil.notifications') }}" id="form-notifs">
            @csrf
            @method('PUT')

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    {{ __('payeur.profil_sms_confirmation') }}
                </span>
                <input type="checkbox" name="notif_sms" value="1" {{ Auth::user()->notif_sms ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    {{ __('payeur.profil_email_recu_pdf') }}
                </span>
                <input type="checkbox" name="notif_email" value="1" {{ Auth::user()->notif_email ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    {{ __('payeur.profil_rappel_echeance') }}
                </span>
                <input type="checkbox" name="notif_rappel_echeance" value="1" {{ Auth::user()->notif_rappel_echeance ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>
        </form>

        <div style="font-size:10px;color:#aaa;margin-top:10px;">
            {{ __('payeur.profil_prefs_auto') }}
        </div>
    </div>

</div>

{{-- ── Sécurité du compte ── --}}
<div class="seclbl">{{ __('payeur.profil_securite_compte') }}</div>
<div class="epcard" style="max-width:480px;">

    @if($errors->has('current_password') || $errors->has('password'))
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payeur.profil.password') }}" autocomplete="off">
        @csrf
        @method('PUT')
        {{-- Champ piège anti-autofill navigateur --}}
        <input type="text" name="fake_user" style="display:none;" tabindex="-1" autocomplete="username" />
        <input type="password" name="fake_pass" style="display:none;" tabindex="-1" autocomplete="new-password" />

        <div class="lbl">{{ __('payeur.profil_mdp_actuel') }}</div>
        <div style="position:relative;">
            <input class="inp" type="password" name="current_password" id="pwd_current" required autocomplete="current-password" style="padding-right:40px;" />
            <span onclick="togglePwd('pwd_current',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#888;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
        </div>

        <div class="lbl">{{ __('payeur.profil_nouveau_mdp') }}</div>
        <div style="position:relative;">
            <input class="inp" type="password" name="password" id="pwd_new" placeholder="{{ __('payeur.profil_mdp_placeholder') }}" required autocomplete="new-password" style="padding-right:40px;" />
            <span onclick="togglePwd('pwd_new',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#888;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
        </div>

        <div class="lbl">{{ __('payeur.profil_confirmer_mdp') }}</div>
        <div style="position:relative;">
            <input class="inp" type="password" name="password_confirmation" id="pwd_confirm" required autocomplete="new-password" style="padding-right:40px;" />
            <span onclick="togglePwd('pwd_confirm',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#888;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </span>
        </div>

        <button type="submit" class="btn-o" style="margin-top:4px;width:auto;padding:9px 18px;font-size:12px;">
            {{ __('payeur.profil_changer_mdp') }}
        </button>
    </form>
</div>


@include('partials.telephone-cm-script')
<script>
  document.addEventListener('DOMContentLoaded', function() { initTelephoneCm('.tel-cm-input'); });
function togglePwd(id, el) {
    var inp = document.getElementById(id);
    var isHidden = inp.type === 'password';
    inp.type = isHidden ? 'text' : 'password';
    el.style.color = isHidden ? '#0D9E75' : '#888';
}
</script>

@endsection
