@extends('layouts.etablissement')

@section('title', __('etablissement.tdb_financier'))

@section('content')

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
            $abonnementExpire = $abonnement->date_fin ? $abonnement->date_fin->format('d/m/Y') : '—';
        }
    @endphp

    {{-- Bannière statut établissement --}}
    <div style="background:{{ $config['bg'] }};border:1.5px solid {{ $config['border'] }};border-radius:10px;padding:12px 16px;margin-bottom:12px;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
        <span class="material-symbols-outlined" style="font-size:22px;color:{{ $config['border'] }};flex-shrink:0;margin-top:1px;">{{ $config['icon'] }}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:{{ $config['text'] }};margin-bottom:2px;">
                {{ $config['label'] }}
                <span style="font-weight:400;font-size:11px;margin-left:8px;background:{{ $config['border'] }};color:#fff;padding:1px 8px;border-radius:99px;">
                    {{ strtoupper($statut) }}
                </span>
            </div>
            <div style="font-size:12px;color:{{ $config['text'] }};opacity:0.85;line-height:1.5;">{{ $config['msg'] }}</div>
            @if($statut === 'en_attente')
            <div style="font-size:11px;color:#92400E;margin-top:4px;opacity:0.7;display:flex;align-items:center;gap:4px;">
                <span class="material-symbols-outlined" style="font-size:13px;">mail</span>
                {!! __('etablissement.email_notif_validation', ['email' => Auth::user()->email]) !!}
            </div>
            @endif
        </div>
        <div style="flex-shrink:0;text-align:right;min-width:120px;">
            <div style="font-size:11px;color:{{ $config['text'] }};opacity:0.7;">{{ __('etablissement.nom_etablissement_label') }}</div>
            <div style="font-size:12px;font-weight:600;color:{{ $config['text'] }};word-break:break-word;">
                {{ $etablissement->nom }}
            </div>
        </div>
    </div>
    @endif

    {{-- Bannière plan abonnement --}}
    @if(isset($abonnement) && $abonnement && isset($planColor))
    <div style="background:{{ $planColor['bg'] }};border:1.5px solid {{ $planColor['border'] }};border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
        <span class="material-symbols-outlined" style="font-size:22px;color:{{ $planColor['border'] }};flex-shrink:0;margin-top:1px;">{{ $planColor['icon'] }}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:{{ $planColor['text'] }};margin-bottom:2px;">
                {{ __('etablissement.plan_plan', ['plan' => $planLabel]) }}
                <span style="font-weight:400;font-size:11px;margin-left:8px;background:{{ $planColor['border'] }};color:#fff;padding:1px 8px;border-radius:99px;">
                    {{ __('etablissement.plan_badge', ['statut' => strtoupper($abonnement->statut)]) }}
                </span>
            </div>
            <div style="font-size:12px;color:{{ $planColor['text'] }};opacity:0.85;line-height:1.5;">{{ $planMsg }}</div>
            @if(isset($joursRestants) && $joursRestants <= 7)
            <div style="font-size:11px;color:#D94040;margin-top:4px;font-weight:600;display:flex;align-items:center;gap:4px;">
                <span class="material-symbols-outlined" style="font-size:13px;">warning</span>
                {!! __('etablissement.expiration_warning', ['jours' => $joursRestants, 'date' => $abonnementExpire]) !!}
            </div>
            @endif
        </div>
        <div style="flex-shrink:0;text-align:right;min-width:130px;">
            <div style="font-size:11px;color:{{ $planColor['text'] }};opacity:0.7;">{{ __('etablissement.expire_le') }}</div>
            <div style="font-size:12px;font-weight:600;color:{{ $planColor['text'] }};">{{ $abonnementExpire ?? '—' }}</div>
            @if(isset($joursRestants))
            <div style="font-size:11px;color:{{ $joursRestants <= 7 ? '#D94040' : $planColor['text'] }};margin-top:2px;">
                {{ __('etablissement.jours_restants', ['count' => $joursRestants]) }}
            </div>
            @endif
        </div>
    </div>
    @elseif($statut === 'actif')
    <div style="background:#F9FAFB;border:1.5px solid #E5E7EB;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span class="material-symbols-outlined" style="font-size:20px;color:#9CA3AF;">credit_card_off</span>
        <div style="font-size:12px;color:#6B7280;flex:1;">
            {{ __('etablissement.aucun_abonnement') }}
        </div>
    </div>
    @endif

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.tdb_financier') }}</div>
    <div style="font-size:12px;color:#888;margin-bottom:16px;">
        {{ __('etablissement.annee_scolaire', ['annee' => $anneeScolaire ?? '2025-2026']) }} · {{ \Carbon\Carbon::now()->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}
    </div>

    {{-- ── KPIs ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-teal);">
                {{ number_format($totalEncaisseMois ?? 0, 0, ',', ' ') }}
            </div>
            <div class="klbl">{{ __('etablissement.kpi_encaisse_mois') }}</div>
        </div>
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-red);">
                {{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}
            </div>
            <div class="klbl">{{ __('etablissement.kpi_impaye') }}</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $nbApprenants ?? 0 }}</div>
            <div class="klbl">{{ __('etablissement.kpi_eleves_inscrits') }}</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ $nbDossiersImpayes ?? 0 }}</div>
            <div class="klbl">{{ __('etablissement.kpi_dossiers_impayes') }}</div>
        </div>
    </div>

    {{-- ── Taux de recouvrement ── --}}
    <div class="epcard" style="margin-bottom:18px;padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-size:14px;font-weight:600;color:#333;margin-bottom:2px;">
                    {{ __('etablissement.taux_recouvrement_titre', ['annee' => $anneeScolaire ?? '2025-2026']) }}
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
    <div class="seclbl" style="margin-top:0;">{{ __('etablissement.derniers_paiements') }}</div>
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
        {!! __('etablissement.taux_etab_info', ['taux' => number_format($tauxRecouvrementDecimal ?? 0, 2, ',', '')]) !!}
    </div>

@endsection
