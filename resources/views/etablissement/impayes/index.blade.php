@extends('layouts.etablissement')

@section('title', __('etablissement.impaye'))

@section('content')

<script>
    window.EP_LANG = window.EP_LANG || {};
    window.EP_LANG.confirm_relance_sms = {!! json_encode(__('etablissement.confirm_relance_sms')) !!};
</script>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
        <div style="font-size:17px;font-weight:700;">{{ __('etablissement.dossiers_impayes') }}</div>
        <div style="font-size:12px;color:#888;">
            {{ __('etablissement.dossiers_attente', ['count' => $fraisImpayes->total()]) }}
        </div>
    </div>
    <form method="POST" action="{{ route('etablissement.impayes.relancer') }}">
        @csrf
        <button type="submit" class="btn-p" style="width:auto;"
                onclick="return confirm(window.EP_LANG.confirm_relance_sms)">
            📱 {{ __('etablissement.relancer_tous_sms') }}
        </button>
    </form>
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
        <div class="kval" style="color:var(--ep-gold);">{{ $tauxRecouvrement ?? 0 }}%</div>
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
                                           border-radius:20px;cursor:pointer;"
                                    title="{{ __('etablissement.sms_relance_title') }}">
                                📱 SMS
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
