@extends('layouts.etablissement')

@section('title', 'Rapports')

@section('content')

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">Rapports financiers</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">Année {{ $anneeScolaire ?? '2025-2026' }} · Statistiques globales de l'établissement</div>

    {{-- ── KPIs annuels ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-teal);">{{ number_format($totalEncaisseAnnee ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA encaissés (année)</div>
        </div>
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-red);">{{ number_format($totalImpayeAnnee ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA impayés (année)</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $tauxRecouvrement ?? 0 }}%</div>
            <div class="klbl">Taux de recouvrement</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ $nbApprenants ?? 0 }}</div>
            <div class="klbl">Apprenants suivis</div>
        </div>
    </div>

    <div class="g2" style="margin-bottom:18px;">

        {{-- ── Répartition par moyen de paiement ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;">Répartition des paiements</div>
            @forelse (($repartitionMoyens ?? []) as $moyen)
                <div class="row">
                    <div style="display:flex;align-items:center;gap:9px;font-size:13px;">
                        <span class="dot" style="background:{{ match($moyen['mode']) {
                            'mtn_momo' => '#FFCC00', 'orange_money' => '#FF6600', 'carte' => '#185FA5', default => '#999',
                        } }};"></span>
                        {{ match($moyen['mode']) {
                            'mtn_momo' => 'MTN Mobile Money', 'orange_money' => 'Orange Money', 'carte' => 'Carte bancaire', default => $moyen['mode'],
                        } }}
                    </div>
                    <strong>{{ $moyen['pourcentage'] }}%</strong>
                </div>
            @empty
                <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">Aucune donnée disponible.</div>
            @endforelse
        </div>

        {{-- ── Répartition par classe ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:12px;">Recouvrement par classe</div>
            @forelse (($repartitionClasses ?? []) as $classe)
                <div class="row">
                    <div>
                        <div style="font-size:13px;font-weight:600;">{{ $classe['nom'] }}</div>
                        <div style="font-size:10px;color:#888;">{{ $classe['nb_apprenants'] }} élève(s)</div>
                    </div>
                    <span class="pill {{ $classe['taux'] >= 80 ? 'pg' : ($classe['taux'] >= 50 ? 'pa' : 'pr') }}">
                        {{ $classe['taux'] }}%
                    </span>
                </div>
            @empty
                <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">Aucune donnée disponible.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Export ── --}}
    <div class="epcard">
        <div style="font-size:14px;font-weight:700;margin-bottom:4px;">Exporter les données</div>
        <div style="font-size:12px;color:#888;margin-bottom:14px;">Téléchargez un rapport détaillé au format Excel ou PDF.</div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('etablissement.rapports.index', ['export' => 'excel']) }}" class="btn-p" style="width:auto;">
                Exporter en Excel
            </a>
            <a href="{{ route('etablissement.rapports.index', ['export' => 'pdf']) }}" class="btn-o" style="width:auto;">
                Exporter en PDF
            </a>
        </div>
    </div>

@endsection
