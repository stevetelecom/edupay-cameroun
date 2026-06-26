@extends('layouts.admin')
@section('title', 'Commissions')

@push('modals')
{{-- MODAL MODIFIER TAUX --}}
<div id="modal-modifier-taux" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>Modifier le taux de commission</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-taux')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:40px;height:40px;background:#FEF3DC;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">Nouveau taux</div>
          <div style="font-size:12px;color:#888;" id="modifier-taux-etab-nom"></div>
        </div>
      </div>
      <form id="form-modifier-taux" method="POST">
        @csrf @method('PATCH')
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
            Taux de commission (ex: 0.025 pour 2,5%)
          </label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" name="taux_commission" id="input-taux"
                   step="0.001" min="0" max="0.1" required
                   style="flex:1;padding:10px 12px;font-size:14px;font-weight:600;border:2px solid #E8A020;border-radius:8px;outline:none;text-align:center;" />
            <span style="font-size:13px;color:#888;">= <span id="taux-pct">0%</span></span>
          </div>
          <div style="font-size:11px;color:#aaa;margin-top:6px;">Valeur entre 0 et 0.1 (0% a 10%)</div>
        </div>
        <div style="background:#FEF3DC;border-left:3px solid #E8A020;border-radius:6px;padding:10px 12px;margin-bottom:16px;">
          <div style="font-size:12px;color:#854F0B;">
            Taux actuel plateforme : <strong>{{ number_format($tauxActuel * 100, 1) }}%</strong>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-modifier-taux')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Annuler
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#E8A020;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL PRELEVER --}}
<div id="modal-prelever" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>Marquer comme prelevee</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-prelever')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:16px;">
        Cette commission sera marquee comme prelevee. Cette action est enregistree dans les logs d'audit.
      </p>
      <form id="form-prelever" method="POST">
        @csrf @method('PATCH')
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-prelever')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Annuler
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            Confirmer
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
    <h1 class="text-xl font-bold text-gray-900">Commissions</h1>
    <p class="text-sm text-gray-500 mt-0.5">Suivi et prelevement des commissions par etablissement</p>
  </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#FEF3DC] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#E8A020]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-[#E8A020]">{{ number_format($stats['total_mois'], 0, ',', ' ') }}</div>
      <div class="text-xs text-gray-400">FCFA ce mois</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-gray-900">{{ $stats['nb_mois'] }}</div>
      <div class="text-xs text-gray-400">Ce mois</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['calculees'] }}</div>
      <div class="text-xs text-gray-400">A prelever</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-green-700">{{ $stats['prelevees'] }}</div>
      <div class="text-xs text-gray-400">Prelevees</div>
    </div>
  </div>
</div>

{{-- Bandeau taux global --}}
<div style="background:#FEF3DC;border-left:4px solid #E8A020;border-radius:10px;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <div>
    <div style="font-size:13px;font-weight:700;color:#854F0B;">Taux de commission global — configurable</div>
    <div style="font-size:12px;color:#BA7517;margin-top:2px;">
      <strong>{{ number_format($tauxActuel * 100, 1, ',', '') }}%</strong>
      par transaction · Profil Standard · Conforme COBAC/BEAC
    </div>
  </div>
  <button onclick="ouvrirModifierTauxGlobal()"
          style="display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:#854F0B;color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
    Modifier le taux
  </button>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.commissions.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Rechercher par ecole..."
           class="flex-1 min-w-[180px] px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]" />
    <select name="etablissement_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]">
      <option value="">Tous les etablissements</option>
      @foreach($etablissements as $e)
      <option value="{{ $e->id }}" {{ request('etablissement_id')==$e->id ? 'selected' : '' }}>{{ $e->nom }}</option>
      @endforeach
    </select>
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]">
      <option value="">Tous statuts</option>
      <option value="calculee" {{ request('statut')==='calculee' ? 'selected' : '' }}>A prelever</option>
      <option value="prelevee" {{ request('statut')==='prelevee' ? 'selected' : '' }}>Prelevee</option>
    </select>
    <button type="submit" class="bg-[#E8A020] hover:bg-[#cc8c1a] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','statut','etablissement_id']))
    <a href="{{ route('admin.commissions.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">Reinitialiser</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div class="responsive-admin-table-container">
    <table class="responsive-admin-table text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Etablissement</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Transaction</th>
        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Montant tx</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Taux</th>
        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Commission</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($commissions as $c)
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3">
          <div class="font-semibold text-gray-900">{{ $c->etablissement->nom ?? '—' }}</div>
          <div class="text-xs text-gray-400">{{ $c->created_at->format('d/m/Y') }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-gray-700 font-mono text-xs">{{ $c->paiement->reference ?? '—' }}</div>
        </td>
        <td class="px-4 py-3 text-right text-gray-700">
          {{ number_format($c->montant_transaction, 0, ',', ' ') }} FCFA
        </td>
        <td class="px-4 py-3 text-center">
          <span class="text-xs font-semibold text-[#E8A020]">
            {{ number_format($c->taux * 100, 1) }}%
          </span>
        </td>
        <td class="px-4 py-3 text-right font-bold text-[#E8A020]">
          {{ number_format($c->montant_commission, 0, ',', ' ') }} FCFA
        </td>
        <td class="px-4 py-3">
          @if($c->statut === 'prelevee')
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-800">Prelevee</span>
          @else
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-yellow-100 text-yellow-800">A prelever</span>
          @endif
        </td>
        <td class="px-4 py-3">
          <div class="flex items-center justify-center gap-1.5">
            {{-- Modifier taux etablissement --}}
            <button onclick="ouvrirModifierTaux({{ $c->etablissement_id }}, {{ json_encode($c->etablissement->nom ?? '') }}, {{ $c->taux }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#FEF3DC] hover:bg-[#fde68a] text-[#E8A020] transition-colors" title="Modifier taux">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </button>
            {{-- Marquer prelevee --}}
            @if($c->statut === 'calculee')
            <button onclick="ouvrirPrelever({{ $c->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600 transition-colors" title="Marquer prelevee">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">Aucune commission trouvee.</td>
      </tr>
      @endforelse
    </tbody>
    </table>
  </div>
  @if($commissions->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $commissions->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
function ouvrirModifierTaux(etablissementId, nom, tauxActuel) {
    document.getElementById('modifier-taux-etab-nom').textContent = nom;
    document.getElementById('input-taux').value = tauxActuel;
    document.getElementById('taux-pct').textContent = (tauxActuel * 100).toFixed(1) + '%';
    document.getElementById('form-modifier-taux').action = '/admin-ep2026/commissions/' + etablissementId + '/modifier';
    epModal.open('modal-modifier-taux');
}
function ouvrirModifierTauxGlobal() {
    document.getElementById('modifier-taux-etab-nom').textContent = 'Taux global plateforme';
    document.getElementById('input-taux').value = {{ $tauxActuel }};
    document.getElementById('taux-pct').textContent = '{{ number_format($tauxActuel * 100, 1) }}%';
    document.getElementById('form-modifier-taux').action = '/admin-ep2026/commissions/global/modifier';
    epModal.open('modal-modifier-taux');
}
function ouvrirPrelever(id) {
    document.getElementById('form-prelever').action = '/admin-ep2026/commissions/' + id + '/prelever';
    epModal.open('modal-prelever');
}
// Mise a jour pourcentage en temps reel
document.getElementById('input-taux').addEventListener('input', function() {
    document.getElementById('taux-pct').textContent = (parseFloat(this.value || 0) * 100).toFixed(1) + '%';
});
</script>
@endpush
