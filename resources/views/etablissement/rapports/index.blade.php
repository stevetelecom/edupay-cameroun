@extends('layouts.etablissement')

@section('title', __('etablissement.rapports_financiers'))

@section('content')

    {{-- En-tête de page style tableau de bord (pastille dorée + Poppins) --}}
    <div class="ep-entete" style="margin-bottom:2px;">
        <span class="material-symbols-outlined">monitoring</span>
        <h3>{{ __('etablissement.rapports_financiers') }}</h3>
    </div>
    <div class="ep-sous-titre" style="margin-bottom:18px;">{{ __('etablissement.rapports_sous_titre', ['annee' => $anneeScolaire ?? \App\Support\AnneeScolaire::active()]) }}</div>

    {{-- ── KPIs annuels — pastilles + compteurs animés ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi ep-kpi">
            <div class="ep-ico vert"><span class="material-symbols-outlined">savings</span></div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($totalEncaisseAnnee ?? 0, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('etablissement.kpi_encaisse_annee') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico rouge"><span class="material-symbols-outlined">error</span></div>
            <div>
                <div class="kval" data-ep-count>{{ number_format($totalImpayeAnnee ?? 0, 0, ',', ' ') }}</div>
                <div class="klbl">{{ __('etablissement.kpi_impaye_annee') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico bleu"><span class="material-symbols-outlined">track_changes</span></div>
            <div>
                <div class="kval" data-ep-count>{{ $tauxRecouvrement ?? 0 }}%</div>
                <div class="klbl">{{ __('etablissement.taux_recouvrement') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico or"><span class="material-symbols-outlined">school</span></div>
            <div>
                <div class="kval" data-ep-count>{{ $nbApprenants ?? 0 }}</div>
                <div class="klbl">{{ __('etablissement.apprenants_suivis') }}</div>
            </div>
        </div>
    </div>

    <div class="g2" style="margin-bottom:18px;">

        {{-- ── Diagramme de Venn : répartition des paiements par moyen ──
             Chaque cercle = apprenants ayant payé UNIQUEMENT avec ce moyen ;
             le cœur doré = apprenants multi-moyens (chevauchement). --}}
        <div class="epcard">
            <div class="ep-entete" style="margin-bottom:6px;">
                <span class="material-symbols-outlined">join_inner</span>
                <h3>{{ __('etablissement.repartition_paiements') }}</h3>
            </div>
            <div style="position:relative;height:300px;">
                <canvas id="chart-venn"></canvas>
            </div>
            {{-- Légende manuelle : exclusifs + cœur multi-moyens --}}
            <div id="venn-legende" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:12px;font-size:12.5px;font-weight:600;font-family:'Poppins',sans-serif;color:var(--ep-gris);"></div>
            @if(empty($venn['mtn_momo'] ?? 0) && empty($venn['orange_money'] ?? 0) && empty($venn['carte'] ?? 0) && empty($nbApprenantsMultiMoyens ?? 0))
                <div style="text-align:center;color:#999;font-size:13px;padding:8px 0 0;">{{ __('etablissement.aucune_donnee') }}</div>
            @endif
        </div>

        {{-- ── Histogramme : recouvrement par classe (montants) ── --}}
        <div class="epcard">
            <div class="ep-entete" style="margin-bottom:6px;">
                <span class="material-symbols-outlined">bar_chart</span>
                <h3>{{ __('etablissement.recouvrement_classe') }}</h3>
            </div>
            @if(count($repartitionClasses ?? []))
            <div style="position:relative;height:300px;">
                <canvas id="chart-classes"></canvas>
            </div>
            @else
                <div style="text-align:center;color:#999;font-size:13px;padding:40px 0;">{{ __('etablissement.aucune_donnee') }}</div>
            @endif
        </div>
    </div>

    {{-- ── Détail textuel par classe (taux en pills, conservé) ── --}}
    @if(count($repartitionClasses ?? []))
    <div class="epcard" style="margin-bottom:18px;">
        <div class="ep-entete" style="margin-bottom:10px;">
            <span class="material-symbols-outlined">list_alt</span>
            <h3>{{ __('etablissement.recouvrement_classe') }} — {{ __('etablissement.taux_recouvrement') }}</h3>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
            @foreach($repartitionClasses as $classe)
                <span class="pill {{ $classe['taux'] >= 80 ? 'pg' : ($classe['taux'] >= 50 ? 'pa' : 'pr') }}" style="font-size:12px;padding:6px 12px;">
                    {{ $classe['nom'] }} · {{ $classe['taux'] }}%
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Export ── --}}
    <div class="epcard">
        <div class="ep-entete" style="margin-bottom:4px;">
            <span class="material-symbols-outlined">download</span>
            <h3>{{ __('etablissement.exporter_donnees') }}</h3>
        </div>
        <div class="ep-sous-titre" style="margin-bottom:14px;">{{ __('etablissement.exporter_hint') }}</div>
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

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(function () {
    'use strict';

    // ── Données injectées depuis le contrôleur (JSON sécurisé) ──
    const venn       = @json($venn ?? []);
    const classes    = @json($repartitionClasses ?? []);
    const nbMulti    = @json((int) ($nbApprenantsMultiMoyens ?? 0));
    const tickPas    = @json((int) ($tickPasClasse ?? 100000));

    const TEAL = '#0D9E75', NAVY = '#0B2545', GOLD = '#E8A020', BLUE = '#1F6FB2', ORANGE = '#FF6600';
    const sombre = document.documentElement.getAttribute('data-theme') === 'dark';

    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 12.5;
    Chart.defaults.color = sombre ? '#8CA0B8' : '#5A6472';

    function fmt(v) { return (v >= 1000000 ? (v/1000000) + 'M' : (v >= 1000 ? (v/1000) + 'k' : '' + v)); }

    // ── Venn : rendu canvas natif (2-3 cercles semi-transparents + cœur) ──
    // Chart.js ne propose pas de Venn : on dessine directement sur un canvas.
    const cv = document.getElementById('chart-venn');
    if (cv) {
        const legende = document.getElementById('venn-legende');
        const defs = [
            { cle: 'mtn_momo',      label: 'MTN MoMo',      couleur: '#FFCC00' },
            { cle: 'orange_money',  label: 'Orange Money',  couleur: ORANGE },
            { cle: 'carte',         label: 'Carte bancaire', couleur: BLUE }
        ].filter(d => (venn[d.cle] ?? 0) > 0 || nbMulti > 0);

        const n = defs.length;
        const dpr = window.devicePixelRatio || 1;
        const rect = cv.parentElement.getBoundingClientRect();
        cv.width = rect.width * dpr;
        cv.height = 300 * dpr;
        cv.style.height = '300px';
        const ctx = cv.getContext('2d');
        ctx.scale(dpr, dpr);
        const W = rect.width, H = 300;
        const cx = W / 2, cy = H / 2 + 8;
        const R = Math.min(W, H) * 0.27;
        const ECART = n === 3 ? R * 0.62 : (n === 2 ? R * 0.62 : 0);

        const positions = n === 3
            ? [[cx, cy - R * 0.55], [cx - R * 0.6, cy + R * 0.42], [cx + R * 0.6, cy + R * 0.42]]
            : (n === 2 ? [[cx - ECART / 2, cy], [cx + ECART / 2, cy]] : [[cx, cy]]);

        function cercle(x, y, couleur, alpha) {
            ctx.beginPath();
            ctx.arc(x, y, R, 0, Math.PI * 2);
            ctx.fillStyle = couleur;
            ctx.globalAlpha = alpha;
            ctx.fill();
            ctx.globalAlpha = 1;
            ctx.lineWidth = 2.5;
            ctx.strokeStyle = '#fff';
            ctx.stroke();
        }

        // Fond sombre : contour adapté
        if (sombre) { cv.style.background = 'transparent'; }

        defs.forEach((d, i) => cercle(positions[i][0], positions[i][1], d.couleur, 0.45));

        // Cœur multi-moyens : disque doré semi-transparent au centre
        if (nbMulti > 0) {
            const rCoeur = n === 3 ? R * 0.42 : R * 0.5;
            ctx.beginPath();
            ctx.arc(cx, cy, rCoeur, 0, Math.PI * 2);
            ctx.fillStyle = GOLD;
            ctx.globalAlpha = 0.55;
            ctx.fill();
            ctx.globalAlpha = 1;
        }

        // Étiquettes de compteurs (exclusifs)
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = "700 17px 'Poppins', sans-serif";
        ctx.fillStyle = NAVY;
        defs.forEach((d, i) => {
            const [x, y] = positions[i];
            // Décale l'étiquette vers l'extérieur du cercle
            const dx = (x - cx) * 0.55, dy = (y - cy) * 0.55;
            ctx.fillStyle = sombre ? '#E8EEF6' : NAVY;
            ctx.fillText(String(venn[d.cle] ?? 0), x + dx * 0.9, y + dy * 0.9);
        });
        // Étiquette du cœur
        if (nbMulti > 0) {
            ctx.font = "800 15px 'Poppins', sans-serif";
            ctx.fillStyle = '#7A5205';
            ctx.fillText(String(nbMulti), cx, cy);
        }

        // Légende sous le graphique
        if (legende) {
            let html = defs.map(d =>
                '<span style="display:inline-flex;align-items:center;gap:6px;">' +
                '<span class="dot" style="background:' + d.couleur + ';"></span>' +
                d.label + ' (' + (venn[d.cle] ?? 0) + ')</span>'
            ).join('');
            if (nbMulti > 0) {
                html += '<span style="display:inline-flex;align-items:center;gap:6px;">' +
                        '<span class="dot" style="background:' + GOLD + ';"></span>Multi-moyens (' + nbMulti + ')</span>';
            }
            legende.innerHTML = html;
        }
    }

    // ── Histogramme : attendu vs encaissé par classe ──
    const cvc = document.getElementById('chart-classes');
    if (cvc && classes.length) {
        new Chart(cvc, {
            type: 'bar',
            data: {
                labels: classes.map(c => c.nom),
                datasets: [
                    {
                        label: 'Attendu',
                        data: classes.map(c => c.attendu),
                        backgroundColor: sombre ? 'rgba(232,238,246,.22)' : 'rgba(11,37,69,.18)',
                        borderRadius: 6
                    },
                    {
                        label: 'Encaissé',
                        data: classes.map(c => c.paye),
                        backgroundColor: TEAL,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                    tooltip: {
                        backgroundColor: NAVY, padding: 12, cornerRadius: 10,
                        callbacks: {
                            label: (ctx) => ' ' + ctx.dataset.label + ' : ' + ctx.parsed.y.toLocaleString('fr-FR') + ' FCFA',
                            afterLabel: (ctx) => {
                                if (ctx.datasetIndex !== 1) return '';
                                const c = classes[ctx.dataIndex];
                                return ' Taux : ' + c.taux + '% · ' + c.nb_apprenants + ' élèves';
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 12 } } },
                    y: {
                        beginAtZero: true,
                        grid: { color: sombre ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)' },
                        ticks: { callback: (v) => fmt(v), stepSize: tickPas }
                    }
                }
            }
        });
    }
})();
</script>
@endpush
