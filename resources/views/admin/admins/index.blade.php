@extends('layouts.admin')

@section('title', __('admin.equipe_supervision'))

@section('content')

@push('modals')

{{-- ══ MODAL : Ajouter un admin ══ --}}
<div id="modal-create-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">{{ __('admin.ajouter_admin') }}</h3>
      <button onclick="fermerModal('modal-create-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <form method="POST" action="{{ route('admin.admins.store') }}">
      @csrf
      <div class="p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.prenom') }} *</label>
            <input type="text" name="prenom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                   placeholder="Wandji" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.nom') }} *</label>
            <input type="text" name="nom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                   placeholder="NGUELE" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.email') }} *</label>
          <input type="email" name="email" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                 placeholder="wandji@edupay.cm" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.telephone_2fa') }}</label>
          <input type="text" class="tel-cm-input" data-allow-fixe="false" name="telephone" placeholder="6XXXXXXXX" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                 placeholder="6XXXXXXXX" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.role_etoile') }}</label>
          <select name="role" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            @foreach($rolesDisponibles as $valeur => $libelle)
              <option value="{{ $valeur }}">{{ $libelle }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.mdp_min10') }}</label>
          <input type="password" name="password" required autocomplete="new-password"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.confirmer_mdp') }}</label>
          <input type="password" name="password_confirmation" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>

      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button"
                onclick="fermerModal('modal-create-admin')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
          {{ __('messages.annuler') }}
        </button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          {{ __('admin.creer_le_compte') }}
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
      <h3 class="font-bold text-red-600">{{ __('admin.supprimer_admin') }}</h3>
      <button onclick="fermerModal('modal-delete-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        {!! __('admin.confirm_suppr_admin', [':nom' => '<span id="delete-admin-nom" class="text-red-600"></span>']) !!}
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-delete-admin')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
        {{ __('messages.annuler') }}
      </button>
      <form id="delete-admin-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
          {{ __('admin.supprimer') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Confirmer suspension ══ --}}
<div id="modal-suspend-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-yellow-100">
      <h3 class="font-bold text-yellow-700">{{ __('admin.suspendre_admin') }}</h3>
      <button onclick="fermerModal('modal-suspend-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        {!! __('admin.confirm_suspendre_admin', [':nom' => '<span id="suspend-admin-nom" class="text-yellow-700"></span>']) !!}
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-suspend-admin')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
        {{ __('messages.annuler') }}
      </button>
      <form id="suspend-admin-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit"
                class="px-5 py-2 text-sm bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg">
          {{ __('admin.suspendre') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Confirmer activation ══ --}}
<div id="modal-activer-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-green-100">
      <h3 class="font-bold text-green-700">{{ __('admin.activer_admin') }}</h3>
      <button onclick="fermerModal('modal-activer-admin')"
              class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        {!! __('admin.confirm_activer_admin', [':nom' => '<span id="activate-admin-nom" class="text-green-700"></span>']) !!}
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-activer-admin')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
        {{ __('messages.annuler') }}
      </button>
      <form id="activate-admin-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit"
                class="px-5 py-2 text-sm bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg">
          {{ __('admin.activer') }}
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
      <h3 class="font-bold text-gray-900">{{ __('admin.detail_admin') }}</h3>
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
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.telephone') }}</span><span id="voir-tel" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.statut') }}</span><span id="voir-statut" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.dern_connexion') }}</span><span id="voir-connexion" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('admin.two_fa') }}</span><span class="font-medium text-green-600">{{ __('admin.email_actif') }}</span></div>
      </div>
    </div>
    <div class="flex justify-end px-6 py-4 border-t">
      <button onclick="fermerModal('modal-voir-admin')" class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">{{ __('admin.fermer') }}</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier un admin ══ --}}
