@extends('layouts.admin')
@section('title', 'Logs securite')

@push('modals')
{{-- MODAL DETAIL LOG --}}
<div id="modal-detail-log" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>Detail du log</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-log')">x</button>
    </div>
    <div id="modal-detail-log-content" class="ep-modal-body"></div>
  </div>
</div>
@endpush

@section('content')

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">Logs de securite</h1>
    <p class="text-sm text-gray-500 mt-0.5">Audit complet — Conforme COBAC/BEAC</p>
  </div>
  <a href="{{ route('admin.logs.index', array_merge(request()->query(), ['export'=>1])) }}"
     style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;font-weight:500;color:#444;text-decoration:none;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Exporter CSV
  </a>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#E0F5EE] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-gray-900">{{ $stats['total_jour'] }}</div>
      <div class="text-xs text-gray-400">Evenements aujourd'hui</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-red-700">{{ $stats['critiques'] }}</div>
      <div class="text-xs text-gray-400">Critiques aujourd'hui</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['warnings'] }}</div>
      <div class="text-xs text-gray-400">Warnings aujourd'hui</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-blue-700">{{ $stats['connexions'] }}</div>
      <div class="text-xs text-gray-400">Connexions aujourd'hui</div>
    </div>
  </div>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.logs.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Action, detail, IP..."
           class="flex-1 min-w-50 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <select name="niveau" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]">
      <option value="">Tous niveaux</option>
      <option value="INFO"     {{ request('niveau')==='INFO'     ? 'selected' : '' }}>INFO</option>
      <option value="WARNING"  {{ request('niveau')==='WARNING'  ? 'selected' : '' }}>WARNING</option>
      <option value="CRITICAL" {{ request('niveau')==='CRITICAL' ? 'selected' : '' }}>CRITICAL</option>
    </select>
    <input type="date" name="date_debut" value="{{ request('date_debut') }}"
           class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <input type="date" name="date_fin" value="{{ request('date_fin') }}"
           class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
    <button type="submit" class="bg-[#0D9E75] hover:bg-[#0A8562] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','niveau','date_debut','date_fin']))
    <a href="{{ route('admin.logs.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">Reinitialiser</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div class="overflow-x-auto">
    <table class="min-w-255 w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Niveau</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">IP</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Detail</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($logs as $log)
      @php
        $ns = match($log->niveau) {
          'CRITICAL' => 'background:#fee2e2;color:#b91c1c;',
          'WARNING'  => 'background:#fef9c3;color:#ca8a04;',
          'INFO'     => 'background:#dcfce7;color:#166534;',
          default    => 'background:#f3f4f6;color:#555;',
        };
      @endphp
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
          {{ $log->created_at->format('d/m/Y H:i:s') }}
        </td>
        <td class="px-4 py-3">
          <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;{{ $ns }}">
            {{ $log->niveau }}
          </span>
        </td>
        <td class="px-4 py-3">
          <div class="font-mono text-xs font-semibold text-gray-700">{{ $log->action }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-xs text-gray-500 truncate max-w-62.5">{{ $log->detail ?? '—' }}</div>
        </td>
        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $log->ip_address ?? '—' }}</td>
        <td class="px-4 py-3 text-center">
          <button onclick="ouvrirDetailLog({{ $log->id }})"
                  class="w-7 h-7 flex items-center justify-center rounded-lg bg-[#E0F5EE] hover:bg-[#c4eadb] text-[#0D9E75] transition-colors mx-auto" title="Detail">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Aucun log trouve.</td>
      </tr>
      @endforelse
    </tbody>
    </table>
  </div>
  @if($logs->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $logs->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
function ouvrirDetailLog(id) {
    const content = document.getElementById('modal-detail-log-content');
    content.innerHTML = '<div style="text-align:center;padding:30px 0;"><div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div></div>';
    epModal.open('modal-detail-log');
    fetch('/admin-ep2026/logs-securite/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p style="text-align:center;color:#dc2626;padding:20px;">Erreur de chargement.</p>'; });
}
</script>
@endpush
