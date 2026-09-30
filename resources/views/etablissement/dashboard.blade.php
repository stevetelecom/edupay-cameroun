@extends('layouts.etablissement')

@section('title', __('etablissement.tdb_financier'))

@section('content')

    {{-- Les deux bandeaux d'identité (statut + plan) côte à côte,
         largeurs égales ; empilés sous 1024px --}}
    <div class="ep-bandeaux-duo">

    {{-- ── Bannière statut établissement ── --}}
    @if(isset($etablissement))
    @php
        $statut = $etablissement->statut ?? 'inconnu';
        $config = match($statut) {
            'actif'      => ['bg'=>'#ECFDF5','border'=>'#0D9E75','text'=>'#065F46','icon'=>'verified','label'=>__('etablissement.statut_actif_lbl'),      'msg'=>__('etablissement.statut_actif_msg')],
            'en_attente' => ['bg'=>'#FFFBEB','border'=>'#E8A020','text'=>'#92400E','icon'=>'hourglass_top','label'=>__('etablissement.statut_attente_lbl'),'msg'=>__('etablissement.statut_attente_msg')],
            'suspendu'   => ['bg'=>'#FEF2F2','border'=>'#D94040','text'=>'#7F1D1D','icon'=>'block','label'=>__('etablissement.statut_suspendu_lbl'),   'msg'=>__('etablissement.statut_suspendu_msg')],
            default      => ['bg'=>'#F9FAFB','border'=>'#9CA3AF','text'=>'#374151','icon'=>'info','label'=>__('etablissement.statut_inconnu_lbl'),      'msg'=>__('etablissement.statut_inconnu_msg')],
        };

        // Config plan abonnement
        $planConfig = null;
        $planLabel  = null;
        $planColor  = null;
        $planIcon   = null;
        $planMsg    = null;
        if (isset($abonnement) && $abonnement) {
            $planData = \App\Models\Abonnement::PLANS[$abonnement->plan] ?? null;
            $planLabel = $planData ? ucfirst($abonnement->plan) : ucfirst($abonnement->plan);
            $planColor = match($abonnement->plan) {
                'basique'  => ['bg'=>'#E0F5EE','border'=>'#0D9E75','text'=>'#065F46','icon'=>'workspace_premium'],
                'standard' => ['bg'=>'#E6F0FB','border'=>'#185FA5','text'=>'#1A4F8A','icon'=>'star'],
                'premium'  => ['bg'=>'#FEF3DC','border'=>'#E8A020','text'=>'#92400E','icon'=>'diamond'],
                default    => ['bg'=>'#F9FAFB','border'=>'#9CA3AF','text'=>'#374151','icon'=>'help'],
            };
            $maxApp = $planData['max_apprenants'] ?? -1;
            $planMsg = match($abonnement->plan) {
                'basique'  => __('etablissement.plan_basique_msg', ['max' => $maxApp]),
                'standard' => __('etablissement.plan_standard_msg', ['max' => $maxApp]),
                'premium'  => __('etablissement.plan_premium_msg'),
                default    => __('etablissement.plan_defaut_msg'),
            };
            $joursRestants = $abonnement->joursRestants();
            $abonnementExpire = $abonnement->date_fin ? $abonnement->date_fin->format('d/m/Y') : '';

            // Etat REEL, derive des dates et non du statut stocke. Sans cela,
            // une periode terminee s'affichait « 0 jours restants » et
            // l'avertissement « expire le 25/09 (0 jours) » : on pouvait
            // croire que l'etablissement etait encore couvert ce jour-la.
            $abonnementEtat = $abonnement->etat();
            $abonnementEcheance = $abonnement->grace_period_fin
                ? $abonnement->grace_period_fin->format('d/m/Y')
                : '';

            // L'icone principale suit l'ALERTE, pas seulement le plan : un
            // abonnement expire ou en grace doit se voir immediatement, sans
            // avoir a lire le texte.
            $alerteIcon = match($abonnementEtat) {
                'expire'       => 'error',
                'grace_period' => 'warning',
                default        => $joursRestants <= 7 ? 'warning' : $planColor['icon'],
            };
        }
    @endphp

    {{-- Bannière statut établissement — composant ep-bandeau v2 --}}
    <div class="ep-bandeau {{ match($statut) {
        'actif' => 'succes', 'en_attente' => 'attente', 'suspendu' => 'danger', default => 'neutre',
    } }}">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">{{ $config['icon'] }}</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-titre">
                {{ $config['label'] }}
                <span class="ep-bandeau-badge">{{ strtoupper($statut) }}</span>
            </div>
            <div class="ep-bandeau-texte">{{ $config['msg'] }}</div>
            @if($statut === 'en_attente')
            <div class="ep-bandeau-texte" style="margin-top:4px;display:flex;align-items:center;gap:5px;opacity:.85;">
                <span class="material-symbols-outlined" style="font-size:14px;">mail</span>
                {!! __('etablissement.email_notif_validation', ['email' => e(Auth::user()->email)]) !!}
            </div>
            @endif
        </div>
        <div class="ep-bandeau-droite">
            <div style="font-size:10.5px;opacity:.7;">{{ __('etablissement.nom_etablissement_label') }}</div>
            <div style="font-weight:600;color:var(--ep-navy);word-break:break-word;">{{ $etablissement->nom }}</div>
        </div>
    </div>
    @endif

    {{-- Bannière plan abonnement — composant ep-bandeau v2 (repliable) --}}
    @if(isset($abonnement) && $abonnement && isset($planColor))
    @php
        $planVariante = match($abonnementEtat) {
            'expire'       => 'danger',
            'grace_period' => 'attente',
            default        => $joursRestants <= 7 ? 'attente' : 'succes',
        };
    @endphp
    <div class="ep-bandeau repliable {{ $planVariante }}">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">{{ $alerteIcon }}</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-titre">
                {{ __('etablissement.plan_plan', ['plan' => $planLabel]) }}
                <span class="ep-bandeau-badge">{{ __('etablissement.plan_badge', ['statut' => strtoupper(str_replace('_', ' ', $abonnementEtat))]) }}</span>
            </div>
            <div class="ep-bandeau-texte">{{ $planMsg }}</div>
            @if($abonnementEtat === 'expire')
            <div style="font-size:11px;color:#B91C1C;margin-top:4px;font-weight:700;display:flex;align-items:center;gap:4px;">
                <span class="material-symbols-outlined" style="font-size:13px;">event_busy</span>
                {{ __('etablissement.abonnement_expire', ['date' => $abonnementExpire]) }}
            </div>
            <div style="margin-top:8px;">
                <a href="{{ route('etablissement.abonnement.requis') }}" class="ep-btn-renouveler">
                    <span class="material-symbols-outlined">autorenew</span>
                    {{ __('etablissement.abon_renouveler') }}
                </a>
            </div>
            @elseif($abonnementEtat === 'grace_period')
            <div style="font-size:11px;color:#B45309;margin-top:4px;font-weight:600;display:flex;align-items:center;gap:4px;">
                <span class="material-symbols-outlined" style="font-size:13px;">warning</span>
                {{ __('etablissement.abonnement_expire_grace', ['date' => $abonnementExpire, 'grace' => $abonnementEcheance]) }}
            </div>
            @elseif(isset($joursRestants) && $joursRestants <= 7)
            <div style="font-size:11px;color:#D94040;margin-top:4px;font-weight:600;display:flex;align-items:center;gap:4px;">
                <span class="material-symbols-outlined" style="font-size:13px;">warning</span>
                {!! __('etablissement.expiration_warning', ['jours' => e($joursRestants), 'date' => e($abonnementExpire)]) !!}
            </div>
            @endif
        </div>
        <div class="ep-bandeau-droite">
            <div style="font-size:10.5px;opacity:.7;">{{ __('etablissement.expire_le') }}</div>
            <div style="font-weight:700;color:var(--ep-navy);">{{ $abonnementExpire ?: __('etablissement.date_non_renseignee') }}</div>
            @if(isset($abonnement) && $abonnement && $abonnement->montant_mensuel)
            <div style="font-size:10px;opacity:.75;margin-top:5px;padding-top:4px;border-top:1px dashed var(--ep-bordure);">
                {{ number_format((int) $abonnement->montant_mensuel, 0, ',', ' ') }} × {{ $abonnement->dureeEnMois() }} {{ __('etablissement.mois') }}<br/>
                <span style="font-size:12.5px;font-weight:800;color:var(--ep-navy);">{{ number_format($abonnement->montantTotal(), 0, ',', ' ') }} FCFA</span>
            </div>
            @endif
            @if($abonnementEtat === 'expire')
            <div style="font-size:11px;color:#B91C1C;font-weight:700;margin-top:3px;">{{ __('etablissement.periode_echue') }}</div>
            @elseif(isset($joursRestants))
            <div style="font-size:11px;font-weight:600;margin-top:3px;color:{{ $joursRestants <= 7 ? '#D94040' : 'var(--ep-gris)' }};">
                {{ __('etablissement.jours_restants', ['count' => $joursRestants]) }}
            </div>
            @endif
        </div>
        <button type="button" class="ep-bandeau-chevron" aria-label="Détails">
            <span class="material-symbols-outlined">expand_more</span>
        </button>
        {{-- Contenu repliable : rappel des actions du plan --}}
        <div class="ep-bandeau-contenu">
            <div class="ep-bandeau-contenu-inner">
                <div style="display:flex;align-items:center;gap:6px;font-weight:600;color:var(--ep-navy);margin-bottom:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px;color:var(--ep-gold);">workspace_premium</span>
                    {{ __('etablissement.plan_plan', ['plan' => $planLabel]) }}
                </div>
                @if($abonnementEtat === 'expire')
                    <a href="{{ route('etablissement.abonnement.requis') }}" class="btn-p" style="width:auto;display:inline-flex;align-items:center;gap:6px;padding:8px 16px;font-size:12px;">
                        <span class="material-symbols-outlined" style="font-size:15px;">autorenew</span>
                        {{ __('etablissement.abon_renouveler') }}
                    </a>
                @elseif($abonnementEtat === 'grace_period')
                    <a href="{{ route('etablissement.abonnement.requis') }}" class="ep-btn-renouveler">
                        <span class="material-symbols-outlined">autorenew</span>
                        {{ __('etablissement.abon_renouveler') }}
                    </a>
                @else
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span class="material-symbols-outlined" style="font-size:15px;color:var(--ep-teal);">check_circle</span>
                        {{ __('etablissement.aucun_abonnement') === '' ? '' : __('messages.statut') }} : <strong style="color:var(--ep-teal2);">{{ __('etablissement.statut_actif_lbl') }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
    @elseif($statut === 'actif')
    <div class="ep-bandeau neutre" style="align-items:center;">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">credit_card_off</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-texte">{{ __('etablissement.aucun_abonnement') }}</div>
        </div>
    </div>
    @endif

    </div><!-- /ep-bandeaux-duo -->

    @if(($nbApprenants ?? 0) > 0 && ($nbFraisAnnee ?? 0) === 0)
    <div class="ep-bandeau attente" style="align-items:center;">
        <div class="ep-bandeau-ico">
            <span class="material-symbols-outlined">event_busy</span>
        </div>
        <div class="ep-bandeau-corps">
            <div class="ep-bandeau-texte">
                {{ __('etablissement.aucun_frais_annee', ['annee' => $anneeScolaire ?? \App\Support\AnneeScolaire::active($etablissement ?? null)]) }}
            </div>
        </div>
        <a href="{{ route('etablissement.frais.index') }}" class="btn-o" style="width:auto;padding:8px 16px;flex-shrink:0;">{{ __('etablissement.gerer_frais') }}</a>
    </div>
    @endif

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.tdb_financier') }}</div>
    <div style="font-size:12px;color:#888;margin-bottom:16px;">
        {{ __('etablissement.annee_scolaire_valeur', ['annee' => $anneeScolaire ?? \App\Support\AnneeScolaire::active($etablissement ?? null)]) }} · {{ \Carbon\Carbon::now()->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}
    </div>

    {{-- ── KPIs ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi ep-kpi">
            <div class="ep-ico vert"><span class="material-symbols-outlined">account_balance_wallet</span></div>
            <div>
                <div class="kval" style="font-size:20px;" data-ep-count>
                    {{ number_format($totalEncaisseMois ?? 0, 0, ',', ' ') }}
                </div>
                <div class="klbl">{{ __('etablissement.kpi_encaisse_mois') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico rouge"><span class="material-symbols-outlined">error</span></div>
            <div>
                <div class="kval" style="font-size:20px;" data-ep-count>
                    {{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}
                </div>
                <div class="klbl">{{ __('etablissement.kpi_impaye') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico bleu"><span class="material-symbols-outlined">school</span></div>
            <div>
                <div class="kval" data-ep-count>{{ $nbApprenants ?? 0 }}</div>
                <div class="klbl">{{ __('etablissement.kpi_eleves_inscrits') }}</div>
            </div>
        </div>
        <div class="kpi ep-kpi">
            <div class="ep-ico or"><span class="material-symbols-outlined">folder_off</span></div>
            <div>
                <div class="kval" data-ep-count>{{ $nbDossiersImpayes ?? 0 }}</div>
                <div class="klbl">{{ __('etablissement.kpi_dossiers_impayes') }}</div>
            </div>
        </div>
    </div>

    {{-- ══ GRAPHIQUES — recouvrement + évolution (Chart.js) ══ --}}
    <div class="g2" style="margin-bottom:18px;">
        <div class="epcard">
            <div class="seclbl" style="margin:0 0 10px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:20px;">show_chart</span>
                {{ __('etablissement.tdb_financier') }}
            </div>
            <div style="height:230px;">
                <canvas id="chart-recouvrement"></canvas>
            </div>
        </div>
        <div class="epcard">
            <div class="seclbl" style="margin:0 0 10px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:20px;">donut_large</span>
                {{ __('etablissement.moyens_paiement') ?? 'Moyens de paiement' }}
            </div>
            <div style="height:230px;">
                <canvas id="chart-moyens-etab"></canvas>
            </div>
        </div>
    </div>

    {{-- ══ HISTOGRAMME — encaissements des 14 derniers jours ══ --}}
    <div class="epcard" style="margin-bottom:18px;">
        <div class="seclbl" style="margin:0 0 14px;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:20px;">bar_chart</span>
            {{ __('etablissement.encaissements_14_jours') ?? 'Encaissements des 14 derniers jours' }}
        </div>
        <div class="ep-histo" id="histo-14j">
            @foreach($histogramme14Jours as $j)
            <div class="ep-histo-col {{ $j['actif'] ? '' : 'dim' }}">
                <div class="ep-histo-bar" style="height:{{ max($j['total'] > 0 ? max(6, round(($j['total'] / max($histogramme14Jours->max('total'), 1)) * 100)) : 2, 2) }}%;animation-delay:{{ $loop->index * 30 }}ms;">
                    <span class="ep-histo-tip">{{ number_format($j['total'], 0, ',', ' ') }} FCFA · {{ $j['libelle'] }}</span>
                </div>
                <span class="ep-histo-lbl">{{ $j['jour'] }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Taux de recouvrement ── --}}
    <div class="epcard" style="margin-bottom:18px;padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:22px;">track_changes</span>
                <div>
                    <div style="font-size:14px;font-weight:600;color:#333;margin-bottom:2px;">
                        {{ __('etablissement.taux_recouvrement_titre', ['annee' => $anneeScolaire ?? \App\Support\AnneeScolaire::active($etablissement ?? null)]) }}
                    </div>
                <div style="font-size:12px;color:#888;">
                    {{ __('etablissement.taux_recouvrement_legend', ['payes' => number_format($totalPaye ?? 0, 0, ',', ' '), 'attendu' => number_format($totalAttendu ?? 0, 0, ',', ' ')]) }}
                </div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:28px;font-weight:700;color:{{ ($tauxRecouvrementDecimal ?? 0) >= 80 ? '#0D9E75' : (($tauxRecouvrementDecimal ?? 0) >= 50 ? '#E8A020' : '#D94040') }};">
                    {{ number_format($tauxRecouvrementDecimal ?? 0, 2, ',', '') }}%
                </div>
            </div>
        </div>
        <div style="background:#f0f0f0;border-radius:6px;height:8px;overflow:hidden;">
            <div style="background:{{ ($tauxRecouvrementDecimal ?? 0) >= 80 ? '#0D9E75' : (($tauxRecouvrementDecimal ?? 0) >= 50 ? '#E8A020' : '#D94040') }};height:100%;width:{{ min($tauxRecouvrementDecimal ?? 0, 100) }}%;transition:width 0.5s ease;">
            </div>
        </div>
    </div>

    {{-- ── Derniers paiements reçus ── --}}
    <div class="seclbl" style="margin-top:0;display:flex;align-items:center;gap:8px;">
        <span class="material-symbols-outlined" style="color:var(--ep-gold);font-size:20px;">history</span>
        {{ __('etablissement.derniers_paiements') }}
    </div>
    <div class="epcard" style="margin-bottom:14px;">
        @forelse ($derniersPaiements ?? [] as $paiement)
            <div class="row">
                <div>
                    <div style="font-size:13px;font-weight:600;">
                        {{ $paiement->apprenant->nom }} {{ $paiement->apprenant->prenom }} · {{ $paiement->apprenant->classe }}
                    </div>
                    <div style="font-size:11px;color:#888;">
                        {{ $paiement->fraisApprenant->categorieFrais->nom ?? '—' }}
                        ·
                        {{ match($paiement->mode_paiement) {
                            'mtn_momo' => __('etablissement.mtn_momo'),
                            'orange_money' => __('etablissement.orange_money'),
                            'carte' => __('etablissement.carte'),
                            default => $paiement->mode_paiement,
                        } }}
                        ·
                        {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->locale(app()->getLocale())->diffForHumans() : '—' }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-weight:600;color:{{ $paiement->statut === 'valide' ? 'var(--ep-teal)' : 'var(--ep-gold)' }};">
                        {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                    <span class="pill {{ match($paiement->statut) {
                        'valide' => 'pg',
                        'en_attente' => 'pa',
                        'echoue' => 'pr',
                        'rembourse' => 'pb',
                        default => 'pa',
                    } }}">
                        {{ match($paiement->statut) {
                            'valide' => __('etablissement.st_recu'),
                            'en_attente' => __('etablissement.st_en_attente'),
                            'echoue' => __('etablissement.st_echoue'),
                            'rembourse' => __('etablissement.st_rembourse'),
                            'annule' => __('etablissement.st_annule'),
                            default => $paiement->statut,
                        } }}
                    </span>
                </div>
            </div>
        @empty
            <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
                {{ __('etablissement.aucun_paiement') }}
            </div>
        @endforelse
    </div>

    <div style="display:flex;gap:10px;">
        <form method="POST" action="{{ route('etablissement.impayes.relancer') }}" style="flex:1;">
            @csrf
            <button type="submit" class="btn-p" style="font-size:12px;width:100%;">
                {{ __('etablissement.relancer_impayes_btn', ['count' => $nbDossiersImpayes ?? 0]) }}
            </button>
        </form>
        <a href="{{ route('etablissement.rapports.index') }}" class="btn-o" style="flex:1;font-size:12px;">
            {{ __('etablissement.exporter_rapport_excel') }}
        </a>
    </div>

    {{-- ── Info recouvrement pour établissement ── --}}
    <div style="font-size:12px;color:#999;margin-top:16px;text-align:center;">
        {!! __('etablissement.taux_etab_info', ['taux' => e(number_format($tauxRecouvrementDecimal ?? 0, 2, ',', ''))]) !!}
    </div>

@endsection

@push('scripts')
{{-- Chart.js via CDN --}}
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
(function () {
    'use strict';

    // ── Construction (ou re-construction) des graphiques au changement
    //    de thème clair / sombre (événement « ep-themechange »). ──
    function monterGraphiques() {

        // Détruit les instances existantes avant re-création (bascule de thème)
        ['chart-recouvrement', 'chart-moyens-etab'].forEach(function (id) {
            var c = document.getElementById(id);
            if (c && window.Chart && Chart.getChart(c)) Chart.getChart(c).destroy();
        });

    // Données injectées depuis le contrôleur
    const totalAttendu = @json((int) ($totalAttendu ?? 0));
    const totalPaye    = @json((int) ($totalPaye ?? 0));
    const totalImpaye  = @json((int) ($totalImpaye ?? 0));
    const repartition  = @json($repartitionMoyensAnnee);
    const encaisseMois = @json((int) ($totalEncaisseMois ?? 0));

    const TEAL = '#0D9E75', RED = '#D94040', NAVY = '#0B2545', GOLD = '#E8A020', BLUE = '#1F6FB2';
    // Thème actuel : adapte les couleurs de lecture des graphiques
    const sombre = document.documentElement.getAttribute('data-theme') === 'dark';
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = sombre ? '#8CA0B8' : '#5A6472';

    // ══ Donut : recouvrement (payé / impayé) ══
    const ctxRec = document.getElementById('chart-recouvrement');
    if (ctxRec) {
        if (totalAttendu <= 0) {
            new Chart(ctxRec, {
                type: 'doughnut',
                data: { labels: ['Aucun frais cette année'], datasets: [{ data: [1], backgroundColor: [sombre ? '#1C2C45' : '#E4E9EE'], borderWidth: 0 }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        } else {
            new Chart(ctxRec, {
                type: 'doughnut',
                data: {
                    labels: ['Encaissé', 'Impayé'],
                    datasets: [{
                        data: [totalPaye, Math.max(totalAttendu - totalPaye, 0)],
                        backgroundColor: [TEAL, RED],
                        borderWidth: 3,
                        borderColor: sombre ? '#0E1A2E' : '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                        tooltip: {
                            backgroundColor: NAVY, padding: 12, cornerRadius: 10,
                            callbacks: { label: (ctx) => ' ' + ctx.parsed.toLocaleString('fr-FR') + ' FCFA' }
                        }
                    }
                }
            });
        }
    }

    // ══ Donut : répartition par moyen de paiement (année active) ══
    const ctxMoy = document.getElementById('chart-moyens-etab');
    if (ctxMoy) {
        const config = {
            'mtn_momo':     { label: 'MTN MoMo',      couleur: GOLD },
            'orange_money': { label: 'Orange Money',  couleur: '#FF6600' },
            'carte':        { label: 'Carte bancaire', couleur: BLUE }
        };
        const labels = [], data = [], couleurs = [];
        Object.keys(config).forEach(k => {
            const r = repartition[k];
            if (r && r.total > 0) {
                labels.push(config[k].label);
                data.push(r.total);
                couleurs.push(config[k].couleur);
            }
        });
        if (data.length === 0) {
            labels.push('Aucun paiement');
            data.push(1);
            couleurs.push('#E4E9EE');
        }
        new Chart(ctxMoy, {
            type: 'doughnut',                data: { labels, datasets: [{ data, backgroundColor: couleurs, borderWidth: 3, borderColor: (sombre ? '#0E1A2E' : '#fff'), hoverOffset: 8 }] },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
                    tooltip: {
                        backgroundColor: NAVY, padding: 12, cornerRadius: 10,
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

    } // ── ferme monterGraphiques() : cette accolade manquait, le script
      //    entier mourait en SyntaxError et les 2 graphiques restaient vides

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
