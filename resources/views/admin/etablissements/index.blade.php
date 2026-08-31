@extends('layouts.admin')
@section('title', __('messages.etablissements'))

@push('modals')

{{-- MODAL DETAIL --}}
<div id="modal-detail-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('admin.detail_etablissement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-etab')">x</button>
    </div>
    <div id="modal-detail-etab-content" class="ep-modal-body">
      <div style="text-align:center;padding:30px 0;">
        <div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div>
      </div>
    </div>
  </div>
</div>

{{-- MODAL ACTIVER --}}
<div id="modal-activer-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.activer_etablissement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-activer-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_activation') }}</div>
          <div style="font-size:12px;color:#888;" id="activer-etab-nom"></div>
        </div>
      </div>
      <p style="font-size:13px;color:#555;">{{ __('admin.actif_msg_activation') }}</p>
    </div>
    <div class="ep-modal-foot">
      <form id="form-activer-etab" method="POST">
        @csrf @method('PATCH')
        <button type="button" onclick="epModal.close('modal-activer-etab')"
                style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;margin-right:8px;">
          {{ __('messages.annuler') }}
        </button>
        <button type="submit"
                style="padding:8px 20px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
          {{ __('admin.activer') }}
        </button>
      </form>
    </div>
  </div>
</div>

{{-- MODAL SUSPENDRE --}}
<div id="modal-suspendre-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.suspendre_etablissement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-suspendre-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fef9c3;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_suspension') }}</div>
          <div style="font-size:12px;color:#888;" id="suspendre-etab-nom"></div>
        </div>
      </div>
      <form id="form-suspendre-etab" method="POST">
        @csrf @method('PATCH')
        <div style="margin-bottom:12px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">{{ __('admin.raison_optionnelle') }}</label>
          <textarea name="raison" rows="3"
                    placeholder="{{ __('admin.ph_raison') }}"
                    style="width:100%;padding:8px 12px;font-size:13px;border:1px solid #ddd;border-radius:8px;resize:none;box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-suspendre-etab')"
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
<div id="modal-supprimer-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.supprimer_etablissement') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-supprimer-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_suppression') }}</div>
          <div style="font-size:12px;color:#888;" id="supprimer-etab-nom"></div>
        </div>
      </div>
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
        <p style="font-size:12px;color:#b91c1c;margin:0;">{{ __('admin.irreversible_archive') }}</p>
      </div>
      <label style="display:flex;align-items:flex-start;gap:10px;background:#fff7f7;border:1px solid #fecaca;border-radius:8px;padding:10px 12px;margin-bottom:14px;cursor:pointer;">
        <input type="checkbox" id="supprimer-etab-confirm" onchange="document.getElementById('btn-supprimer-etab-confirme').disabled = !this.checked;"
               style="width:16px;height:16px;margin-top:1px;accent-color:#dc2626;flex-shrink:0;">
        <span style="font-size:12px;color:#b91c1c;line-height:1.5;">{!! __('admin.confirm_suppression_check', [':nom' => '<strong id="supprimer-etab-check-nom"></strong>']) !!}</span>
      </label>
      <form id="form-supprimer-etab" method="POST">
        @csrf @method('DELETE')
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-supprimer-etab')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit" id="btn-supprimer-etab-confirme" disabled
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;opacity:.5;cursor:not-allowed;">
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
    <h1 class="text-xl font-bold text-gray-900">{{ __('admin.etablissements_partenaires') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.gestion_activation_supervision') }}</p>
  </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#E0F5EE] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/></svg>
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
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['en_attente'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.en_attente_s') }}</div>
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
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.etablissements.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="{{ __('admin.rechercher_nom_ville_email') }}"
           class="flex-1 min-w-50 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">{{ __('admin.tous_statuts') }}</option>
      <option value="actif"      {{ request('statut')==='actif'      ? 'selected' : '' }}>Actif</option>
      <option value="en_attente" {{ request('statut')==='en_attente' ? 'selected' : '' }}>En attente</option>
      <option value="suspendu"   {{ request('statut')==='suspendu'   ? 'selected' : '' }}>Suspendu</option>
    </select>
    <select name="type" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">{{ __('admin.tous_les_types') }}</option>
      <option value="maternelle"    {{ request('type')==='maternelle'    ? 'selected' : '' }}>{{ __('admin.maternelle') }}</option>
      <option value="primaire"      {{ request('type')==='primaire'      ? 'selected' : '' }}>{{ __('admin.primaire') }}</option>
      <option value="secondaire"    {{ request('type')==='secondaire'    ? 'selected' : '' }}>{{ __('admin.secondaire') }}</option>
      <option value="universitaire" {{ request('type')==='universitaire' ? 'selected' : '' }}>{{ __('admin.universitaire') }}</option>
      <option value="formation"     {{ request('type')==='formation'     ? 'selected' : '' }}>{{ __('admin.formation_pro') }}</option>
    </select>
    <button type="submit" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','statut','type']))
    <a href="{{ route('admin.etablissements.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">Reinitialiser</a>
    @endif
  </form>
</div>

{{-- Actions groupées --}}
<div id="bulk-toolbar-etabs" class="bg-white border border-gray-200 rounded-xl p-3 mb-4 hidden items-center gap-3 flex-wrap">
  <span class="text-sm text-gray-600 font-medium">
    <span id="nb-selection-etab">0</span> {{ __('admin.element_selectionne') }}
  </span>
  <button id="btn-bulk-activation-etab" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
    {{ __('admin.activer_selection') }}
  </button>
  <button id="btn-bulk-delete-etab" class="bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
    {{ __('admin.supprimer_selection') }}
  </button>
</div>
<form id="form-bulk-etabs" method="POST" class="hidden">
  @csrf
  @method('PATCH')
  <input type="hidden" name="ids" value="">
</form>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl">
  <div>
    <table id="dt-etablissements" class="ep-dt text-sm">
    <thead>
      <tr>
        <th data-orderable="false" class="w-8">
          <input type="checkbox" id="select-all-etablissement" class="ep-checkbox">
        </th>
        <th>{{ __('messages.etablissement') }}</th>
        <th>{{ __('admin.type_region') }}</th>
        <th>{{ __('messages.contact') }}</th>
        <th>{{ __('messages.apprenants') }}</th>
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
var dtEtab;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-etablissements')) {
        $('#dt-etablissements').DataTable().destroy();
    }

    dtEtab = epDT('#dt-etablissements', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("admin.etablissements.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.statut = $('select[name="statut"]').val();
                d.type   = $('select[name="type"]').val();
            }
        },
        columns: [
            { data: 0, orderable: false, responsivePriority: 8 }, // Sélection
            { data: 1, orderable: true,  responsivePriority: 1 }, // Etablissement
            { data: 2, orderable: true,  responsivePriority: 5 }, // Type / Région
            { data: 3, orderable: false, responsivePriority: 6 }, // Contact
            { data: 4, orderable: true,  responsivePriority: 4 }, // Apprenants
            { data: 5, orderable: true,  responsivePriority: 3 }, // Statut
            { data: 6, orderable: true,  responsivePriority: 7 }, // Inscrit le
            { data: 7, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[1, 'asc']],
    });

    // Le formulaire de filtres déclenche un reload AJAX au lieu d'un GET classique
    $('form[action="{{ route('admin.etablissements.index') }}"]').on('submit', function(e) {
        e.preventDefault();
        dtEtab.ajax.reload();
    });

    // Recherche en direct dans le champ texte (debounce léger)
    var searchTimer;
    $('input[name="search"]').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtEtab.search($('input[name="search"]').val()).draw();
        }, 300);
    });

    // Filtres select déclenchent directement le reload
    $('select[name="statut"], select[name="type"]').on('change', function() {
        dtEtab.ajax.reload();
    });
});
function ouvrirDetail(id) {
    const content = document.getElementById('modal-detail-etab-content');
    content.innerHTML = '<div style="text-align:center;padding:30px 0;"><div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div></div>';
    epModal.open('modal-detail-etab');
    fetch('/admin-ep2026/etablissements/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p style="text-align:center;color:#dc2626;padding:20px;">Erreur de chargement.</p>'; });
}
function ouvrirActivation(id, nom) {
    document.getElementById('activer-etab-nom').textContent = nom;
    document.getElementById('form-activer-etab').action = '/admin-ep2026/etablissements/' + id + '/activer';
    epModal.open('modal-activer-etab');
}
function ouvrirSuspension(id, nom) {
    document.getElementById('suspendre-etab-nom').textContent = nom;
    document.getElementById('form-suspendre-etab').action = '/admin-ep2026/etablissements/' + id + '/suspendre';
    epModal.open('modal-suspendre-etab');
}
function ouvrirSuppression(id, nom) {
    document.getElementById('supprimer-etab-nom').textContent = nom;
    document.getElementById('supprimer-etab-check-nom').textContent = nom;
    document.getElementById('supprimer-etab-confirm').checked = false;
    document.getElementById('btn-supprimer-etab-confirme').disabled = true;
    document.getElementById('form-supprimer-etab').action = '/admin-ep2026/etablissements/' + id;
    epModal.open('modal-supprimer-etab');
}