<div id="modal-edit-admin" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">{{ __('admin.modifier_admin') }}</h3>
      <button onclick="fermerModal('modal-edit-admin')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
    </div>
    <form id="form-edit-admin" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.prenom') }} *</label>
            <input type="text" name="prenom" id="edit-prenom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.nom') }} *</label>
            <input type="text" name="nom" id="edit-nom" required
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.email') }} *</label>
          <input type="email" name="email" id="edit-email" required
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('messages.telephone') }}</label>
          <input type="text" class="tel-cm-input" data-allow-fixe="false" name="telephone" id="edit-telephone"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.role_etoile') }}</label>
          <select name="role" id="edit-role" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            <option value="super-admin">{{ __('admin.opt_super_admin_total') }}</option>
            <option value="superviseur">{{ __('admin.opt_superviseur_lecture') }}</option>
            <option value="comptable_plateforme">{{ __('admin.opt_comptable_plateforme') }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.nouveau_mdp_vide') }}</label>
          <input type="password" name="password" autocomplete="new-password"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.confirmer_mdp_opt') }}</label>
          <input type="password" name="password_confirmation"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-edit-admin')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">{{ __('messages.annuler') }}</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">{{ __('messages.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

@endpush

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ __('admin.equipe_supervision') }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.total_admins_enregistres', ['count' => $totalAdmins]) }}</p>
      </div>
      @if(Auth::guard('admin')->user()->hasRole('super-admin'))
        <button onclick="ouvrirModal('modal-create-admin')"
                class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          {{ __('admin.ajouter_admin') }}
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
      <table id="dt-admins" class="ep-dt text-sm" style="width:100%">
        <thead>
          <tr>
            <th>{{ __('admin.admin_col') }}</th>
            <th>{{ __('admin.role_col') }}</th>
            <th>{{ __('messages.contact') }}</th>
            <th>{{ __('messages.dern_connexion') }}</th>
            <th>{{ __('messages.statut') }}</th>
            <th data-orderable="false">{{ __('messages.actions') }}</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

@endsection

@push('scripts')
@include('partials.telephone-cm-script')
@php
    $adminRoleLabelsJs = [
        'super-admin' => __('admin.role_super_admin'),
        'superviseur' => __('admin.role_superviseur'),
        'comptable_plateforme' => __('admin.role_comptable'),
    ];
@endphp
<script>
  document.addEventListener('DOMContentLoaded', function() { initTelephoneCm('.tel-cm-input'); });
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
    var roleLabels = @json($adminRoleLabelsJs);
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

function confirmerSuspensionAdmin(id, nom) {
    document.getElementById('suspend-admin-nom').textContent = nom;
    document.getElementById('suspend-admin-form').action = "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/admins') }}/" + id + '/suspendre';
    var modal = document.getElementById('modal-suspend-admin');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function confirmerActivationAdmin(id, nom) {
    document.getElementById('activate-admin-nom').textContent = nom;
    document.getElementById('activate-admin-form').action = "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/admins') }}/" + id + '/activer';
    var modal = document.getElementById('modal-activer-admin');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function confirmerSuppressionAdmin(id, nom) {
    document.getElementById('delete-admin-nom').textContent = nom;
    document.getElementById('delete-admin-form').action = "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/admins') }}/" + id;
    var modal = document.getElementById('modal-delete-admin');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

var dtAdmins;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-admins')) {
        $('#dt-admins').DataTable().destroy();
    }

    dtAdmins = epDT('#dt-admins', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("admin.admins.datatable") }}',
            type: 'GET'
        },
        columns: [
            { data: 0, orderable: true,  responsivePriority: 1 }, // Administrateur
            { data: 1, orderable: false, responsivePriority: 5 }, // Rôle
            { data: 2, orderable: false, responsivePriority: 4 }, // Contact
            { data: 3, orderable: true,  responsivePriority: 3 }, // Dernière connexion
            { data: 4, orderable: true,  responsivePriority: 6 }, // Statut
            { data: 5, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[0, 'asc']],
    });
});
</script>
@endpush
