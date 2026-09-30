@extends('layouts.admin')

@section('title', __('messages.tableau_de_bord'))

@section('content')

    {{-- En-tête de page --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900">{{ __('admin.kpis_globaux') }} — {{ $mois }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.toutes_ecoles_temps_reel_dash') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 border border-green-200 px-3 py-1.5 rounded-full font-medium">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block"></span>
                {{ __('admin.systeme_operationnel') }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-2 xl:grid-cols-6 gap-4 mb-6">

        {{-- Volume de transactions --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico vert">
                <span class="material-symbols-outlined">payments</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($volumeMois, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('admin.volume_mois') }} · FCFA</div>
                @if(!is_null($variationVolume ?? null))
                    <span class="ep-var {{ $variationVolume >= 0 ? 'haut' : 'bas' }}">
                        <span class="material-symbols-outlined">{{ $variationVolume >= 0 ? 'trending_up' : 'trending_down' }}</span>
                        {{ $variationVolume >= 0 ? '+' : '' }}{{ number_format($variationVolume, 1, ',', ' ') }} % / {{ __('admin.mois_precedent') }}
                    </span>
                @endif
            </div>
        </div>

        {{-- Commissions --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico or">
                <span class="material-symbols-outlined">trending_up</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($commissionsMois, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('messages.commissions') }} · FCFA</div>
            </div>
        </div>

        {{-- Établissements actifs --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico bleu">
                <span class="material-symbols-outlined">apartment</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ $etablissementsActifs }}</div>
                <div class="klbl">{{ __('admin.actifs_sur_plateforme') }}</div>
            </div>
        </div>

        {{-- Payeurs --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico purple">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($payeursTotaux, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('admin.payeurs_inscrits_plateforme') }}</div>
            </div>
        </div>

        {{-- Transactions --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico navy">
                <span class="material-symbols-outlined">credit_card</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($transactionsMois, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('admin.validees_ce_mois_dash') }}</div>
            </div>
        </div>

        {{-- Réclamations --}}
        <div class="kpi ep-kpi">
            <div class="ep-ico rouge">
                <span class="material-symbols-outlined">support_agent</span>
            </div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($reclamationsMois, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('admin.crees_ce_mois') }}</div>
            </div>
        </div>
    </div>

    {{-- ══ GRAPHIQUES — évolution mensuelle + répartition moyens (Chart.js) ══ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

        {{-- Barres + ligne : détail mensuel des montants encaissés et du taux --}}
        {{-- (fusion : remplace la courbe redondante et l'ancienne section pleine largeur) --}}
        <div class="bg-white rounded-xl p-5 xl:col-span-2 ep-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined" style="color:#E8A020;font-size:22px;">bar_chart</span>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">{{ __('admin.evolution_mensuelle_taux') }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('admin.calcul_taux') }}</p>
                    </div>
                </div>
            </div>
            <div style="height:300px;">
                <canvas id="chart-evolution-detail"></canvas>
            </div>
        </div>

        {{-- Donut : répartition par moyen de paiement --}}
        <div class="bg-white rounded-xl p-5 ep-shadow">
            <div class="ep-entete">
                <span class="material-symbols-outlined">donut_large</span>
                <h3>{{ __('admin.repartition_paiements') }}</h3>
            </div>
            <div style="height:260px;">
                <canvas id="chart-moyens"></canvas>
            </div>
            <div class="mt-4 space-y-2">
                @php
                    $moyensLegende = [
                        'mtn_momo'     => ['label' => 'MTN MoMo',      'couleur' => '#E8A020'],
                        'orange_money' => ['label' => 'Orange Money',  'couleur' => '#FF6600'],
                        'carte'        => ['label' => __('admin.carte_bancaire'), 'couleur' => '#1F6FB2'],
                    ];
                    $totalTxLegende = $repartitionMoyens->sum('total') ?: 1;
                @endphp
                @foreach ($moyensLegende as $key => $info)
                    @php
                        $rowL = $repartitionMoyens->get($key);
                        $pctL = $rowL ? round(($rowL->total / $totalTxLegende) * 100, 1) : 0;
                    @endphp
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:{{ $info['couleur'] }}"></span>
                            <span class="text-gray-600 font-medium">{{ $info['label'] }}</span>
                        </div>
                        <span class="font-bold text-gray-800">{{ $pctL }}% <span class="text-gray-400 font-medium ml-1">{{ number_format($rowL->volume ?? 0, 0, ',', ' ') }} FCFA</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ══ VISUELS D'ANALYSE : Venn payeurs/apprenants · pictogrammes statuts ·
         histogramme hebdomadaire (grille 3 colonnes, comme la maquette) ══ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

        {{-- 1. Diagramme de Venn SVG : couverture de la relation payeur ↔ apprenant --}}
        <div class="bg-white rounded-xl p-5 ep-shadow">
            <div class="ep-entete">
                <span class="material-symbols-outlined">join_inner</span>
                <h3>{{ __('admin.couverture_rattachement') }}</h3>
            </div>
            <p class="text-xs text-gray-500 mb-2">{{ __('admin.couverture_rattachement_sub') }}</p>
            @php
                // Proportions du Venn : rayons liés au volume de chaque population,
                // intersection au centre (rattachements réels)
                $vennPayeurs = max($payeursRattaches + $payeursSeuls, 1);
                $vennApprenants = max($apprenantsRattaches + $apprenantsSeuls, 1);
                $rP = 44 + 26 * min($vennPayeurs / max($vennPayeurs + $vennApprenants, 1), 1);
                $rA = 44 + 26 * min($vennApprenants / max($vennPayeurs + $vennApprenants, 1), 1);
            @endphp
            <div style="display:flex;justify-content:center;">
                <svg width="230" height="190" viewBox="0 0 230 190" role="img" aria-label="{{ __('admin.couverture_rattachement') }}">
                    {{-- Cercle payeurs (or) --}}
                    <circle cx="88" cy="85" r="{{ $rP }}" fill="rgba(232,160,32,.32)" stroke="#E8A020" stroke-width="2"/>
                    {{-- Cercle apprenants (bleu) --}}
                    <circle cx="142" cy="85" r="{{ $rA }}" fill="rgba(31,111,178,.30)" stroke="#1F6FB2" stroke-width="2"/>
                    {{-- Intersection : ellipse au centre, teinte teal --}}
                    <ellipse cx="115" cy="85" rx="{{ min($rP, $rA) * 0.62 }}" ry="{{ min($rP, $rA) * 0.9 }}" fill="rgba(13,158,117,.38)"/>
                    {{-- Libellés et valeurs : intersection décalée sous les cercles pour
                         éviter le chevauchement des petits cercles (données faibles) --}}
                    <text x="55" y="42" text-anchor="middle" font-size="11" font-weight="700" fill="#B4780E">{{ __('admin.payeurs') }}</text>
                    <text x="55" y="58" text-anchor="middle" font-size="15" font-weight="800" fill="#8B5E10">{{ $payeursSeuls }}</text>
                    <text x="175" y="42" text-anchor="middle" font-size="11" font-weight="700" fill="#1F6FB2">{{ __('admin.apprenants') }}</text>
                    <text x="175" y="58" text-anchor="middle" font-size="15" font-weight="800" fill="#123C66">{{ $apprenantsSeuls }}</text>
                    <text x="115" y="178" text-anchor="middle" font-size="10.5" font-weight="700" fill="#0D9E75">{{ __('admin.rattaches') }} : {{ $payeursRattaches }} ↔ {{ $apprenantsRattaches }}</text>
                </svg>
            </div>
            <div class="text-xs text-center text-gray-500 mt-1">
                {{ __('admin.venn_legend', ['p' => $payeursSeuls, 'a' => $apprenantsSeuls]) }}
            </div>
        </div>

        {{-- 2. Pictogrammes : 1 icône = 1 apprenant, par statut de paiement --}}
        <div class="bg-white rounded-xl p-5 ep-shadow">
            <div class="ep-entete">
                <span class="material-symbols-outlined">emoji_people</span>
                <h3>{{ __('admin.statuts_apprenants') }}</h3>
            </div>
            <p class="text-xs text-gray-500 mb-3">{{ __('admin.picto_legende') }}</p>
            @php
                $pictoConfig = [
                    'regle'   => ['ico' => 'sentiment_satisfied', 'couleur' => '#0D9E75', 'label' => __('admin.statut_regle')],
                    'partiel' => ['ico' => 'sentiment_neutral',  'couleur' => '#E8A020', 'label' => __('admin.statut_partiel')],
                    'impaye'  => ['ico' => 'sentiment_dissatisfied', 'couleur' => '#D94040', 'label' => __('admin.statut_impaye')],
                ];
            @endphp
            <div style="display:grid;gap:12px;">
                @foreach ($pictoConfig as $statut => $cfg)
                    @php $nb = (int) ($statutsApprenants[$statut] ?? 0); @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold" style="color:{{ $cfg['couleur'] }}">{{ $cfg['label'] }}</span>
                            <span class="font-bold text-gray-700">{{ $nb }}</span>
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:3px;">
                            {{-- 1 pictogramme = 1 apprenant (max 24 affichés, +N au-delà) --}}
                            @for ($i = 0; $i < min($nb, 24); $i++)
                                <span class="material-symbols-outlined" style="font-size:19px;color:{{ $cfg['couleur'] }};font-variation-settings:'FILL' 1;">{{ $cfg['ico'] }}</span>
                            @endfor
                            @if ($nb > 24)
                                <span class="text-[11px] font-bold" style="color:{{ $cfg['couleur'] }};align-self:center;">+{{ $nb - 24 }}</span>
                            @elseif ($nb === 0)
                                <span class="text-[11px] text-gray-300">—</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 3. Histogramme : volume encaissé sur les 7 derniers jours (Chart.js) --}}
        <div class="bg-white rounded-xl p-5 ep-shadow">
            <div class="ep-entete">
                <span class="material-symbols-outlined">calendar_view_week</span>
                <h3>{{ __('admin.volume_semaine') }}</h3>
            </div>
            <div style="height:240px;">
                <canvas id="chart-semaine"></canvas>
            </div>
        </div>
    </div>

    {{-- ── Derniers établissements inscrits (pleine largeur) ── --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined" style="color:#E8A020;font-size:20px;">apartment</span>
                    <h2 class="text-sm font-bold text-gray-900">{{ __('admin.derniers_etablissements_inscrits') }}</h2>
                </div>
                @if (Route::has('admin.etablissements.index'))
                <a href="{{ route('admin.etablissements.index') }}"
                   class="text-xs text-[#0D9E75] hover:underline font-medium">
                    {{ __('admin.voir_tout') }}
                </a>
                @endif
            </div>
            <div class="space-y-0">
                @forelse ($derniersEtablissements as $etablissement)
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100 last:border-b-0">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $etablissement->nom }}</div>
                            <div class="text-xs text-gray-400">{{ $etablissement->ville }} · {{ $etablissement->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            {{ $etablissement->statut === 'actif' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($etablissement->statut) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">{{ __('admin.aucun_etablissement_inscrit') }}</p>
                @endforelse
            </div>
        </div>

    {{-- ── Taux de recouvrement GLOBAL ── --}}
    <div class="bg-white border border-gray-200 rounded-xl p-5 mb-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined" style="color:#E8A020;font-size:22px;">donut_small</span>
                <div>
                    <h2 class="text-sm font-bold text-gray-900">{{ __('admin.taux_recouvrement_plateforme') }}</h2>
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.calcul_taux') }}</p>
                </div>
            </div>
            <div class="text-right">
                <div class="text-4xl font-bold text-[#0D9E75]">{{ number_format($tauxRecouvrementGlobal, 2, ',', '') }}%</div>
                <p class="text-xs text-gray-500 mt-1">{{ __('admin.taux_global') }}</p>
            </div>
        </div>
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500 bg-[#0D9E75]"
                 style="width:{{ min($tauxRecouvrementGlobal, 100) }}%">
            </div>
        </div>
    </div>

    {{-- ── Grille taux par région + par établissement ── --}}
    <div class="grid grid-cols-2 gap-5 mb-5">

        {{-- Taux par région --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="ep-entete">
                <span class="material-symbols-outlined">public</span>
                <h3>{{ __('admin.taux_par_region') }}</h3>
            </div>
            <div class="space-y-2.5 max-h-72 overflow-y-auto">
                @forelse ($tauxParRegion as $region)
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-800">{{ $region->region ?: __('admin.non_specifiee') }}</div>
                            <div class="text-xs text-gray-400">{{ $region->nb_etablissements }} {{ __('admin.etab_suffix') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-[#0D9E75]">{{ number_format($region->taux_recouvrement ?? 0, 2, ',', '') }}%</div>
                            <div class="text-xs text-gray-400">{{ number_format($region->montant_paye ?? 0, 0, ',', ' ') }} FCFA</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">{{ __('admin.dt_empty_table') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Top 10 établissements par taux de recouvrement --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="ep-entete">
                <span class="material-symbols-outlined">emoji_events</span>
                <h3>{{ __('admin.top_etablissements_taux') }}</h3>
            </div>
            <div class="space-y-2.5 max-h-72 overflow-y-auto">
                @forelse ($tauxParEtablissement as $index => $etab)
                    <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-gray-400 w-5">{{ $index + 1 }}.</span>
                                <div>
                                    <div class="text-sm font-semibold text-gray-800">{{ $etab->nom }}</div>
                                    <div class="text-xs text-gray-400">{{ $etab->ville ?? 'N/A' }} · {{ $etab->region ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold" style="color:{{ $etab->taux_recouvrement >= 80 ? '#0D9E75' : ($etab->taux_recouvrement >= 50 ? '#E8A020' : '#D94040') }}">
                                    {{ number_format($etab->taux_recouvrement ?? 0, 2, ',', '') }}%
                                </div>
                                <div class="text-xs text-gray-400">{{ number_format($etab->montant_paye ?? 0, 0, ',', ' ') }} FCFA</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">{{ __('admin.dt_empty_table') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Bandeau taux commission — composant ep-bandeau v2 --}}
    <div class="ep-bandeau attente" style="align-items:center;">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">percent</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-titre">{{ __('admin.taux_commission_configurable') }}</div>
            <div class="ep-bandeau-texte">
                {{-- Taux réel (composer AdminSidebarComposer) + détail des 2 composantes --}}
                <strong style="font-size:14px;color:#854F0B;">{{ number_format($tauxCommission * 100, 2, ',', '') }}%</strong>
                {{ __('messages.par_transaction') }}
                <span style="opacity:.8;">(AangaraaPay {{ number_format($tauxAangaraaPct, 2, ',', '') }}% + EduPay {{ number_format($margeEdupayPct, 2, ',', '') }}%)</span>
                · {{ __('admin.profil_std_cobac') }}
            </div>
        </div>
        @if (Route::has('admin.commissions.index'))
        <a href="{{ route('admin.commissions.index') }}"
           class="btn-p" style="width:auto;display:inline-flex;align-items:center;gap:6px;padding:9px 18px;font-size:12.5px;flex-shrink:0;">
            <span class="material-symbols-outlined" style="font-size:15px;">edit</span>
            {{ __('admin.modifier_taux') }}
        </a>
        @endif
    </div>@endsection

@push('scripts')
{{-- Chart.js via CDN --}}
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(function () {
    'use strict';

    // ── Construction (ou re-construction) de tous les graphiques.
    //    La fonction est rappelée à chaque changement de thème clair/sombre
    //    (événement « ep-themechange ») pour adapter grille, ticks et pointes. ──
    function monterGraphiques() {

        // Détruit les instances existantes avant re-création (bascule de thème)
        ['chart-evolution', 'chart-moyens', 'chart-evolution-detail', 'chart-semaine'].forEach(function (id) {
            var c = document.getElementById(id);
            if (c && window.Chart && Chart.getChart(c)) Chart.getChart(c).destroy();
        });

    // ── Données injectées depuis le contrôleur (JSON sécurisé) ──
    const evolution = @json($evolutionMensuelle);
    const repartition = @json($repartitionMoyens);

    // ── Palette EduPay ──
    const TEAL  = '#0D9E75';
    const NAVY  = '#0B2545';
    const GOLD  = '#E8A020';
    const BLUE  = '#1F6FB2';
    const RED   = '#FF6600';
    const GRIS  = '#5A6472';

    // Thème actuel : adapte les couleurs de lecture des graphiques
    const sombre = document.documentElement.getAttribute('data-theme') === 'dark';

    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = sombre ? '#8CA0B8' : GRIS;

    // ══ 1. Courbe : évolution mensuelle du taux (12 mois) ══
    const ctxEvo = document.getElementById('chart-evolution');
    if (ctxEvo) {
        new Chart(ctxEvo, {
            type: 'line',
            data: {
                labels: evolution.map(m => m.mois),
                datasets: [{
                    label: 'Taux de recouvrement (%)',
                    data: evolution.map(m => m.taux),
                    borderColor: TEAL,
                    backgroundColor: 'rgba(13,158,117,.12)',
                    fill: true,
                    tension: .4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: sombre ? '#0E1A2E' : '#fff',
                    pointBorderColor: TEAL,
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: NAVY,
                        padding: 12,
                        cornerRadius: 10,
                        titleFont: { weight: '700' },
                        callbacks: {
                            label: (ctx) => ' ' + ctx.parsed.y.toFixed(2) + ' %',
                            afterLabel: (ctx) => {
                                const m = evolution[ctx.dataIndex];
                                return ' ' + (m.montant_paye || 0).toLocaleString('fr-FR') + ' / ' + (m.montant_total || 0).toLocaleString('fr-FR') + ' FCFA';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: sombre ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => v + '%' }
                    }
                }
            }
        });
    }

    // ══ 2. Donut : répartition par moyen de paiement ══
    const ctxMoy = document.getElementById('chart-moyens');
    if (ctxMoy) {
        const moyensConfig = {
            'mtn_momo':     { label: 'MTN MoMo',     couleur: GOLD },
            'orange_money': { label: 'Orange Money', couleur: RED },
            'carte':        { label: 'Carte bancaire', couleur: BLUE }
        };
        const labels = [], data = [], couleurs = [];
        Object.keys(moyensConfig).forEach(k => {
            const r = repartition[k];
            if (r && r.total > 0) {
                labels.push(moyensConfig[k].label);
                data.push(r.total);
                couleurs.push(moyensConfig[k].couleur);
            }
        });
        // Cas où aucune transaction ce mois : donut vide propre
        if (data.length === 0) {
            labels.push('Aucune transaction');
            data.push(1);
            couleurs.push(sombre ? '#1C2C45' : '#E4E9EE');
        }
        new Chart(ctxMoy, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: couleurs,
                    borderWidth: 3,
                    borderColor: sombre ? '#0E1A2E' : '#fff',
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                    tooltip: {
                        backgroundColor: NAVY,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ' : ' + pct + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    // ══ 3. Barres + ligne : détail mensuel montants et taux ══
    const ctxDet = document.getElementById('chart-evolution-detail');
    if (ctxDet) {
        new Chart(ctxDet, {
            type: 'bar',
            data: {
                labels: evolution.map(m => m.mois),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Montant payé (FCFA)',
                        data: evolution.map(m => m.montant_paye),
                        backgroundColor: sombre ? 'rgba(13,158,117,.85)' : 'rgba(13,158,117,.75)',
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Taux (%)',
                        data: evolution.map(m => m.taux),
                        borderColor: GOLD,
                        backgroundColor: GOLD,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        tension: .35,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
                    tooltip: {
                        backgroundColor: NAVY,
                        padding: 12,
                        cornerRadius: 10
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45 } },
                    y: {
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: sombre ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => (v >= 1000000 ? (v/1000000) + 'M' : (v >= 1000 ? (v/1000) + 'k' : v)) }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: { drawOnChartArea: false },
                        ticks: { callback: (v) => v + '%', color: GOLD }
                    }
                }
            }
        });
    }

    // ══ 4. Histogramme : volume encaissé des 7 derniers jours ══
    const ctxSem = document.getElementById('chart-semaine');
    if (ctxSem) {
        const semaine = @json($volumeParJour);
        new Chart(ctxSem, {
            type: 'bar',
            data: {
                labels: semaine.map(j => j.jour),
                datasets: [{
                    label: 'Volume (FCFA)',
                    data: semaine.map(j => j.volume),
                    backgroundColor: semaine.map(j => j.volume > 0 ? 'rgba(13,158,117,.80)' : (sombre ? 'rgba(255,255,255,.10)' : 'rgba(0,0,0,.07)')),
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: NAVY,
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: { label: (c) => ' ' + c.parsed.y.toLocaleString('fr-FR') + ' FCFA' }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: sombre ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => (v >= 1000000 ? (v/1000000) + 'M' : (v >= 1000 ? (v/1000) + 'k' : v)) }
                    }
                }
            }
        });
    }

    } // ── ferme monterGraphiques() : cette accolade manquait, le bloc entier
      //    mourait en SyntaxError et AUCUN graphique ne se montait

    // Premier rendu, puis re-rendu en fondu à chaque bascule clair/sombre
    monterGraphiques();
    var epTimerTheme = null;
    document.addEventListener('ep-themechange', function () {
        clearTimeout(epTimerTheme);
        epTimerTheme = setTimeout(monterGraphiques, 350);
    });
})();
</script>
@endpush