@extends('layouts.etablissement')
@section('title', 'Utilisateurs internes')

@push('modals')

{{-- ══ MODAL : Inviter un utilisateur ══ --}}
<div id="modal-inviter" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>+ Inviter un utilisateur interne</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-inviter')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.utilisateurs.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="lbl">Email *</div>
        <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="jean@lycee-melen.cm" required />
        <div class="lbl">Rôle *</div>
        <select class="select" name="role" required>
          <option value="">— Choisir un rôle —</option>
          <option value="directeur" {{ old('role')=='directeur'?'selected':'' }}>Directeur (accès complet)</option>
          <option value="comptable" {{ old('role')=='comptable'?'selected':'' }}>Comptable (saisie + lecture)</option>
          <option value="caissier"  {{ old('role')=='caissier' ?'selected':'' }}>Caissier (saisie uniquement)</option>
        </select>
        <div class="lbl">Prénom</div>
        <input class="inp" name="prenom" value="{{ old('prenom') }}" placeholder="Jean" />
        <div class="lbl">Nom</div>
        <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="MVONDO" />
        <div class="lbl">Mot de passe *</div>
        <input class="inp" type="password" name="password" placeholder="Min. 10 caractères" autocomplete="new-password" required />
        <div class="lbl">Confirmer le mot de passe *</div>
        <input class="inp" type="password" name="password_confirmation" placeholder="Répétez le mot de passe" required />
        <div style="background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#1A4F8A;margin-top:4px;">
          Un email de bienvenue sera envoyé à cet utilisateur avec ses identifiants de connexion.
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-inviter')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Envoyer l'invitation</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Changer le rôle ══ --}}
<div id="modal-role" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>Changer le rôle</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-role')">×</button>
    </div>
    <form id="role-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div style="font-size:13px;color:#555;margin-bottom:14px;">
          Utilisateur : <strong id="role-user-nom"></strong>
        </div>
        <div class="lbl">Nouveau rôle *</div>
        <select class="select" name="role" id="role-select" required>
          <option value="directeur">Directeur (accès complet)</option>
          <option value="comptable">Comptable (saisie + lecture)</option>
          <option value="caissier">Caissier (saisie uniquement)</option>
        </select>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-role')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer un utilisateur ══ --}}
<div id="modal-delete-user" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>🗑 Retirer l'utilisateur</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-user')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        Retirer <strong id="delete-user-nom"></strong> de l'établissement ?<br>
        Son compte sera désactivé mais ses actions restent dans l'historique.
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-user')">Annuler</button>
      <form id="delete-user-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">Retirer</button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
<div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
  ✓ {{ session('success') }}
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <div style="font-size:17px;font-weight:700;">Utilisateurs internes</div>
    <div style="font-size:12px;color:#888;">Directeurs, comptables et caissiers de votre établissement</div>
  </div>
  <button class="btn-p" style="width:auto;" onclick="epModal.open('modal-inviter')">
    + Inviter un utilisateur
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <table class="ep-table">
    <thead>
      <tr>
        <th>Utilisateur</th><th>Email</th><th>Rôle</th><th>Statut</th><th style="text-align:right;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($utilisateurs ?? [] as $u)
      <tr>
        <td>
          <div style="font-weight:600;">{{ $u->prenom }} {{ $u->nom }}</div>
        </td>
        <td style="color:#666;">{{ $u->email }}</td>
        <td>
          <span class="pill pb">{{ ucfirst($u->roles->first()?->name ?? '—') }}</span>
        </td>
        <td>
          <span class="pill {{ $u->actif ?? true ? 'pg' : 'pr' }}">{{ ($u->actif ?? true) ? 'Actif' : 'Inactif' }}</span>
        </td>
        <td style="text-align:right;white-space:nowrap;">
          <button onclick="changerRole({{ $u->id }}, '{{ addslashes($u->prenom.' '.$u->nom) }}', '{{ $u->roles->first()?->name }}')"
                  style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;margin-right:8px;">
            Changer rôle
          </button>
          @if($u->id !== Auth::id())
          <button onclick="supprimerUser({{ $u->id }}, '{{ addslashes($u->prenom.' '.$u->nom) }}')"
                  style="color:var(--ep-red);background:none;border:none;font-size:12px;cursor:pointer;">
            Retirer
          </button>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px 0;">Aucun utilisateur. Invitez votre équipe.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection

@push('scripts')
<script>
function changerRole(id, nom, roleActuel) {
    document.getElementById('role-user-nom').textContent = nom;
    document.getElementById('role-form').action = "{{ url('etablissement/utilisateurs') }}/" + id + "/role";
    document.getElementById('role-select').value = roleActuel || 'comptable';
    epModal.open('modal-role');
}
function supprimerUser(id, nom) {
    document.getElementById('delete-user-nom').textContent = nom;
    document.getElementById('delete-user-form').action = "{{ url('etablissement/utilisateurs') }}/" + id;
    epModal.open('modal-delete-user');
}
@if($errors->any() && old('email'))
document.addEventListener('DOMContentLoaded', function(){ epModal.open('modal-inviter'); });
@endif
</script>
@endpush
