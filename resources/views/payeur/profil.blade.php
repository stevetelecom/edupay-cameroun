@extends('layouts.payeur')

@section('title', 'Profil & notifications')

@section('content')

<div style="font-size:17px;font-weight:700;margin-bottom:16px;">Profil &amp; préférences de notification</div>

<div class="g2">

    {{-- ── Informations personnelles ── --}}
    <div class="epcard">
        <div class="seclbl" style="margin-top:0;">Informations personnelles</div>

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

            <div class="lbl">Prénom</div>
            <input class="inp" name="prenom" value="{{ old('prenom', Auth::user()->prenom) }}" required />

            <div class="lbl">Nom</div>
            <input class="inp" name="nom" value="{{ old('nom', Auth::user()->nom) }}" required />

            <div class="lbl">Téléphone</div>
            <input class="inp" name="telephone" value="{{ old('telephone', Auth::user()->telephone) }}" required />

            <div class="lbl">Email</div>
            <input class="inp" type="email" name="email" value="{{ old('email', Auth::user()->email) }}" />

            <button type="submit" class="btn-p" style="margin-top:4px;width:auto;padding:9px 18px;font-size:12px;">
                Enregistrer
            </button>
        </form>
    </div>

    {{-- ── Notifications ── --}}
    <div class="epcard">
        <div class="seclbl" style="margin-top:0;">Notifications</div>

        <form method="POST" action="{{ route('payeur.profil.notifications') }}" id="form-notifs">
            @csrf
            @method('PUT')

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.36 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    SMS de confirmation
                </span>
                <input type="checkbox" name="notif_sms" value="1" {{ Auth::user()->notif_sms ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Email reçu PDF
                </span>
                <input type="checkbox" name="notif_email" value="1" {{ Auth::user()->notif_email ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>

            <div class="row">
                <span style="display:flex;align-items:center;gap:8px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    Rappel d'échéance (J-5)
                </span>
                <input type="checkbox" name="notif_rappel_echeance" value="1" {{ Auth::user()->notif_rappel_echeance ? 'checked' : '' }} onchange="document.getElementById('form-notifs').submit();" />
            </div>
        </form>

        <div style="font-size:10px;color:#aaa;margin-top:10px;">
            Ces préférences s'enregistrent automatiquement à chaque modification.
        </div>
    </div>

</div>

{{-- ── Sécurité du compte ── --}}
<div class="seclbl">Sécurité du compte</div>
<div class="epcard" style="max-width:480px;">

    @if($errors->has('current_password') || $errors->has('password'))
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payeur.profil.password') }}">
        @csrf
        @method('PUT')

        <div class="lbl">Mot de passe actuel</div>
        <input class="inp" type="password" name="current_password" required />

        <div class="lbl">Nouveau mot de passe</div>
        <input class="inp" type="password" name="password" placeholder="Min. 8 caractères" required />

        <div class="lbl">Confirmer le nouveau mot de passe</div>
        <input class="inp" type="password" name="password_confirmation" required />

        <button type="submit" class="btn-o" style="margin-top:4px;width:auto;padding:9px 18px;font-size:12px;">
            Changer le mot de passe
        </button>
    </form>
</div>

@endsection
