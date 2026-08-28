@extends('layouts.payeur')
@section('title', __('payeur.mes_enfants_titre'))

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
  <div>
    <div style="font-size:18px;font-weight:700;">{{ __('payeur.mes_enfants') }}</div>
    <div style="font-size:13px;color:#888;">
      {{ __('payeur.n_enfants_suivis', ['count' => $apprenants->count()]) }}
      @if(Auth::user()->ville) · {{ Auth::user()->ville }} @endif
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <button onclick="epModal.open('modal-rattacher')" class="btn-o" style="width:auto;padding:9px 16px;font-size:12px;">
      + {{ __('payeur.rattacher_un_enfant') }}
    </button>
    @if($premierFraisImpaye)
      <a href="{{ route('payeur.paiement.show', $premierFraisImpaye) }}" class="btn-p" style="width:auto;">
        {{ __('payeur.payer_maintenant') }}
      </a>
    @endif
  </div>
</div>

{{-- ── F13 : Mes enfants (multi-enfants) ── --}}
@if($apprenants->isEmpty())
  <div class="epcard" style="text-align:center;color:#999;padding:40px 0;margin-bottom:18px;">
    <div style="font-size:32px;margin-bottom:12px;">👨‍👧‍👦</div>
    <div style="font-size:14px;font-weight:600;margin-bottom:6px;">{{ __('payeur.aucun_enfant_rattache_compte') }}</div>
    <div style="font-size:12px;color:#aaa;margin-bottom:16px;">{{ __('payeur.rattachez_premier_enfant') }}</div>
    <button onclick="epModal.open('modal-rattacher')" class="btn-p" style="width:auto;">
      {{ __('payeur.rattacher_un_enfant') }}
    </button>
  </div>
@else
  <div class="g2" style="margin-bottom:18px;">
    @foreach($apprenants as $apprenant)
      @php
        $totalA  = $apprenant->frais->sum('montant_total');
        $payeA   = $apprenant->frais->sum('montant_paye');
        $resteA  = $totalA - $payeA;
        $pctA    = $totalA > 0 ? round(($payeA / $totalA) * 100) : 0;
        $statutA = $totalA <= 0 ? 'aucun' : ($resteA <= 0 ? 'regle' : ($payeA > 0 ? 'partiel' : 'impaye'));
        $premierImpayeA = $apprenant->frais->first(fn($f) => $f->statut !== 'regle');
      @endphp
      <div class="epcard" style="border-left:3px solid {{ match($statutA) {
          'regle' => 'var(--ep-teal)', 'partiel' => 'var(--ep-gold)', 'impaye' => 'var(--ep-red)', 'aucun' => 'var(--ep-blue-lt)', default => '#ddd',
      } }};">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
          <div>
            <div style="font-size:15px;font-weight:700;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</div>
            <div style="font-size:11px;color:#888;">
              {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
            </div>
          </div>
          <span class="pill {{ match($statutA) { 'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa' } }}">
            {{ match($statutA) { 'regle' => __('payeur.statut_regle'), 'partiel' => __('payeur.statut_partiel'), 'impaye' => __('payeur.statut_impaye'), default => $statutA } }}
          </span>
        </div>

        {{-- Frais ventilés par catégorie --}}
        @forelse($apprenant->frais as $frais)
          @php $resteF = $frais->montant_total - $frais->montant_paye; @endphp
          <div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f5f5f5;">
            <span style="color:#666;">{{ $frais->categorieFrais->nom ?? __('payeur.frais') }}</span>
            <span style="font-weight:600;color:{{ $resteF > 0 ? 'var(--ep-red)' : 'var(--ep-teal)' }};">
              {{ $resteF > 0 ? __('payeur.reste_fcfa', ['montant' => number_format($resteF,0,',',' ')]) : '✓ '. __('payeur.statut_regle') }}
            </span>
          </div>
        @empty
          <div style="font-size:12px;color:#aaa;padding:4px 0;">{{ __('payeur.aucun_frais_enregistre') }}</div>
        @endforelse

        @if($resteA > 0)
          <div class="prog" style="margin-top:10px;margin-bottom:4px;">
            <div class="pfill" style="width:{{ $pctA }}%;"></div>
          </div>
          <div style="font-size:10px;color:#888;margin-bottom:10px;">{{ __('payeur.pct_regle', ['pct' => $pctA]) }}</div>
        @else
          <div style="font-size:12px;color:var(--ep-teal);font-weight:600;margin-top:10px;margin-bottom:10px;">
            ✓ {{ __('payeur.tous_frais_regles') }}
          </div>
        @endif

        <div style="display:flex;gap:6px;">
          @if($premierImpayeA)
            <a href="{{ route('payeur.paiement.show', $premierImpayeA) }}"
               class="{{ $statutA === 'impaye' ? 'btn-r' : 'btn-p' }}"
               style="flex:1;text-align:center;padding:8px;font-size:12px;display:block;">
              {{ $statutA === 'impaye' ? __('payeur.payer').' →' : __('payeur.continuer').' →' }}
            </a>
          @endif
          <a href="{{ route('payeur.frais.apprenant', $apprenant) }}"
             class="btn-o" style="flex:1;text-align:center;padding:8px;font-size:12px;">
            {{ __('payeur.detail') }} →
          </a>
        </div>
      </div>
    @endforeach
  </div>
@endif

@endsection

@push('modals')
@include('payeur.partials.modal-rattacher')
@endpush

@push('scripts')
@include('payeur.partials.modal-rattacher-scripts')
@endpush
