@extends('layouts.admin')
@section('title', __('messages.comptes_payeurs'))

@push('modals')

{{-- MODAL DETAIL --}}
<div id="modal-detail-payeur" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('admin.detail_compte_payeur') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-payeur')">x</button>
    </div>
    <div id="modal-detail-payeur-content" class="ep-modal-body">
      <div style="text-align:center;padding:30px 0;">
        <div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL REACTIVER --}}
<div id="modal-activer-payeur" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.reactiver_compte') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-activer-payeur')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_reactivation') }}</div>
          <div style="font-size:12px;color:#888;" id="activer-payeur-nom"></div>
        </div>
      </div>
      <p style="font-size:13px;color:#555;">{{ __('admin.msg_reactivation') }}</p>
    </div>
    <div class="ep-modal-foot">
      <form id="form-activer-payeur" method="POST">
        @csrf @method('PATCH')
        <button type="button" onclick="epModal.close('modal-activer-payeur')"
                style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;margin-right:8px;">
          {{ __('messages.annuler') }}
        </button>
        <button type="submit"
                style="padding:8px 20px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
          {{ __('admin.reactiver') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- MODAL SUSPENDRE --}}
<div id="modal-suspendre-payeur" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.suspendre_compte') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-suspendre-payeur')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fef9c3;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_suspension') }}</div>
          <div style="font-size:12px;color:#888;" id="suspendre-payeur-nom"></div>
        </div>
      </div>
      <form id="form-suspendre-payeur" method="POST">
        @csrf @method('PATCH')
        <div style="margin-bottom:12px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">{{ __('admin.raison_optionnelle') }}</label>
          <textarea name="raison" rows="3"
                    placeholder="Ex : Fraude suspectée, non-respect des conditions..."
                    style="width:100%;padding:8px 12px;font-size:13px;border:1px solid #ddd;border-radius:8px;resize:none;box-sizing:border-box;"></textarea>
        </div>
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
          <p style="font-size:12px;color:#92400e;margin:0;">{{ __('admin.msg_suspension') }}</p>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-suspendre-payeur')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#ca8a04;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ __('admin.suspendre') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL SUPPRIMER --}}
<div id="modal-supprimer-payeur" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.supprimer_compte') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-supprimer-payeur')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_suppression') }}</div>
          <div style="font-size:12px;color:#888;" id="supprimer-payeur-nom"></div>
        </div>
      </div>
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
        <p style="font-size:12px;color:#b91c1c;margin:0;">{{ __('admin.irreversible_archive') }}</p>
      </div>
      <form id="form-supprimer-payeur" method="POST">
        @csrf @method('DELETE')
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-supprimer-payeur')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ __('admin.supprimer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">{{ __('admin.comptes_payeurs') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.parents_eleves_etudiants') }}</p>
  </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#E0F5EE] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-gray-900">{{ $stats['total'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.total') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-green-700">{{ $stats['actifs'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.actifs') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-red-700">{{ $stats['suspendus'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.suspendus') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#FEF3DC] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#854F0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-[#854F0B]">{{ $stats['parents'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.parents') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#FEF3DC] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#854F0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21v-2a4 4 0 0 0-3-3.87"/><path d="M17 22v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="15" cy="6" r="3"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-[#854F0B]">{{ $stats['eleves'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.eleves') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#E8F0FE] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 9L12 4 2 9l10 5 10-5z"/><path d="M6 11.5V16c0 1.5 2.7 3 6 3s6-1.5 6-3v-4.5"/><path d="M22 9v6"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-blue-700">{{ $stats['etudiants'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.etudiants') }}</div>
    </div>
  </div>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <div class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" placeholder="Rechercher par nom, email, téléphone..."
           class="flex-1 min-w-50 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">{{ __('admin.tous_statuts') }}</option>
      <option value="actif">Actif</option>
      <option value="suspendu">Suspendu</option>
    </select>
    <select name="profil" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">{{ __('admin.tous_profils') }}</option>
      <option value="parent">{{ __('admin.parent') }}</option>
      <option value="eleve">{{ __('admin.eleve') }}</option>
      <option value="etudiant">{{ __('admin.etudiant') }}</option>
    </select>
    <select name="etablissement_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" onchange="dtPayeurs.ajax.reload()">
      <option value="">{{ __('admin.tous_etablissements') }}</option>
      @foreach($etablissements as $etab)
        <option value="{{ $etab->id }}" {{ request('etablissement_id') == $etab->id ? 'selected' : '' }}>{{ $etab->nom }}</option>
      @endforeach
    </select>
    <button type="button" onclick="dtPayeurs.ajax.reload()" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    <button type="button" onclick="reinitialiserFiltresPayeurs()" class="text-sm text-gray-400 hover:text-gray-600 px-2">{{ __('admin.reinitialiser') }}</button>
  </div>
