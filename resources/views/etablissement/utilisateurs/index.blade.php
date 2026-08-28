@extends('layouts.etablissement')
@section('title', __('messages.utilisateurs_internes'))

@push('modals')

{{-- ══ MODAL : Inviter un utilisateur ══ --}}
<div id="modal-inviter" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.inviter_utilisateur') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-inviter')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.utilisateurs.store') }}">
      @csrf
      <div class="ep-modal-body">
        <div class="lbl">Email *</div>
        <input class="inp" type="email" name="email" value="{{ old('email') }}" placeholder="jean@lycee-melen.cm" required />
        <div class="lbl">{{ __('etablissement.role_star') }}</div>
        <select class="select" name="role" required>
          <option value="">{{ __('etablissement.choisir_role') }}</option>
          <option value="directeur" {{ old('role')=='directeur'?'selected':'' }}>{{ __('etablissement.role_directeur') }}</option>
          <option value="comptable" {{ old('role')=='comptable'?'selected':'' }}>{{ __('etablissement.role_comptable') }}</option>
          <option value="caissier"  {{ old('role')=='caissier' ?'selected':'' }}>{{ __('etablissement.role_caissier') }}</option>
        </select>
        <div class="lbl">{{ __('etablissement.prenom') }}</div>
        <input class="inp" name="prenom" value="{{ old('prenom') }}" placeholder="Jean" />
        <div class="lbl">{{ __('etablissement.nom') }}</div>
        <input class="inp" name="nom" value="{{ old('nom') }}" placeholder="MVONDO" />
        <div class="lbl">Mot de passe *</div>
        <input class="inp" type="password" name="password" placeholder="{{ __('etablissement.mdp_min_10_ph') }}" autocomplete="new-password" required />
        <div class="lbl">{{ __('etablissement.confirmer_mdp') }}</div>
        <input class="inp" type="password" name="password_confirmation" placeholder="{{ __('etablissement.repeter_mdp_ph') }}" required />
        <div style="background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#1A4F8A;margin-top:4px;">
          {{ __('etablissement.email_bienvenue_hint') }}
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-inviter')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.envoyer_invitation') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Changer le rôle ══ --}}
<div id="modal-role" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.changer_role') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-role')">×</button>
    </div>
    <form id="role-form" method="POST">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div style="font-size:13px;color:#555;margin-bottom:14px;">
          {!! __('etablissement.utilisateur_label') !!}
        </div>
        <div class="lbl">{{ __('etablissement.nouveau_role') }}</div>
        <select class="select" name="role" id="role-select" required>
          <option value="directeur">{{ __('etablissement.role_directeur') }}</option>
          <option value="comptable">{{ __('etablissement.role_comptable') }}</option>
          <option value="caissier">{{ __('etablissement.role_caissier') }}</option>
        </select>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-role')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">{{ __('etablissement.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer un utilisateur ══ --}}
<div id="modal-delete-user" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.retirer_utilisateur') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-delete-user')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('etablissement.confirm_retirer_1') !!}
        {{ __('etablissement.confirm_retirer_2') }}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="epModal.close('modal-delete-user')">{{ __('etablissement.annuler') }}</button>
      <form id="delete-user-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">{{ __('etablissement.retirer') }}</button>
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
    <div style="font-size:17px;font-weight:700;">{{ __('messages.utilisateurs_internes') }}</div>
    <div style="font-size:12px;color:#888;">{{ __('etablissement.utilisateurs_motif') }}</div>
  </div>
  <button class="btn-p" style="width:auto;" onclick="epModal.open('modal-inviter')">
    {{ __('etablissement.inviter_btn') }}
  </button>
</div>

<div class="epcard" style="padding:0;overflow:hidden;">
  <table class="ep-table">
    <thead>
      <tr>
        <th>{{ __('etablissement.utilisateur_col') }}</th><th>{{ __('etablissement.email_col') }}</th><th>{{ __('etablissement.role_col') }}</th><th>{{ __('etablissement.statut') }}</th><th style="text-align:right;">{{ __('etablissement.actions') }}</th>
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
          <span class="pill pb">{{ match(ucfirst($u->roles->first()?->name ?? '')) {
              'Directeur' => __('etablissement.directeur_word'),
              'Comptable' => __('etablissement.comptable_word'),
              'Caissier' => __('etablissement.caissier_word'),
              default => ucfirst($u->roles->first()?->name ?? '—'),
          } }}</span>
        </td>
        <td>
          <span class="pill {{ $u->actif ?? true ? 'pg' : 'pr' }}">{{ ($u->actif ?? true) ? __('etablissement.actif') : __('etablissement.dt_badge_inactif') }}</span>
        </td>
        <td style="text-align:right;white-space:nowrap;">
          <button onclick="changerRole({{ $u->id }}, '{{ addslashes($u->prenom.' '.$u->nom) }}', '{{ $u->roles->first()?->name }}')"
                  style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;margin-right:8px;">
            {{ __('etablissement.changer_role_btn') }}
          </button>
          @if($u->id !== Auth::id())
          <button onclick="supprimerUser({{ $u->id }}, '{{ addslashes($u->prenom.' '.$u->nom) }}')"
                  style="color:var(--ep-red);background:none;border:none;font-size:12px;cursor:pointer;">
            {{ __('etablissement.retirer') }}
          </button>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="5" style="text-align:center;color:#aaa;padding:30px 0;">{{ __('etablissement.aucun_utilisateur') }}</td></tr>
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