// ── Soumission AJAX des 3 formulaires (activer / suspendre / supprimer) ──
function epSubmitAjax(form, modalId, btnSelector) {
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
            if (modalId) epModal.close(modalId);
            dtEtab.ajax.reload(null, false);
            selectionEtablissements = {};
            mettreAJourSelectionEtab();
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

document.getElementById('form-activer-etab').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjax(this, 'modal-activer-etab', 'button[type="submit"]');
});
document.getElementById('form-suspendre-etab').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjax(this, 'modal-suspendre-etab', 'button[type="submit"]');
});
document.getElementById('form-supprimer-etab').addEventListener('submit', function(e) {
    e.preventDefault();
    epSubmitAjax(this, 'modal-supprimer-etab', 'button[type="submit"]');
});

// ── Sélection groupée d'établissements ──
var selectionEtablissements = {}; // id -> true

function mettreAJourSelectionEtab() {
    var ids = Object.keys(selectionEtablissements).filter(function(k) { return selectionEtablissements[k]; });
    document.getElementById('nb-selection-etab').textContent = ids.length;
    var toolbar = document.getElementById('bulk-toolbar-etabs');
    if (ids.length > 0) {
        toolbar.classList.remove('hidden');
        toolbar.classList.add('flex');
    } else {
        toolbar.classList.add('hidden');
        toolbar.classList.remove('flex');
    }
}

