@extends('layouts.admin')
@section('title', 'Reclamations')

@push('modals')
{{-- MODAL DETAIL --}}
<div id="modal-detail-rec" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>Detail de la reclamation</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-rec')">x</button>
    </div>
    <div id="modal-detail-rec-content" class="ep-modal-body"></div>
  </div>
</div>

{{-- MODAL REPONDRE --}}
<div id="modal-repondre" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>Repondre a la reclamation</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-repondre')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="margin-bottom:12px;">
        <div style="font-size:12px;color:#888;margin-bottom:2px;">Ticket</div>
        <div style="font-size:13px;font-weight:600;color:#111;" id="repondre-ticket"></div>
      </div>
      <form id="form-repondre" method="POST">
        @csrf @method('PATCH')
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">Reponse *</label>
          <textarea name="reponse_admin" rows="5" required
                    placeholder="Votre reponse au client..."
                    style="width:100%;padding:10px 12px;font-size:13px;border:1px solid #ddd;border-radius:8px;resize:vertical;box-sizing:border-box;outline:none;"></textarea>
        </div>
        <div style="margin-bottom:16px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">Nouveau statut *</label>
          <select name="statut" required
                  style="width:100%;padding:10px 12px;font-size:13px;border:1px solid #ddd;border-radius:8px;outline:none;">
            <option value="en_cours">En cours de traitement</option>
            <option value="resolu">Resolu</option>
            <option value="rejete">Rejete</option>
          </select>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-repondre')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Annuler
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            Envoyer la reponse
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
    <h1 class="text-xl font-bold text-gray-900">
      Reclamations
      @if($stats['ouvertes'] > 0)
      <span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#dc2626;color:#fff;border-radius:50%;font-size:11px;font-weight:700;margin-left:6px;">
        {{ $stats['ouvertes'] }}
      </span>
      @endif
    </h1>
    <p class="text-sm text-gray-500 mt-0.5">Gestion des reclamations clients</p>
  </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-blue-700">{{ $stats['ouvertes'] }}</div>
      <div class="text-xs text-gray-400">Ouvertes</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['en_cours'] }}</div>
      <div class="text-xs text-gray-400">En cours</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-green-700">{{ $stats['resolues'] }}</div>
      <div class="text-xs text-gray-400">Resolues</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-red-700">{{ $stats['rejetees'] }}</div>
      <div class="text-xs text-gray-400">Rejetees</div>
    </div>
  </div>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.reclamations.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Ticket, sujet, client..."
           class="flex-1 min-w-50 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">Tous les statuts</option>
      <option value="ouvert"   {{ request('statut')==='ouvert'   ? 'selected' : '' }}>Ouvert</option>
      <option value="en_cours" {{ request('statut')==='en_cours' ? 'selected' : '' }}>En cours</option>
      <option value="resolu"   {{ request('statut')==='resolu'   ? 'selected' : '' }}>Resolu</option>
      <option value="rejete"   {{ request('statut')==='rejete'   ? 'selected' : '' }}>Rejete</option>
    </select>
    <button type="submit" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','statut']))
    <a href="{{ route('admin.reclamations.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">Reinitialiser</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Ticket</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Client</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Sujet</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Statut</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($reclamations as $r)
      @php
        $sc = match($r->statut) {
          'ouvert'   => 'bg-blue-100 text-blue-800',
          'en_cours' => 'bg-yellow-100 text-yellow-800',
          'resolu'   => 'bg-green-100 text-green-800',
          'rejete'   => 'bg-red-100 text-red-800',
          default    => 'bg-gray-100 text-gray-600',
        };
        $label = match($r->statut) {
          'ouvert'   => 'Ouvert',
          'en_cours' => 'En cours',
          'resolu'   => 'Resolu',
          'rejete'   => 'Rejete',
          default    => $r->statut,
        };
      @endphp
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3">
          <div class="font-mono text-xs font-semibold text-gray-700">{{ $r->numero_ticket }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="font-semibold text-gray-900">{{ $r->user->prenom ?? '' }} {{ $r->user->nom ?? '—' }}</div>
          <div class="text-xs text-gray-400">{{ $r->user->email ?? '' }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-gray-700 truncate max-w-50">{{ $r->sujet }}</div>
        </td>
        <td class="px-4 py-3">
          <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sc }}">{{ $label }}</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-500">{{ $r->created_at->format('d/m/Y') }}</td>
        <td class="px-4 py-3">
          <div class="flex items-center justify-center gap-1.5">
            {{-- Voir --}}
            <button onclick="ouvrirDetailRec({{ $r->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#E0F5EE] hover:bg-[#c4eadb] text-[#0D9E75] transition-colors" title="Detail">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            {{-- Repondre --}}
            @if($r->statut !== 'resolu' && $r->statut !== 'rejete')
            <button onclick="ouvrirRepondre({{ $r->id }}, {{ json_encode($r->numero_ticket) }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 transition-colors" title="Repondre">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            </button>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Aucune reclamation trouvee.</td>
      </tr>
      @endforelse
    </tbody>
  </table>
  @if($reclamations->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $reclamations->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
function ouvrirDetailRec(id) {
    const content = document.getElementById('modal-detail-rec-content');
    content.innerHTML = '<div style="text-align:center;padding:30px 0;"><div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div></div>';
    epModal.open('modal-detail-rec');
    fetch('/admin-ep2026/reclamations/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p style="text-align:center;color:#dc2626;padding:20px;">Erreur de chargement.</p>'; });
}
function ouvrirRepondre(id, ticket) {
    document.getElementById('repondre-ticket').textContent = ticket;
    document.getElementById('form-repondre').action = '/admin-ep2026/reclamations/' + id + '/repondre';
    epModal.open('modal-repondre');
}
</script>
@endpush
