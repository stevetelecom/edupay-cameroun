@extends('layouts.admin')
@section('title', 'Etablissements')

@push('modals')

{{-- MODAL DETAIL --}}
<div id="modal-detail-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>Detail de l'etablissement</h3>
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
      <h3>Activer l'etablissement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-activer-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">Confirmer l'activation</div>
          <div style="font-size:12px;color:#888;" id="activer-etab-nom"></div>
        </div>
      </div>
      <p style="font-size:13px;color:#555;">Cet etablissement sera immediatement actif. Les payeurs pourront effectuer des paiements.</p>
    </div>
    <div class="ep-modal-foot">
      <form id="form-activer-etab" method="POST">
        @csrf @method('PATCH')
        <button type="button" onclick="epModal.close('modal-activer-etab')"
                style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;margin-right:8px;">
          Annuler
        </button>
        <button type="submit"
                style="padding:8px 20px;font-size:13px;font-weight:600;background:#16a34a;color:#fff;border:none;border-radius:8px;cursor:pointer;">
          Activer
        </button>
      </form>
    </div>
  </div>
</div>

{{-- MODAL SUSPENDRE --}}
<div id="modal-suspendre-etab" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>Suspendre l'etablissement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-suspendre-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fef9c3;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">Confirmer la suspension</div>
          <div style="font-size:12px;color:#888;" id="suspendre-etab-nom"></div>
        </div>
      </div>
      <form id="form-suspendre-etab" method="POST">
        @csrf @method('PATCH')
        <div style="margin-bottom:12px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">Raison (optionnelle)</label>
          <textarea name="raison" rows="3"
                    placeholder="Ex : Documents manquants, audit en cours..."
                    style="width:100%;padding:8px 12px;font-size:13px;border:1px solid #ddd;border-radius:8px;resize:none;box-sizing:border-box;"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-suspendre-etab')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Annuler
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#ca8a04;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            Suspendre
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
      <h3>Supprimer l'etablissement</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-supprimer-etab')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div style="width:40px;height:40px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">Confirmer la suppression</div>
          <div style="font-size:12px;color:#888;" id="supprimer-etab-nom"></div>
        </div>
      </div>
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:16px;">
        <p style="font-size:12px;color:#b91c1c;margin:0;">Action irreversible — les donnees seront archivees (soft delete).</p>
      </div>
      <form id="form-supprimer-etab" method="POST">
        @csrf @method('DELETE')
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-supprimer-etab')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Annuler
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#dc2626;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            Supprimer
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
    <h1 class="text-xl font-bold text-gray-900">Etablissements partenaires</h1>
    <p class="text-sm text-gray-500 mt-0.5">Gestion, activation et supervision de toutes les ecoles</p>
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
      <div class="text-xs text-gray-400">Total</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-green-700">{{ $stats['actifs'] }}</div>
      <div class="text-xs text-gray-400">Actifs</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['en_attente'] }}</div>
      <div class="text-xs text-gray-400">En attente</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-red-700">{{ $stats['suspendus'] }}</div>
      <div class="text-xs text-gray-400">Suspendus</div>
    </div>
  </div>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.etablissements.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Rechercher par nom, ville, email..."
           class="flex-1 min-w-50 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">Tous les statuts</option>
      <option value="actif"      {{ request('statut')==='actif'      ? 'selected' : '' }}>Actif</option>
      <option value="en_attente" {{ request('statut')==='en_attente' ? 'selected' : '' }}>En attente</option>
      <option value="suspendu"   {{ request('statut')==='suspendu'   ? 'selected' : '' }}>Suspendu</option>
    </select>
    <select name="type" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">Tous les types</option>
      <option value="maternelle"    {{ request('type')==='maternelle'    ? 'selected' : '' }}>Maternelle</option>
      <option value="primaire"      {{ request('type')==='primaire'      ? 'selected' : '' }}>Primaire</option>
      <option value="secondaire"    {{ request('type')==='secondaire'    ? 'selected' : '' }}>Secondaire</option>
      <option value="universitaire" {{ request('type')==='universitaire' ? 'selected' : '' }}>Universitaire</option>
      <option value="formation"     {{ request('type')==='formation'     ? 'selected' : '' }}>Formation pro.</option>
    </select>
    <button type="submit" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','statut','type']))
    <a href="{{ route('admin.etablissements.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">Reinitialiser</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl">
  <div>
    <table id="dt-etablissements" class="ep-dt text-sm">
    <thead>
      <tr>
        <th>Etablissement</th>
        <th>Type / Région</th>
        <th>Contact</th>
        <th>Apprenants</th>
        <th>Statut</th>
        <th>Inscrit le</th>
        <th data-orderable="false">Actions</th>
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
            { data: 0, orderable: true,  responsivePriority: 1 }, // Etablissement
            { data: 1, orderable: true,  responsivePriority: 5 }, // Type / Région
            { data: 2, orderable: false, responsivePriority: 6 }, // Contact
            { data: 3, orderable: true,  responsivePriority: 4 }, // Apprenants
            { data: 4, orderable: true,  responsivePriority: 3 }, // Statut
            { data: 5, orderable: true,  responsivePriority: 7 }, // Inscrit le
            { data: 6, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[0, 'asc']],
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
            epToast(body.message || 'Action effectuée.', 'success');
            epModal.close(modalId);
            dtEtab.ajax.reload(null, false);
        } else {
            epToast(body.message || 'Une erreur est survenue.', 'error');
        }
    })
    .catch(() => {
        epToast('Erreur réseau — veuillez réessayer.', 'error');
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
</script>
@endpush
