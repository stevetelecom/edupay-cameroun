@extends('layouts.etablissement')
@section('title', 'Mon profil')

@section('content')

<div style="max-width:640px;">

  @if(session('success'))
    <div style="background:#E1F5EE;border:1px solid #5DCAA5;border-radius:8px;padding:12px 16px;
                margin-bottom:16px;font-size:13px;color:#085041;display:flex;align-items:center;gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      {{ session('success') }}
    </div>
  @endif

  @if(session('success_password'))
    <div style="background:#E1F5EE;border:1px solid #5DCAA5;border-radius:8px;padding:12px 16px;
                margin-bottom:16px;font-size:13px;color:#085041;display:flex;align-items:center;gap:8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
      {{ session('success_password') }}
    </div>
  @endif

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

    @if($errors->has('current_password') || $errors->has('password'))
      <div style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;
                  margin-bottom:14px;font-size:12px;color:#991B1B;">
        @error('current_password'){{ $message }}@enderror
        @error('password'){{ $message }}@enderror
      </div>
    @endif

    <form method="POST" action="{{ route('etablissement.profil.password') }}">
      @csrf @method('PUT')
      <div style="margin-bottom:12px;">
        <div class="lbl">Mot de passe actuel *</div>
        <input class="inp" type="password" name="current_password" required />
      </div>
      <div style="margin-bottom:12px;">
        <div class="lbl">Nouveau mot de passe *</div>
        <input class="inp" type="password" name="password" placeholder="Min. 8 caractères" required />
      </div>
      <div style="margin-bottom:16px;">
        <div class="lbl">Confirmer le nouveau mot de passe *</div>
        <input class="inp" type="password" name="password_confirmation" required />
      </div>
      <button type="submit" class="btn-p" style="width:auto;padding:9px 20px;">
        Changer le mot de passe
      </button>
    </form>
  </div>

</div>

@endsection
