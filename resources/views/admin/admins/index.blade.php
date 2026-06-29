@extends('layouts.admin')

@section('title', 'Équipe de supervision')

@section('content')

@push('modals')

{{-- ══ MODAL : Ajouter un admin ══ --}}
<div id="modal-create-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">+ Ajouter un administrateur</h3>
      <button onclick="fermerModal('modal-create-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <form method="POST" action="{{ route('admin.admins.store') }}">
      @csrf
      <div class="p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Prénom *</label>
            <input type="text" name="prenom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                   placeholder="Wandji" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
            <input type="text" name="nom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                   placeholder="NGUELE" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
          <input type="email" name="email" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                 placeholder="wandji@edupay.cm" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone (pour 2FA) *</label>
          <input type="text" name="telephone" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                 placeholder="6XXXXXXXX" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Rôle *</label>
          <select name="role" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            @foreach($rolesDisponibles as $valeur => $libelle)
              <option value="{{ $valeur }}">{{ $libelle }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Mot de passe * (min. 10 caractères)</label>
          <input type="password" name="password" required autocomplete="new-password"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer le mot de passe *</label>
          <input type="password" name="password_confirmation" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>

      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button"
                onclick="fermerModal('modal-create-admin')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
          Annuler
        </button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          Créer le compte
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Confirmer suppression ══ --}}
<div id="modal-delete-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-red-100">
      <h3 class="font-bold text-red-600">🗑 Supprimer l'administrateur</h3>
      <button onclick="fermerModal('modal-delete-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        Vous allez supprimer définitivement le compte de
        <strong id="delete-admin-nom" class="text-red-600"></strong>.<br><br>
        Cette action est irréversible.
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-delete-admin')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
        Annuler
      </button>
      <form id="delete-admin-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
          Supprimer
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Voir un admin ══ --}}
<div id="modal-voir-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">👤 Détail administrateur</h3>
      <button onclick="fermerModal('modal-voir-admin')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6 space-y-3">
      <div class="flex items-center gap-4 mb-4">
        <div id="voir-avatar" class="w-14 h-14 rounded-full bg-[#E0F5EE] flex items-center justify-center text-lg font-bold text-[#085041]"></div>
        <div>
          <div id="voir-nom" class="text-base font-bold text-gray-900"></div>
          <div id="voir-email" class="text-sm text-gray-500"></div>
          <div id="voir-role-badge" class="mt-1"></div>
        </div>
      </div>
      <div class="bg-gray-50 rounded-lg p-4 space-y-2">
        <div class="flex justify-between text-sm"><span class="text-gray-500">Téléphone</span><span id="voir-tel" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">Statut</span><span id="voir-statut" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">Dernière connexion</span><span id="voir-connexion" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">2FA</span><span class="font-medium text-green-600">✅ Email activé</span></div>
      </div>
    </div>
    <div class="flex justify-end px-6 py-4 border-t">
      <button onclick="fermerModal('modal-voir-admin')" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Fermer</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier un admin ══ --}}
<div id="modal-edit-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">✏️ Modifier l'administrateur</h3>
      <button onclick="fermerModal('modal-edit-admin')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <form id="form-edit-admin" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Prénom *</label>
            <input type="text" name="prenom" id="edit-prenom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Nom *</label>
            <input type="text" name="nom" id="edit-nom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
          <input type="email" name="email" id="edit-email" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone</label>
          <input type="text" name="telephone" id="edit-telephone"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Rôle *</label>
          <select name="role" id="edit-role" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            <option value="super-admin">Super Admin — Accès total</option>
            <option value="superviseur">Superviseur — Lecture + rapports</option>
            <option value="comptable_plateforme">Comptable plateforme — Commissions + exports</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Nouveau mot de passe (laisser vide = inchangé)</label>
          <input type="password" name="password" autocomplete="new-password"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Confirmer le mot de passe</label>
          <input type="password" name="password_confirmation"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-edit-admin')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

