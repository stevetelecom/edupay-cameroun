@extends('layouts.etablissement')

@section('title', __('etablissement.impaye'))

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
        <div style="font-size:17px;font-weight:700;">{{ __('etablissement.dossiers_impayes') }}</div>
        <div style="font-size:12px;color:#888;">
            {{ __('etablissement.dossiers_attente', ['count' => $fraisImpayes->total()]) }}
        </div>
    </div>
    <button type="button" class="btn-p" style="width:auto;display:inline-flex;align-items:center;gap:6px;"
            onclick="epModal.open('modal-relancer-tous')">
            <span class="material-symbols-outlined" style="font-size:16px;color:#fff;">sms</span>
            {{ __('etablissement.relancer_tous_sms') }}
        </button>

</div>

{{-- KPIs --}}
<div class="g3" style="margin-bottom:16px;">
    <div class="kpi">
        <div class="kval" style="color:var(--ep-red);">
            {{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}
        </div>
        <div class="klbl">{{ __('etablissement.fcfa_total_impaye') }}</div>
    </div>
    <div class="kpi">
        <div class="kval">{{ $fraisImpayes->total() }}</div>
        <div class="klbl">{{ __('etablissement.dossiers_concernes') }}</div>
    </div>
    <div class="kpi">
        <div class="kval" style="color:var(--ep-gold);">{{ number_format($tauxRecouvrementDecimal ?? 0, 2, ',', '') }}%</div>
        <div class="klbl">{{ __('etablissement.taux_recouvrement') }}</div>
    </div>
</div>

{{-- Filtre --}}
<form method="GET" action="{{ route('etablissement.impayes.index') }}"
      class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;">
    <div style="flex:1;">
        <div class="lbl">{{ __('etablissement.filtrer_classe2') }}</div>
        <select name="classe" class="select" style="margin-bottom:0;">
            <option value="">{{ __('etablissement.toutes_classes') }}</option>
            @foreach($classes as $classe)
                <option value="{{ $classe }}" @selected(request('classe') === $classe)>
                    {{ $classe }}
                </option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">{{ __('etablissement.filtrer') }}</button>
    @if(request('classe'))
        <a href="{{ route('etablissement.impayes.index') }}"
           class="btn-o" style="width:auto;padding:10px 16px;">{{ __('etablissement.reinitialiser') }}</a>
    @endif
</form>

{{-- Tableau --}}
<div class="epcard" style="padding:0;overflow:hidden;">
    <table class="ep-table">
        <thead>
            <tr>
                <th>{{ __('etablissement.apprenant_col') }}</th>
                <th>{{ __('etablissement.classe') }}</th>
                <th>{{ __('etablissement.categorie') }}</th>
                <th>{{ __('etablissement.total') }}</th>
                <th>{{ __('etablissement.reste_a_payer') }}</th>
                <th>{{ __('etablissement.statut') }}</th>
                <th>{{ __('etablissement.echeance_col') }}</th>
                <th style="text-align:right;">{{ __('etablissement.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fraisImpayes as $frais)
            @php
                $reste = $frais->montant_total - $frais->montant_paye;
                $prochaine = $frais->categorieFrais
                    ?->echeanciers()
                    ->where('date_echeance', '>=', now())
                    ->orderBy('date_echeance')
                    ->first();
                $joursRestants = $prochaine
                    ? now()->diffInDays(\Carbon\Carbon::parse($prochaine->date_echeance), false)
                    : null;
            @endphp
            <tr>
                <td style="font-weight:600;">
                    {{ $frais->apprenant->nom }} {{ $frais->apprenant->prenom }}
                </td>
                <td>{{ $frais->apprenant->classe }}</td>
                <td>{{ $frais->categorieFrais->nom ?? '—' }}</td>
                <td>{{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA</td>
                <td style="color:var(--ep-red);font-weight:600;">
                    {{ number_format($reste, 0, ',', ' ') }} FCFA
                </td>
                <td>
                    <span class="pill {{ $frais->statut === 'partiel' ? 'pa' : 'pr' }}">
                        {{ $frais->statut === 'partiel' ? __('etablissement.partiel') : __('etablissement.impaye') }}
                    </span>
                </td>
                <td>
                    @if($prochaine)
                        <div style="font-size:12px;">
                            {{ \Carbon\Carbon::parse($prochaine->date_echeance)->format('d/m/Y') }}
                        </div>
                        @if($joursRestants !== null && $joursRestants <= 5 && $joursRestants >= 0)
                            <span class="pill pa" style="font-size:10px;">J‑{{ $joursRestants }}</span>
                        @elseif($joursRestants !== null && $joursRestants < 0)
                            <span class="pill pr" style="font-size:10px;">{{ __('etablissement.depassee') }}</span>
                        @endif
                    @else
                        <span style="color:#aaa;font-size:11px;">—</span>
                    @endif
                </td>
                <td style="text-align:right;">
                    <div style="display:flex;gap:6px;justify-content:flex-end;">
                        <a href="{{ route('etablissement.apprenants.show', $frais->apprenant) }}"
                           style="font-size:11px;color:var(--ep-teal);text-decoration:none;
                                  border:1px solid var(--ep-teal);padding:3px 10px;border-radius:20px;">
                            {{ __('etablissement.dossier') }}
                        </a>
                        <form method="POST"
                              action="{{ route('etablissement.impayes.relancer.apprenant', $frais->apprenant) }}">
                            @csrf
                            <button type="submit"
                                    style="font-size:11px;color:#888;background:none;
                                           border:1px solid #ddd;padding:3px 10px;
                                           border-radius:20px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"
                                    title="{{ __('etablissement.sms_relance_title') }}">
                                <span class="material-symbols-outlined" style="font-size:13px;color:#888;">sms</span> SMS
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align:center;color:#999;padding:30px 0;">
                    {{ __('etablissement.aucun_impaye') }}
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($fraisImpayes->hasPages())
<div style="margin-top:16px;">{{ $fraisImpayes->links() }}</div>
@endif

@endsection

@push('modals')
<div id="modal-relancer-tous" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3><span class="material-symbols-outlined" style="font-size:18px;vertical-align:-4px;">sms</span> {{ __('etablissement.relancer_tous_sms') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-relancer-tous')">×</button>
    </div>
    <div class="ep-modal-body" style="padding:16px 20px;">
      <div style="font-size:13px;color:#555;line-height:1.5;margin-bottom:14px;">
        {{ __('etablissement.relance_tous_desc') }}
      </div>
      <div style="display:flex;gap:12px;align-items:center;background:var(--ep-teal-lt);border-radius:var(--radius-md);padding:10px 14px;margin-bottom:16px;">
        <div style="flex:1;">
          <div style="font-size:20px;font-weight:700;color:#085041;">{{ $fraisImpayes->total() }}</div>
          <div style="font-size:11px;color:#0F6E56;">{{ __('etablissement.dossiers_concernes') }}</div>
        </div>
        <div style="flex:1;">
          <div style="font-size:20px;font-weight:700;color:var(--ep-red);">{{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}</div>
          <div style="font-size:11px;color:#9B2C2C;">{{ __('etablissement.fcfa_total_impaye') }}</div>
        </div>
      </div>
      <form method="POST" action="{{ route('etablissement.impayes.relancer') }}">
        @csrf
        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button type="button" class="btn-o" onclick="epModal.close('modal-relancer-tous')">
            {{ __('etablissement.annuler') }}
          </button>
          <button type="submit" class="btn-p" style="display:inline-flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:16px;color:#fff;">send</span>
            {{ __('etablissement.confirmer_envoyer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endpush
