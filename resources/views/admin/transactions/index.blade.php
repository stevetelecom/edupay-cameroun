@extends('layouts.admin')
@section('title', __('messages.transactions'))

@push('modals')
{{-- MODAL DETAIL TRANSACTION --}}
<div id="modal-detail-tx" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3>{{ __('admin.detail_transaction') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-tx')">x</button>
    </div>
    <div id="modal-detail-tx-content" class="ep-modal-body">
      <div style="text-align:center;padding:30px 0;">
        <div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div>
      </div>
    </div>
  </div>
</div>
@endpush

@section('content')

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">{{ __('admin.supervision_transactions') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.toutes_ecoles_temps_reel') }}</p>
  </div>
  <a href="{{ route('admin.transactions.index', array_merge(request()->query(), ['export'=>1])) }}"
     style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;font-weight:500;color:#444;text-decoration:none;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    {{ __('admin.exporter_csv') }}
  </a>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-[#E0F5EE] rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-[#0D9E75]">{{ number_format($stats['total_mois'], 0, ',', ' ') }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.fcfa_ce_mois') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-gray-900">{{ $stats['nb_mois'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.validees_ce_mois') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-yellow-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-yellow-700">{{ $stats['en_attente'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.en_attente') }}</div>
    </div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4 flex items-center gap-3">
    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center shrink-0">
      <svg class="w-4 h-4 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
    </div>
    <div>
      <div class="text-xl font-bold text-red-700">{{ $stats['echecs'] }}</div>
      <div class="text-xs text-gray-400">{{ __('admin.echecs_ce_mois') }}</div>
    </div>
  </div>
</div>

{{-- Onglets operateur --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div style="display:flex;align-items:center;gap:4px;padding:12px 16px;border-bottom:1px solid #f0f0f0;flex-wrap:wrap;">
    @php
      $operateurs = ['' => __('admin.toutes'), 'MTN_Cameroon' => 'MTN MoMo', 'Orange_Cameroon' => 'Orange Money'];
    @endphp
    @foreach($operateurs as $val => $label)
    <a href="{{ route('admin.transactions.index', array_merge(request()->except('operateur','page'), $val ? ['operateur'=>$val] : [])) }}"
       style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:500;text-decoration:none;transition:all .15s;
              {{ request('operateur')===$val ? 'background:#0D9E75;color:#fff;' : 'background:#f5f5f5;color:#555;' }}">
      {{ $label }}
    </a>
    @endforeach

    {{-- Filtre statut --}}
    <div style="margin-left:auto;">
      <form method="GET" action="{{ route('admin.transactions.index') }}" style="display:flex;gap:8px;align-items:center;">
        @if(request('operateur'))
        <input type="hidden" name="operateur" value="{{ request('operateur') }}">
        @endif
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="{{ __('admin.ref_ou_tel') }}"
               style="padding:6px 12px;font-size:12px;border:1px solid #ddd;border-radius:8px;outline:none;width:180px;" />
        <select name="statut" style="padding:6px 10px;font-size:12px;border:1px solid #ddd;border-radius:8px;outline:none;">
          <option value="">{{ __('admin.tous_statuts') }}</option>
          <option value="valide"     {{ request('statut')==='valide'     ? 'selected' : '' }}>{{ __('admin.valide') }}</option>
          <option value="en_attente" {{ request('statut')==='en_attente' ? 'selected' : '' }}>{{ __('admin.en_attente') }}</option>
          <option value="echoue"     {{ request('statut')==='echoue'     ? 'selected' : '' }}>{{ __('admin.echoue') }}</option>
        </select>
        <button type="submit" style="padding:6px 14px;font-size:12px;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
          Filtrer
        </button>
      </form>
    </div>
  </div>

  {{-- Liste transactions --}}
  <div>
    @forelse($paiements as $p)
    @php
      $ecole = $p->fraisApprenant?->categorieFrais?->etablissement?->nom ?? '—';
      $sc = match($p->statut) {
        'valide'     => 'color:#16a34a;background:#dcfce7;',
        'en_attente' => 'color:#ca8a04;background:#fef9c3;',
        'echoue'     => 'color:#dc2626;background:#fee2e2;',
        default      => 'color:#555;background:#f3f4f6;',
      };
      $label = match($p->statut) {
        'valide'     => __('admin.valide'),
        'en_attente' => __('admin.en_attente'),
        'echoue'     => __('admin.echoue'),
        default      => ucfirst($p->statut),
      };
      $opColor = str_contains($p->operateur ?? '', 'MTN') ? '#FFCC00' : '#FF6600';
    @endphp
    <div onclick="ouvrirDetailTx({{ $p->id }})"
         style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid #f5f5f5;cursor:pointer;transition:background .15s;"
         onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:8px;height:8px;border-radius:50%;background:{{ $opColor }};shrink:0;"></div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">
            {{ $p->reference }} · {{ $ecole }}
          </div>
          <div style="font-size:11px;color:#888;margin-top:2px;">
            {{ $p->operateur ?? '—' }} · {{ $p->created_at->diffForHumans() }}
          </div>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-size:14px;font-weight:700;color:#0D9E75;">
          {{ number_format($p->montant, 0, ',', ' ') }} FCFA
        </div>
        <span style="font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px;{{ $sc }}">
          {{ $label }}
        </span>
      </div>
    </div>
    @empty
    <div style="text-align:center;color:#999;font-size:13px;padding:40px 0;">
      {{ __('admin.aucune_transaction') }}
    </div>
    @endforelse
  </div>

  {{-- Pagination --}}
  @if($paiements->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $paiements->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
function ouvrirDetailTx(id) {
    const content = document.getElementById('modal-detail-tx-content');
    content.innerHTML = '<div style="text-align:center;padding:30px 0;"><div style="width:24px;height:24px;border:2px solid #0D9E75;border-top-color:transparent;border-radius:50%;animation:spin .7s linear infinite;margin:auto;"></div></div>';
    epModal.open('modal-detail-tx');
    fetch('/admin-ep2026/transactions/' + id, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<p style="text-align:center;color:#dc2626;padding:20px;">' + @json(__('admin.erreur_chargement')) + '</p>'; });
}
</script>
@endpush