$(document).on('change', '#dt-etablissements tbody .select-etablissement', function() {
    selectionEtablissements[$(this).val()] = $(this).is(':checked');
    mettreAJourSelectionEtab();
});

$(document).on('change', '#select-all-etablissement', function() {
    var checked = $(this).is(':checked');
    $('#dt-etablissements tbody .select-etablissement').each(function() {
        this.checked = checked;
        selectionEtablissements[$(this).val()] = checked;
    });
    mettreAJourSelectionEtab();
});

dtEtab.on('draw', function() {
    var all = $('#dt-etablissements tbody .select-etablissement');
    var checked = 0;
    all.each(function() {
        this.checked = !!selectionEtablissements[$(this).val()];
        if (this.checked) checked++;
    });
    $('#select-all-etablissement').prop('checked', all.length > 0 && checked === all.length);
});

document.getElementById('btn-bulk-activation-etab').addEventListener('click', function() {
    var ids = Object.keys(selectionEtablissements).filter(function(k) { return selectionEtablissements[k]; });
    if (!ids.length) return epToast(@json(__('admin.aucun_selection')), 'error');
    var count = ids.length;
    if (!window.confirm(count + ' ' + @json(__('admin.confirm_activation_selection')))) return;
    var form = document.getElementById('form-bulk-etabs');
    form.method = 'POST';
    form.querySelector('input[name="ids"]').value = ids.join(',');
    form.querySelector('input[name="_method"]').value = 'PATCH';
    form.action = '/admin-ep2026/etablissements/bulk-activer';
    epSubmitAjax(form, null, null);
});

document.getElementById('btn-bulk-delete-etab').addEventListener('click', function() {
    var ids = Object.keys(selectionEtablissements).filter(function(k) { return selectionEtablissements[k]; });
    if (!ids.length) return epToast(@json(__('admin.aucun_selection')), 'error');
    var count = ids.length;
    if (!window.confirm(count + ' ' + @json(__('admin.confirm_suppression_selection')))) return;
    var form = document.getElementById('form-bulk-etabs');
    form.method = 'POST';
    form.querySelector('input[name="ids"]').value = ids.join(',');
    form.querySelector('input[name="_method"]').value = 'DELETE';
    form.action = '/admin-ep2026/etablissements/bulk-destroy';
    epSubmitAjax(form, null, null);
});
</script>
@endpush