</div>

{{-- Actions groupées --}}
<div id="bulk-toolbar-payeurs" class="bg-white border border-gray-200 rounded-xl p-3 mb-4 hidden items-center gap-3 flex-wrap">
  <span class="text-sm text-gray-600 font-medium">
    <span id="nb-selection-payeur">0</span> {{ __('admin.element_selectionne') }}
  </span>
  <button id="btn-bulk-activation-payeur" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
    {{ __('admin.activer_selection') }}
  </button>
  <button id="btn-bulk-suspension-payeur" class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
    {{ __('admin.suspendre_selection') }}
  </button>
  <button id="btn-bulk-delete-payeur" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
    {{ __('admin.supprimer_selection') }}
  </button>
</div>
<form id="form-bulk-payeurs" method="POST" class="hidden">
  @csrf
  @method('PATCH')
  <input type="hidden" name="ids" value="">
</form>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl">
  <div>
    <table id="dt-payeurs" class="ep-dt text-sm">
    <thead>
      <tr>
        <th data-orderable="false" class="w-8">
          <input type="checkbox" id="select-all-payeur" class="ep-checkbox">
        </th>
        <th>{{ __('admin.payeur_col') }}</th>
        <th>{{ __('messages.contact') }}</th>
        <th>{{ __('admin.enfants') }}</th>
        <th>{{ __('messages.statut') }}</th>
        <th>{{ __('admin.inscrit_le') }}</th>
        <th data-orderable="false">{{ __('messages.actions') }}</th>
      </tr>
    </thead>
    <tbody></tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
var dtPayeurs;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-payeurs')) {
        $('#dt-payeurs').DataTable().destroy();
    }

    dtPayeurs = epDT('#dt-payeurs', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("admin.payeurs.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.statut = $('select[name="statut"]').val();
                d.profil = $('select[name="profil"]').val();
                d.etablissement_id = $('select[name="etablissement_id"]').val();
            }
        },
        columns: [
            { data: 0, orderable: false, responsivePriority: 8 }, // Sélection
            { data: 1, orderable: true,  responsivePriority: 1 }, // Payeur
            { data: 2, orderable: false, responsivePriority: 5 }, // Contact
            { data: 3, orderable: true,  responsivePriority: 4 }, // Enfants
            { data: 4, orderable: true,  responsivePriority: 3 }, // Statut
            { data: 5, orderable: true,  responsivePriority: 6 }, // Inscrit le
            { data: 6, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[1, 'asc']],
    });

    var searchTimer;
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtPayeurs.search($('input[name="search"]').val()).draw();
        }, 300);
    });

    $('select[name="statut"], select[name="profil"]').on('change', function() {
        dtPayeurs.ajax.reload();
    });
});

function reinitialiserFiltresPayeurs() {
    $('input[name="search"]').val('');
    $('select[name="statut"]').val('');
    $('select[name="profil"]').val('');
    $('select[name="etablissement_id"]').val('');
    dtPayeurs.ajax.reload();
}

function ouvrirDetailPayeur(id) {
    const content = document.getElementById('modal-detail-payeur-content');
    content.innerHTML = '<div style="text-align:center;padding:30px 0;"><div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div></div>';
    epModal.open('modal-detail-payeur');
    fetch('/admin-ep2026/payeurs/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p style="text-align:center;color:#dc2626;padding:20px;">Erreur de chargement.</p>'; });
}
function ouvrirActivationPayeur(id, nom) {
    document.getElementById('activer-payeur-nom').textContent = nom;
    document.getElementById('form-activer-payeur').action = '/admin-ep2026/payeurs/' + id + '/activer';
    epModal.open('modal-activer-payeur');
}
function ouvrirSuspensionPayeur(id, nom) {
    document.getElementById('suspendre-payeur-nom').textContent = nom;
    document.getElementById('form-suspendre-payeur').action = '/admin-ep2026/payeurs/' + id + '/suspendre';
    epModal.open('modal-suspendre-payeur');
}
function ouvrirSuppressionPayeur(id, nom) {
    document.getElementById('supprimer-payeur-nom').textContent = nom;
    document.getElementById('form-supprimer-payeur').action = '/admin-ep2026/payeurs/' + id;
    epModal.open('modal-supprimer-payeur');
}

// ── Soumission AJAX des 3 formulaires ──
function epSubmitAjaxPayeur(form, modalId, btnSelector) {
    const btn = form.querySelector(btnSelector);
    const btnTexteOriginal = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = '...'; }

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: new FormData(form),
    })
    .then(r => r.json().then(data => ({ status: r.status, body: data })))
    .then(({ status, body }) => {
        if (status >= 200 && status < 300 && body.success) {
            epToast(body.message || @json(__('admin.action_effectuee')), 'success');
            epModal.close(modalId);
            dtPayeurs.ajax.reload(null, false);
        } else {
            epToast(body.message || @json(__('admin.une_erreur_survenue')), 'error');
        }
    })
    .catch(() => {
        epToast(@json(__('admin.erreur_reseau')), 'error');
    })
    .finally(() => {
        if (btn) { btn.disabled = false; btn.textContent = btnTexteOriginal; }
    });
}

