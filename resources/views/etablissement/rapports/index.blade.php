@extends('layouts.etablissement')

@section('title', __('etablissement.rapports_financiers'))

@section('content')

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.rapports_financiers') }}</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">{{ __('etablissement.rapports_sous_titre', ['annee' => $anneeScolaire ?? '2025-2026']) }}</div>

    {{-- ── KPIs annuels ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-teal);">{{ number_format($totalEncaisseAnnee ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('etablissement.kpi_encaisse_annee') }}</div>
        </div>
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-red);">{{ number_format($totalImpayeAnnee ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('etablissement.kpi_impaye_annee') }}</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $tauxRecouvrement ?? 0 }}%</div>
            <div class="klbl">{{ __('etablissement.taux_recouvrement') }}</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ $nbApprenants ?? 0 }}</div>
            <div class="klbl">{{ __('etablissement.apprenants_suivis') }}</div>
        </div>
    </div>

    <div class="g2" style="margin-bottom:18px;">

        {{-- ── Répartition par moyen de paiement ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;">{{ __('etablissement.repartition_paiements') }}</div>
            @forelse (($repartitionMoyens ?? []) as $moyen)
                <div class="row">
                    <div style="display:flex;align-items:center;gap:9px;font-size:13px;">
                        <span class="dot" style="background:{{ match($moyen['mode']) {
                            'mtn_momo' => '#FFCC00', 'orange_money' => '#FF6600', 'carte' => '#185FA5', default => '#999',
                        } }};"></span>
                        {{ match($moyen['mode']) {
                            'mtn_momo' => __('etablissement.mtn_momo'), 'orange_money' => __('etablissement.orange_money'), 'carte' => __('etablissement.mt_bancaire'), default => $moyen['mode'],
                        } }}
                    </div>
                    <strong>{{ $moyen['pourcentage'] }}%</strong>
                </div>
            @empty
                <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">{{ __('etablissement.aucune_donnee') }}</div>
            @endforelse
        </div>

        {{-- ── Répartition par classe ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;">{{ __('etablissement.recouvrement_classe') }}</div>
            @forelse (($repartitionClasses ?? []) as $classe)
                <div class="row">
                    <div>
                        <div style="font-size:13px;font-weight:600;">{{ $classe['nom'] }}</div>
                        <div style="font-size:10px;color:#888;">{{ __('etablissement.nb_eleves_suffix', ['count' => $classe['nb_apprenants']]) }}</div>
                    </div>
                    <span class="pill {{ $classe['taux'] >= 80 ? 'pg' : ($classe['taux'] >= 50 ? 'pa' : 'pr') }}">
                        {{ $classe['taux'] }}%
                    </span>
                </div>
            @empty
                <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">{{ __('etablissement.aucune_donnee') }}</div>
            @endforelse
        </div>
    </div>

    {{-- ── Export ── --}}
    <div class="epcard">
        <div style="font-size:14px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.exporter_donnees') }}</div>
        <div style="font-size:12px;color:#888;margin-bottom:14px;">{{ __('etablissement.exporter_hint') }}</div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('etablissement.rapports.export.excel') }}" class="btn-p" style="width:auto;">
                {{ __('etablissement.exporter_excel') }}
            </a>
            <a href="{{ route('etablissement.rapports.export.pdf') }}" class="btn-o" style="width:auto;">
                {{ __('etablissement.exporter_pdf') }}
            </a>
        </div>
    </div>

@endsection
