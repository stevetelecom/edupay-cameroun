@extends('layouts.admin')

@section('title', __('admin.equipe_supervision'))

@section('content')

@push('modals')

{{-- ══ MODAL : Ajouter un admin — composant ep-modal (coins 16px, en-tête pastille époxy, Poppins) ══ --}}
<div id="modal-create-admin" class="ep-modal-overlay"
     onclick="if(event.target===this)epModal.close('modal-create-admin')">
  <div class="ep-modal ep-modal-md flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <div style="display:flex;align-items:center;gap:11px;min-width:0;">
        <div class="ep-ico purple ep-ico-side"><span class="material-symbols-outlined">person_add</span></div>
        <h3>{{ __('admin.ajouter_admin') }}</h3>
      </div>
      <button class="ep-modal-close" onclick="epModal.close('modal-create-admin')" aria-label="Fermer">×</button>
    </div>
    <form method="POST" action="{{ route('admin.admins.store') }}">
      @csrf
      <div class="ep-modal-body" style="padding:20px;">

        {{-- Champs en .lbl/.inp : même style que les modals payeur/école --}}
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="lbl">{{ __('admin.prenom') }} *</label>
            <input type="text" name="prenom" required class="inp"
                   placeholder="Wandji" />
          </div>
          <div>
            <label class="lbl">{{ __('messages.nom') }} *</label>
            <input type="text" name="nom" required class="inp"
                   placeholder="NGUELE" />
          </div>
        </div>

        <div>
          <label class="lbl">{{ __('admin.email') }} *</label>
          <input type="email" name="email" required class="inp"
                 placeholder="wandji@edupay.cm" />
        </div>

        <div>
          <label class="lbl">{{ __('admin.telephone_2fa') }}</label>
          <input type="text" name="telephone" placeholder="6XXXXXXXX" required
                 class="inp tel-cm-input"
                 data-allow-fixe="false" />
        </div>

        <div>
          <label class="lbl">{{ __('admin.role_etoile') }}</label>
          <select name="role" required class="select">
            @foreach($rolesDisponibles as $valeur => $libelle)
              <option value="{{ $valeur }}">{{ $libelle }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="lbl">{{ __('admin.mdp_min10') }}</label>
          <input type="password" name="password" required autocomplete="new-password" class="inp" />
        </div>

        <div>
          <label class="lbl">{{ __('admin.confirmer_mdp') }}</label>
          <input type="password" name="password_confirmation" required class="inp" />
        </div>

      </div>
      <div class="ep-modal-foot shrink-0">
        <button type="button"
                onclick="epModal.close('modal-create-admin')"
                class="btn-o" style="width:auto;padding:8px 16px;">
          {{ __('messages.annuler') }}
        </button>
        <button type="submit"
                class="btn-p" style="width:auto;padding:9px 22px;display:inline-flex;align-items:center;gap:6px;">
          <span class="material-symbols-outlined" style="font-size:16px;">check_circle</span>
          {{ __('admin.creer_le_compte') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Confirmer suppression ══ --}}
<div id="modal-delete-admin" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-sm flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="color:var(--ep-red);">
          <span class="material-symbols-outlined" style="font-size:19px;" aria-hidden="true">delete</span>{{ __('admin.supprimer_admin') }}
        </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-delete-admin')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13.5px;color:var(--ep-gris);line-height:1.7;font-family:'Poppins',sans-serif;">
        {!! __('admin.confirm_suppr_admin', ['nom' => '<span id="delete-admin-nom" style="color:var(--ep-red);font-weight:600;"></span>']) !!}
      </p>
    </div>
    <div class="ep-modal-foot shrink-0">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-delete-admin')">
        {{ __('messages.annuler') }}
      </button>
      <form id="delete-admin-form" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="btn-r" style="width:auto;padding:8px 18px;">
          {{ __('admin.supprimer') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Confirmer suspension ══ --}}
<div id="modal-suspend-admin" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-sm flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="color:#B45309;">
          <span class="material-symbols-outlined" style="font-size:19px;" aria-hidden="true">pause_circle</span>{{ __('admin.suspendre_admin') }}
        </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-suspend-admin')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13.5px;color:var(--ep-gris);line-height:1.7;font-family:'Poppins',sans-serif;">
        {!! __('admin.confirm_suspendre_admin', ['nom' => '<span id="suspend-admin-nom" style="color:#B45309;font-weight:600;"></span>']) !!}
      </p>
    </div>
    <div class="ep-modal-foot shrink-0">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-suspend-admin')">
        {{ __('messages.annuler') }}
      </button>
      <form id="suspend-admin-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 18px;background:var(--ep-gold);">
          {{ __('admin.suspendre') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Confirmer activation ══ --}}
<div id="modal-activer-admin" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-sm flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="color:var(--ep-teal2);">
          <span class="material-symbols-outlined" style="font-size:19px;" aria-hidden="true">play_circle</span>{{ __('admin.activer_admin') }}
        </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-activer-admin')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13.5px;color:var(--ep-gris);line-height:1.7;font-family:'Poppins',sans-serif;">
        {!! __('admin.confirm_activer_admin', ['nom' => '<span id="activate-admin-nom" style="color:var(--ep-teal2);font-weight:600;"></span>']) !!}
      </p>
    </div>
    <div class="ep-modal-foot shrink-0">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-activer-admin')">
        {{ __('messages.annuler') }}
      </button>
      <form id="activate-admin-form" method="POST" style="display:inline;">
        @csrf @method('PATCH')
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 18px;">
          {{ __('admin.activer') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- ══ MODAL : Voir un admin ══ --}}
<div id="modal-voir-admin" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-md flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3>
          <span class="material-symbols-outlined" style="font-size:19px;" aria-hidden="true">person</span>{{ __('admin.detail_admin') }}
        </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-voir-admin')">×</button>
    </div>
    <div class="ep-modal-body">
      <div class="flex items-center gap-4 mb-4">
        <div id="voir-avatar" class="w-14 h-14 rounded-full flex items-center justify-center text-lg font-bold" style="background:var(--ep-teal-lt,#E0F5EE);color:#085041;"></div>
        <div>
          <div id="voir-nom" class="text-base font-bold text-gray-900"></div>
          <div id="voir-email" class="text-sm text-gray-500"></div>
          <div id="voir-role-badge" class="mt-1"></div>
        </div>
      </div>
      <div class="rounded-lg p-4 space-y-2" style="background:var(--ep-gris-lt,#F4F6F9);">
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.telephone') }}</span><span id="voir-tel" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.statut') }}</span><span id="voir-statut" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('messages.dern_connexion') }}</span><span id="voir-connexion" class="font-medium"></span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ __('admin.two_fa') }}</span><span class="font-medium text-green-600 flex items-center gap-1">
          <span class="material-symbols-outlined" style="font-size:15px;" aria-hidden="true">verified</span>{{ __('admin.email_actif') }}
        </span></div>
      </div>
    </div>
    <div class="ep-modal-foot shrink-0">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-voir-admin')">{{ __('admin.fermer') }}</button>
    </div>
  </div>
