@extends('layouts.admin')
@section('title', 'Gestion des abonnements')

@push('modals')
{{-- ══ MODAL : Nouvel abonnement ══ --}}
<div id="modal-new-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">+ Activer un abonnement</h3>
      <button onclick="fermerModal('modal-new-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form method="POST" action="{{ route('admin.abonnements.store') }}">
      @csrf
      <div class="p-6 space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Établissement *</label>
          <select name="etablissement_id" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            <option value="">-- Choisir un établissement --</option>
            @foreach(\App\Models\Etablissement::where('statut','actif')->orderBy('nom')->get() as $etab)
              <option value="{{ $etab->id }}">{{ $etab->nom }} — {{ $etab->ville }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Plan *</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   style="border-color: {{ $plan['couleur'] }}20;"
                   id="plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-lg font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">FCFA/mois</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Date de début *</label>
          <input type="date" name="date_debut" required value="{{ now()->format('Y-m-d') }}"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement reçu</label>
          <input type="text" name="reference_paiement" placeholder="Ex: MTN-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2" placeholder="Remarques éventuelles..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-new-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          Activer l'abonnement
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Renouveler ══ --}}
<div id="modal-renew-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">🔄 Renouveler l'abonnement</h3>
      <button onclick="fermerModal('modal-renew-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form id="form-renew" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-700">
          Renouvellement pour : <strong id="renew-nom"></strong><br/>
          Plan actuel : <strong id="renew-plan"></strong>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement reçu</label>
          <input type="text" name="reference_paiement" placeholder="Ex: OM-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-renew-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#185FA5] hover:bg-[#144d8a] text-white font-semibold rounded-lg">
          Confirmer le renouvellement
        </button>
      </div>
    </form>
  </div>
</div>
{{-- ══ MODAL : Modifier le plan ══ --}}
<div id="modal-edit-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">Modifier le plan</h3>
      <button onclick="fermerModal('modal-edit-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form id="form-edit-abo" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
          Établissement : <strong id="edit-abo-nom"></strong>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">Nouveau plan *</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   id="edit-plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selEditPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-sm font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">FCFA/mois</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement</label>
          <input type="text" name="reference_paiement" placeholder="Ex: MTN-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-edit-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer abonnement ══ --}}
<div id="modal-delete-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-red-100">
      <h3 class="font-bold text-red-600">Supprimer l'abonnement</h3>
      <button onclick="fermerModal('modal-delete-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        Vous allez supprimer l'abonnement de <strong id="delete-abo-nom" class="text-red-600"></strong>.<br/><br/>
        L'établissement n'aura plus de plan actif. Cette action est irréversible.
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-delete-abo')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
      <form id="form-delete-abo" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
          Supprimer
        </button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg mb-4">✓ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-4">✗ {{ session('error') }}</div>
@endif

{{-- En-tête --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-xl font-bold text-gray-900">Gestion des abonnements</h1>
    <p class="text-sm text-gray-500 mt-0.5">Suivi et activation des plans établissements</p>
  </div>
  <button onclick="ouvrirModal('modal-new-abo')"
          class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
    + Activer un abonnement
  </button>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#0D9E75]">{{ $stats['actifs'] }}</div>
    <div class="text-xs text-gray-500 mt-1">Abonnements actifs</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#E8A020]">{{ $stats['grace_period'] }}</div>
    <div class="text-xs text-gray-500 mt-1">En grace period</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-red-500">{{ $stats['expires'] }}</div>
    <div class="text-xs text-gray-500 mt-1">Expirés</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#0B2545]">{{ number_format($stats['revenus_mois'],0,',',' ') }}</div>
    <div class="text-xs text-gray-500 mt-1">FCFA encaissés (mois)</div>
  </div>
</div>

{{-- Filtres --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
  <div class="flex flex-wrap gap-2">
    <select id="filter-statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">Tous les statuts</option>
      <option value="actif">Actif</option>
      <option value="grace_period">Grace period</option>
      <option value="expire">Expiré</option>
    </select>
    <select id="filter-plan" class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">Tous les plans</option>
      <option value="basique">Basique</option>
      <option value="standard">Standard</option>
      <option value="premium">Premium</option>
    </select>
  </div>
  <div class="flex items-center gap-2 ml-auto">
    <input id="filter-search" type="text" placeholder="Rechercher un établissement…"
           class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white w-64" />
    <button type="button" onclick="resetAbonnementFilters()"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Réinitialiser</button>
  </div>
</div>

{{-- Tableau responsive --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <table id="dt-abonnements" class="ep-dt text-sm" style="width:100%">
    <thead>
      <tr>
        <th>Établissement</th>
        <th>Plan</th>
        <th>Période</th>
        <th>Statut</th>
        <th>Montant</th>
        <th data-orderable="false">Actions</th>
      </tr>
    </thead>
    <tbody></tbody>
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
function selPlan(plan) {
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('plan-card-' + p);
        if (card) card.style.opacity = p === plan ? '1' : '0.5';
    });
}
function modifierAbo(id, nom, planActuel) {
    document.getElementById('edit-abo-nom').textContent = nom;
    document.getElementById('form-edit-abo').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id;
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('edit-plan-card-' + p);
        if (card) {
            card.style.opacity = '1';
            card.style.borderColor = p === planActuel ? '#0D9E75' : '';
            const input = card.querySelector('input');
            if (input) input.checked = (p === planActuel);
        }
    });
    ouvrirModal('modal-edit-abo');
}
function selEditPlan(plan) {
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('edit-plan-card-' + p);
        if (card) card.style.borderColor = p === plan ? '#0D9E75' : '';
    });
}
function supprimerAbo(id, nom) {
    document.getElementById('delete-abo-nom').textContent = nom;
    document.getElementById('form-delete-abo').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id;
    ouvrirModal('modal-delete-abo');
}
function renouveler(id, nom, plan) {
    document.getElementById('renew-nom').textContent  = nom;
    document.getElementById('renew-plan').textContent = plan;
    document.getElementById('form-renew').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id + '/renouveler';
    ouvrirModal('modal-renew-abo');
}

var dtAbonnements;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-abonnements')) {
        $('#dt-abonnements').DataTable().destroy();
    }

    dtAbonnements = epDT('#dt-abonnements', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("admin.abonnements.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.statut = $('#filter-statut').val();
                d.plan   = $('#filter-plan').val();
            }
        },
        columns: [
            { data: 0, orderable: true,  responsivePriority: 1 }, // Établissement
            { data: 1, orderable: true,  responsivePriority: 5 }, // Plan
            { data: 2, orderable: true,  responsivePriority: 3 }, // Période
            { data: 3, orderable: true,  responsivePriority: 4 }, // Statut
            { data: 4, orderable: true,  responsivePriority: 6 }, // Montant
            { data: 5, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[0, 'asc']],
    });

    $('#filter-statut, #filter-plan').on('change', function() {
        dtAbonnements.ajax.reload();
    });

    var searchTimer;
    $('#filter-search').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtAbonnements.search($('#filter-search').val()).draw();
        }, 300);
    });
});

function resetAbonnementFilters() {
    $('#filter-statut').val('');
    $('#filter-plan').val('');
    $('#filter-search').val('');
    dtAbonnements.search('').draw();
}
</script>
@endpush