@endpush

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Équipe de supervision</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ $admins->count() }} administrateur(s) enregistré(s)</p>
      </div>
      @if(Auth::guard('admin')->user()->hasRole('super-admin'))
        <button onclick="ouvrirModal('modal-create-admin')"
                class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          + Ajouter un administrateur
        </button>
      @endif
    </div>

    @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg mb-4">
        ✓ {{ session('success') }}
      </div>
    @endif
    @if(session('error'))
      <div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-4">
        ✗ {{ session('error') }}
      </div>
    @endif

    {{-- Tableau des admins --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Administrateur</th>
            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Rôle</th>
            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Téléphone</th>
            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Dernière connexion</th>
            <th class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Statut</th>
            <th class="text-right text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          @foreach($admins as $admin)
            <tr class="hover:bg-gray-50">
              <td class="px-5 py-3">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-[#E0F5EE] flex items-center justify-center text-xs font-bold text-[#085041]">
                    {{ $admin->initiales }}
                  </div>
                  <div>
                    <div class="font-semibold text-gray-900">{{ $admin->nom_complet }}</div>
                    <div class="text-xs text-gray-500">{{ $admin->email }}</div>
                  </div>
                  @if($admin->id === Auth::guard('admin')->id())
                    <span class="text-xs bg-blue-50 text-blue-600 border border-blue-200 px-2 py-0.5 rounded-full">Vous</span>
                  @endif
                </div>
              </td>
              <td class="px-5 py-3">
                @php
                  $roleStyles = [
                      'super-admin'          => 'bg-purple-50 text-purple-700 border-purple-200',
                      'superviseur'          => 'bg-blue-50 text-blue-700 border-blue-200',
                      'comptable_plateforme' => 'bg-amber-50 text-amber-700 border-amber-200',
                  ];
                  $roleLabels = [
                      'super-admin'          => 'Super Admin',
                      'superviseur'          => 'Superviseur',
                      'comptable_plateforme' => 'Comptable plateforme',
                  ];
                  $roleAdmin = $admin->getRoleNames()->first() ?? '';
                @endphp
                <span class="text-xs font-medium px-2 py-1 rounded-full border {{ $roleStyles[$roleAdmin] ?? 'bg-gray-50 text-gray-600 border-gray-200' }}">
                  {{ $roleLabels[$roleAdmin] ?? $roleAdmin }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-600">{{ $admin->telephone ?? '—' }}</td>
              <td class="px-5 py-3 text-gray-500 text-xs">
                {{ $admin->derniere_connexion ? $admin->derniere_connexion->diffForHumans() : 'Jamais connecté' }}
              </td>
              <td class="px-5 py-3">
                @if($admin->est_actif)
                  <span class="text-xs font-medium bg-green-50 text-green-700 border border-green-200 px-2 py-1 rounded-full">Actif</span>
                @else
                  <span class="text-xs font-medium bg-red-50 text-red-700 border border-red-200 px-2 py-1 rounded-full">Suspendu</span>
                @endif
              </td>
              <td class="px-5 py-3 text-right">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                  {{-- Voir (tous) --}}
                  <button onclick="voirAdmin(
                      '{{ $admin->initiales }}',
                      '{{ addslashes($admin->nom_complet) }}',
                      '{{ addslashes($admin->email) }}',
                      '{{ addslashes($admin->telephone ?? '—') }}',
                      '{{ $admin->est_actif ? 'Actif' : 'Suspendu' }}',
                      '{{ $admin->derniere_connexion ? $admin->derniere_connexion->diffForHumans() : 'Jamais' }}',
                      '{{ $roleAdmin }}'
                  )" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Voir</button>

                  @if(Auth::guard('admin')->user()->hasRole('super-admin') && $admin->id !== Auth::guard('admin')->id())
                  {{-- Modifier --}}
                  <button onclick="modifierAdmin(
                      {{ $admin->id }},
                      '{{ addslashes($admin->prenom) }}',
                      '{{ addslashes($admin->nom) }}',
                      '{{ addslashes($admin->email) }}',
                      '{{ addslashes($admin->telephone ?? '') }}',
                      '{{ $roleAdmin }}'
                  )" class="text-xs text-amber-600 hover:text-amber-800 font-medium">Modifier</button>

                  {{-- Suspendre/Activer --}}
                  @if($admin->est_actif)
                    <form method="POST" action="{{ route('admin.admins.suspendre', $admin) }}" style="display:inline;">
                      @csrf @method('PATCH')
                      <button type="submit" class="text-xs text-orange-500 hover:text-orange-700 font-medium">Suspendre</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.admins.activer', $admin) }}" style="display:inline;">
                      @csrf @method('PATCH')
                      <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium">Activer</button>
                    </form>
                  @endif

                  {{-- Supprimer --}}
                  <button onclick="confirmerSuppressionAdmin({{ $admin->id }}, '{{ addslashes($admin->prenom) }} {{ addslashes($admin->nom) }}')"
                          class="text-xs text-red-500 hover:text-red-700 font-medium">Supprimer</button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

@endsection

@push('scripts')
<script>
function ouvrirModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden');
    el.style.display = 'flex';
}
function fermerModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden');
    el.style.display = 'none';
}
function voirAdmin(initiales, nom, email, tel, statut, connexion, role) {
    document.getElementById('voir-avatar').textContent = initiales;
    document.getElementById('voir-nom').textContent = nom;
    document.getElementById('voir-email').textContent = email;
    document.getElementById('voir-tel').textContent = tel;
    document.getElementById('voir-connexion').textContent = connexion;
    var statutEl = document.getElementById('voir-statut');
    statutEl.textContent = statut;
    statutEl.className = statut === 'Actif' ? 'font-medium text-green-600' : 'font-medium text-red-600';
    var roleLabels = {
        'super-admin': 'Super Admin',
        'superviseur': 'Superviseur',
        'comptable_plateforme': 'Comptable plateforme'
    };
    var roleColors = {
        'super-admin': 'bg-purple-50 text-purple-700 border-purple-200',
        'superviseur': 'bg-blue-50 text-blue-700 border-blue-200',
        'comptable_plateforme': 'bg-amber-50 text-amber-700 border-amber-200'
    };
    document.getElementById('voir-role-badge').innerHTML =
        '<span class="text-xs font-medium px-2 py-1 rounded-full border ' +
        (roleColors[role] || 'bg-gray-50 text-gray-600 border-gray-200') + '">' +
        (roleLabels[role] || role) + '</span>';
    ouvrirModal('modal-voir-admin');
}

function modifierAdmin(id, prenom, nom, email, telephone, role) {
    document.getElementById('edit-prenom').value = prenom;
    document.getElementById('edit-nom').value = nom;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-telephone').value = telephone;
    document.getElementById('edit-role').value = role;
    document.getElementById('form-edit-admin').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/admins') }}/" + id;
    ouvrirModal('modal-edit-admin');
}

function confirmerSuppressionAdmin(id, nom) {
    document.getElementById('delete-admin-nom').textContent = nom;
    document.getElementById('delete-admin-form').action = "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/admins') }}/" + id;
    var modal = document.getElementById('modal-delete-admin');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}
</script>
@endpush