</div>

{{-- ══ MODAL : Modifier un admin ══ --}}
<div id="modal-edit-admin" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-md flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3>
          <span class="material-symbols-outlined" style="font-size:19px;" aria-hidden="true">edit</span>{{ __('admin.modifier_admin') }}
        </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-edit-admin')">×</button>
    </div>
    <form id="form-edit-admin" method="POST" action="">
      @csrf @method('PATCH')
      <div class="ep-modal-body space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="lbl">{{ __('admin.prenom') }} *</label>
            <input type="text" name="prenom" id="edit-prenom" required
                   class="inp" />
          </div>
          <div>
            <label class="lbl">{{ __('messages.nom') }} *</label>
            <input type="text" name="nom" id="edit-nom" required
                   class="inp" />
          </div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.email') }} *</label>
          <input type="email" name="email" id="edit-email" required
                 class="inp" />
        </div>
        <div>
          <label class="lbl">{{ __('messages.telephone') }}</label>
          <input type="text" name="telephone" id="edit-telephone"
                 class="inp tel-cm-input"
                 data-allow-fixe="false" />
        </div>
        <div>
          <label class="lbl">{{ __('admin.role_etoile') }}</label>
          <select name="role" id="edit-role" required
                  class="select">
            <option value="super-admin">{{ __('admin.opt_super_admin_total') }}</option>
            <option value="superviseur">{{ __('admin.opt_superviseur_lecture') }}</option>
            <option value="comptable_plateforme">{{ __('admin.opt_comptable_plateforme') }}</option>
          </select>
        </div>
        <div>
          <label class="lbl">{{ __('admin.nouveau_mdp_vide') }}</label>
          <input type="password" name="password" autocomplete="new-password"
                 class="inp" />
        </div>
        <div>
          <label class="lbl">{{ __('admin.confirmer_mdp_opt') }}</label>
          <input type="password" name="password_confirmation"
                 class="inp" />
        </div>
      </div>
      <div class="ep-modal-foot shrink-0">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-edit-admin')">{{ __('messages.annuler') }}</button>
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 20px;">{{ __('messages.enregistrer') }}</button>
      </div>
    </form>
  </div>
</div>

@endpush

    {{-- En-tête --}}
    <div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:22px;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div class="ep-ico purple ep-ico-entete"><span class="material-symbols-outlined">manage_accounts</span></div>
        <div>
          <h3 style="margin:0;">{{ __('admin.equipe_supervision') }}</h3>
          <p class="text-sm text-gray-500 mt-0.5 ep-sous-titre" style="margin-top:2px;">{{ __('admin.total_admins_enregistres', ['count' => $totalAdmins]) }}</p>
        </div>
      </div>
      @if(Auth::guard('admin')->user()->hasRole('super-admin'))
        <button onclick="ouvrirModal('modal-create-admin')"
                class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          {{ __('admin.ajouter_admin') }}
        </button>
      @endif
    </div>

    @if(session('success'))
      <div class="ep-bandeau succes" style="align-items:center;margin-bottom:16px;">
        <div class="ep-bandeau-ico"><span class="material-symbols-outlined">check_circle</span></div>
        <div class="ep-bandeau-corps"><div class="ep-bandeau-texte">{{ session('success') }}</div></div>
      </div>
    @endif
    @if(session('error'))
      <div class="ep-bandeau danger" style="align-items:center;margin-bottom:16px;">
        <div class="ep-bandeau-ico"><span class="material-symbols-outlined">error</span></div>
        <div class="ep-bandeau-corps"><div class="ep-bandeau-texte">{{ session('error') }}</div></div>
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
