@extends('layouts.etablissement')

@section('title', 'Utilisateurs internes')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <div style="font-size:17px;font-weight:700;">Utilisateurs internes &amp; rôles</div>
        <div style="font-size:12px;color:#888;margin-top:2px;">Directeur, comptables et caissiers de votre établissement</div>
    </div>
    @if($estDirecteur)
        <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;" onclick="document.getElementById('invite-box').style.display='block';this.style.display='none';">
            + Inviter un utilisateur
        </button>
    @endif
</div>

@if($estDirecteur)
<div id="invite-box" class="epcard" style="display:none;margin-bottom:16px;border-left:3px solid var(--ep-teal);">
    <div class="seclbl" style="margin-top:0;">Inviter un nouvel utilisateur</div>

    @if($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('etablissement.utilisateurs.store') }}">
        @csrf
        <div class="inp-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <div class="lbl">Prénom</div>
                <input class="inp" name="prenom" value="{{ old('prenom') }}" required />
            </div>
            <div>
                <div class="lbl">Nom</div>
                <input class="inp" name="nom" value="{{ old('nom') }}" required />
            </div>
        </div>
        <div class="inp-row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <div class="lbl">Email</div>
                <input class="inp" type="email" name="email" value="{{ old('email') }}" required />
            </div>
            <div>
                <div class="lbl">Téléphone</div>
                <input class="inp" name="telephone" value="{{ old('telephone') }}" required />
            </div>
        </div>
        <div class="lbl">Rôle</div>
        <select class="select" name="role" required>
            <option value="">-- Choisir --</option>
            <option value="comptable" {{ old('role') === 'comptable' ? 'selected' : '' }}>Comptable (saisie + lecture)</option>
            <option value="caissier" {{ old('role') === 'caissier' ? 'selected' : '' }}>Caissier (saisie seule)</option>
            <option value="directeur" {{ old('role') === 'directeur' ? 'selected' : '' }}>Directeur (admin complet)</option>
        </select>

        <div style="display:flex;gap:8px;margin-top:6px;">
            <button type="submit" class="btn-p" style="width:auto;padding:9px 18px;font-size:12px;">
                Envoyer l'invitation
            </button>
            <button type="button" class="btn-o" style="width:auto;padding:9px 18px;font-size:12px;"
                    onclick="document.getElementById('invite-box').style.display='none';">
                Annuler
            </button>
        </div>
    </form>
</div>
@endif

<div class="epcard">
    @forelse($utilisateurs as $u)
        @php
            $role = $u->roles->pluck('name')->intersect(['directeur','comptable','caissier'])->first() ?? 'caissier';
            $pillClass = match($role) {
                'directeur' => '', 'comptable' => 'pg', 'caissier' => 'pa', default => 'pa',
            };
            $pillStyle = $role === 'directeur' ? 'background:var(--ep-purple-lt);color:#5B21B6;' : '';
            $avBg = match($role) {
                'directeur' => 'var(--ep-purple-lt)', 'comptable' => 'var(--ep-teal-lt)', 'caissier' => 'var(--ep-gold-lt)', default => '#eee',
            };
            $avColor = match($role) {
                'directeur' => '#5B21B6', 'comptable' => '#0F6E56', 'caissier' => '#8B5E10', default => '#555',
            };
        @endphp
        <div class="row">
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="av" style="background:{{ $avBg }};color:{{ $avColor }};">{{ $u->initiales }}</div>
                <div>
                    <div style="font-size:13px;font-weight:600;">{{ $u->nom }} {{ $u->prenom }}</div>
                    <div style="font-size:11px;color:#888;">{{ $roleLabels[$role] ?? ucfirst($role) }}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="pill {{ $pillClass }}" style="{{ $pillStyle }}">{{ $rolePermissions[$role] ?? '' }}</span>
                @if($estDirecteur && $u->id !== Auth::id())
                    <form method="POST" action="{{ route('etablissement.utilisateurs.destroy', $u) }}"
                          onsubmit="return confirm('Retirer {{ $u->prenom }} {{ $u->nom }} de l\'équipe ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background:transparent;border:none;color:#B91C1C;cursor:pointer;font-size:11px;padding:4px;">
                            Retirer
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            Aucun utilisateur interne trouvé.
        </div>
    @endforelse
</div>

@endsection
