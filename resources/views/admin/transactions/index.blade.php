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

<div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="ep-ico vert ep-ico-entete"><span class="material-symbols-outlined">credit_card</span></div>
    <div>
    <h3 style="margin:0;">{{ __('admin.supervision_transactions') }}</h3>
    <p class="text-sm text-gray-500 mt-0.5 ep-sous-titre" style="margin-top:2px;">{{ __('admin.toutes_ecoles_temps_reel') }}</p>
    </div>
  </div>
  <a href="{{ route('admin.transactions.index', array_merge(request()->query(), ['export'=>1])) }}"
     style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;font-weight:500;color:#444;text-decoration:none;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    {{ __('admin.exporter_csv') }}
  </a>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="kpi ep-kpi">
    <div class="ep-ico vert"><span class="material-symbols-outlined">payments</span></div>
    <div>
      <div class="kval" data-ep-count>{{ number_format($stats['total_mois'], 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('admin.fcfa_ce_mois') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico bleu"><span class="material-symbols-outlined">credit_card</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['nb_mois'] }}</div>
      <div class="klbl">{{ __('admin.validees_ce_mois') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico or"><span class="material-symbols-outlined">schedule</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['en_attente'] }}</div>
      <div class="klbl">{{ __('admin.en_attente') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico rouge"><span class="material-symbols-outlined">error</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['echecs'] }}</div>
      <div class="klbl">{{ __('admin.echecs_ce_mois') }}</div>
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
          <option value="annule"     {{ request('statut')==='annule'     ? 'selected' : '' }}>{{ __('admin.annule') }}</option>
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
        'rembourse'  => 'color:#1d4ed8;background:#dbeafe;',
        'annule'     => 'color:#6b7280;background:#f3f4f6;',
        default      => 'color:#555;background:#f3f4f6;',
      };
      $label = match($p->statut) {
        'valide'     => __('admin.valide'),
        'en_attente' => __('admin.en_attente'),
        'echoue'     => __('admin.echoue'),
        'rembourse'  => __('admin.rembourse'),
        'annule'     => __('admin.annule'),
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
