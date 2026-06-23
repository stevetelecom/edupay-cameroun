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
           class="flex-1 min-w-[200px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
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
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Etablissement</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type / Region</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Contact</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Apprenants</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Inscrit le</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse ($etablissements as $etab)
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3">
          <div class="font-semibold text-gray-900">{{ $etab->nom }}</div>
          <div class="text-xs text-gray-400">{{ $etab->code_etablissement }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-gray-700">{{ ucfirst($etab->type ?? '—') }}</div>
          <div class="text-xs text-gray-400">{{ $etab->ville }}, {{ $etab->region }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-gray-700">{{ $etab->telephone }}</div>
          <div class="text-xs text-gray-400">{{ $etab->email }}</div>
        </td>
        <td class="px-4 py-3 text-center font-semibold text-gray-800">{{ $etab->apprenants_count }}</td>
        <td class="px-4 py-3">
          @php
            $sc = match($etab->statut) {
              'actif'      => 'bg-green-100 text-green-800',
              'en_attente' => 'bg-yellow-100 text-yellow-800',
              'suspendu'   => 'bg-red-100 text-red-800',
              default      => 'bg-gray-100 text-gray-600',
            };
          @endphp
          <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sc }}">
            {{ ucfirst(str_replace('_', ' ', $etab->statut)) }}
          </span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ $etab->created_at->format('d/m/Y') }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center justify-center gap-1.5">
            {{-- Detail --}}
            <button onclick="ouvrirDetail({{ $etab->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#E0F5EE] hover:bg-[#c4eadb] text-[#0D9E75] transition-colors" title="Detail">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            {{-- Activer --}}
            @if($etab->statut !== 'actif')
            <button onclick="ouvrirActivation({{ $etab->id }}, {{ json_encode($etab->nom) }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600 transition-colors" title="Activer">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            @endif
            {{-- Suspendre --}}
            @if($etab->statut !== 'suspendu')
            <button onclick="ouvrirSuspension({{ $etab->id }}, {{ json_encode($etab->nom) }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-yellow-50 hover:bg-yellow-100 text-yellow-600 transition-colors" title="Suspendre">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            </button>
            @endif
            {{-- Supprimer --}}
            <button onclick="ouvrirSuppression({{ $etab->id }}, {{ json_encode($etab->nom) }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-red-50 hover:bg-red-100 text-red-500 transition-colors" title="Supprimer">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">Aucun etablissement trouve.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
  @if($etablissements->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $etablissements->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
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
</script>
@endpush