document.getElementById('form-activer-payeur').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjaxPayeur(this, 'modal-activer-payeur', 'button[type="submit"]');
});
document.getElementById('form-suspendre-payeur').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjaxPayeur(this, 'modal-suspendre-payeur', 'button[type="submit"]');
});
document.getElementById('form-supprimer-payeur').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjaxPayeur(this, 'modal-supprimer-payeur', 'button[type="submit"]');
});

// ── Sélection groupée de comptes payeurs ──
var selectionPayeurs = {}; // id -> true

function mettreAJourSelectionPayeur() {
    var ids = Object.keys(selectionPayeurs).filter(function(k) { return selectionPayeurs[k]; });
    document.getElementById('nb-selection-payeur').textContent = ids.length;
    var toolbar = document.getElementById('bulk-toolbar-payeurs');
    if (ids.length > 0) {
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
    } else {
        toolbar.classList.add('hidden');
        toolbar.classList.remove('flex');
    }
}

$(document).on('change', '#dt-payeurs tbody .payeur-check', function() {
    selectionPayeurs[$(this).val()] = $(this).is(':checked');
    mettreAJourSelectionPayeur();
});

$(document).on('change', '#select-all-payeur', function() {
    var checked = $(this).is(':checked');
    $('#dt-payeurs tbody .payeur-check').each(function() {
        this.checked = checked;
        selectionPayeurs[$(this).val()] = checked;
    });
    mettreAJourSelectionPayeur();
});

dtPayeurs.on('draw', function() {
    var all = $('#dt-payeurs tbody .payeur-check');
    var checked = 0;
    all.each(function() {
        this.checked = !!selectionPayeurs[$(this).val()];
        if (this.checked) checked++;
    });
    $('#select-all-payeur').prop('checked', all.length > 0 && checked === all.length);
});

function epSubmitAjaxBulkPayeur(action, method, ids) {
    var form = document.getElementById('form-bulk-payeurs');
    form.querySelector('input[name="ids"]').value = ids.join(',');
    form.method = 'POST';
    form.querySelector('input[name="_method"]').value = method;
    form.action = action;

    const btn = null;
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: new FormData(form),
    })
    .then(r => r.json().then(data => ({ status: r.status, body: data })))
    .then(({ status, body }) => {
        if (status >= 200 && status < 300 && body.success) {
            epToast(body.message || @json(__('admin.action_effectuee')), 'success');
            dtPayeurs.ajax.reload(null, false);
            selectionPayeurs = {};
            mettreAJourSelectionPayeur();
        } else {
            epToast(body.message || @json(__('admin.une_erreur_survenue')), 'error');
        }
    })
    .catch(() => {
        epToast(@json(__('admin.erreur_reseau')), 'error');
    });
}

document.getElementById('btn-bulk-activation-payeur').addEventListener('click', function() {
    var ids = Object.keys(selectionPayeurs).filter(function(k) { return selectionPayeurs[k]; });
    if (!ids.length) return epToast(@json(__('admin.aucun_selection')), 'error');
    if (!window.confirm(ids.length + ' ' + @json(__('admin.confirm_activation_selection_payeur')))) return;
    epSubmitAjaxBulkPayeur('/admin-ep2026/payeurs/bulk-activer', 'PATCH', ids);
});

document.getElementById('btn-bulk-suspension-payeur').addEventListener('click', function() {
    var ids = Object.keys(selectionPayeurs).filter(function(k) { return selectionPayeurs[k]; });
    if (!ids.length) return epToast(@json(__('admin.aucun_selection')), 'error');
    if (!window.confirm(ids.length + ' ' + @json(__('admin.confirm_suspension_selection_payeur')))) return;
    epSubmitAjaxBulkPayeur('/admin-ep2026/payeurs/bulk-suspendre', 'PATCH', ids);
});

document.getElementById('btn-bulk-delete-payeur').addEventListener('click', function() {
    var ids = Object.keys(selectionPayeurs).filter(function(k) { return selectionPayeurs[k]; });
    if (!ids.length) return epToast(@json(__('admin.aucun_selection')), 'error');
    if (!window.confirm(ids.length + ' ' + @json(__('admin.confirm_suppression_selection_payeur')))) return;
    epSubmitAjaxBulkPayeur('/admin-ep2026/payeurs/bulk-destroy', 'DELETE', ids);
});
</script>
@endpush
