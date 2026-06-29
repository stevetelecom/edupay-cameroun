@extends('layouts.etablissement')

@section('title', 'Tableau de bord')

@section('content')

    {{-- ── Bannière statut établissement ── --}}
    @if(isset($etablissement))
    @php
        $statut = $etablissement->statut ?? 'inconnu';
        $config = match($statut) {
            'actif'        => ['bg'=>'#ECFDF5','border'=>'#0D9E75','text'=>'#065F46','icon'=>'✅','label'=>'Établissement actif',      'msg'=>'Votre établissement est validé et actif sur EduPay.'],
            'en_attente'   => ['bg'=>'#FFFBEB','border'=>'#E8A020','text'=>'#92400E','icon'=>'⏳','label'=>'En attente de validation', 'msg'=>"Votre dossier est en cours d'examen par l'équipe EduPay. Vous serez notifié par email dès activation."],
            'suspendu'     => ['bg'=>'#FEF2F2','border'=>'#D94040','text'=>'#7F1D1D','icon'=>'🚫','label'=>'Établissement suspendu',   'msg'=>"Votre compte est suspendu. Contactez le support EduPay pour plus d'informations."],
            default        => ['bg'=>'#F9FAFB','border'=>'#9CA3AF','text'=>'#374151','icon'=>'ℹ️','label'=>'Statut inconnu',           'msg'=>'Statut non défini. Contactez le support.'],
        };
    @endphp
    <div style="
        background:{{ $config['bg'] }};
        border:1.5px solid {{ $config['border'] }};
        border-radius:10px;
        padding:12px 16px;
        margin-bottom:18px;
        display:flex;
        align-items:flex-start;
        gap:12px;
        flex-wrap:wrap;
    ">
        <div style="font-size:20px;flex-shrink:0;">{{ $config['icon'] }}</div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:13px;font-weight:700;color:{{ $config['text'] }};margin-bottom:2px;">
                {{ $config['label'] }}
                <span style="font-weight:400;font-size:11px;margin-left:8px;background:{{ $config['border'] }};color:#fff;padding:1px 8px;border-radius:99px;">
                    {{ strtoupper($statut) }}
                </span>
            </div>
            <div style="font-size:12px;color:{{ $config['text'] }};opacity:0.85;line-height:1.5;">
                {{ $config['msg'] }}
            </div>
            @if($statut === 'en_attente')
            <div style="font-size:11px;color:#92400E;margin-top:4px;opacity:0.7;">
                📧 Un email vous sera envoyé à <strong>{{ Auth::user()->email }}</strong> dès validation.
            </div>
            @endif
        </div>
        <div style="flex-shrink:0;text-align:right;min-width:120px;">
            <div style="font-size:11px;color:{{ $config['text'] }};opacity:0.7;">Nom de l'établissement</div>
            <div style="font-size:12px;font-weight:600;color:{{ $config['text'] }};word-break:break-word;">
                {{ $etablissement->nom }}
            </div>
        </div>
    </div>
    @endif

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">Tableau de bord financier</div>
    <div style="font-size:12px;color:#888;margin-bottom:16px;">
        Année {{ $anneeScolaire ?? '2025-2026' }} · {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('MMMM YYYY') }}
    </div>

    {{-- ── KPIs ── --}}
    <div class="g4" style="margin-bottom:18px;">
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-teal);">
                {{ number_format($totalEncaisseMois ?? 0, 0, ',', ' ') }}
            </div>
            <div class="klbl">FCFA encaissés (mois)</div>
        </div>
        <div class="kpi">
            <div class="kval" style="font-size:18px;color:var(--ep-red);">
                {{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}
            </div>
            <div class="klbl">FCFA impayés</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $nbApprenants ?? 0 }}</div>
            <div class="klbl">Élèves inscrits</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ $nbDossiersImpayes ?? 0 }}</div>
            <div class="klbl">Dossiers impayés</div>
        </div>
    </div>

    {{-- ── Taux de recouvrement ── --}}
    <div class="epcard" style="margin-bottom:18px;padding:16px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div style="font-size:14px;font-weight:600;color:#333;margin-bottom:2px;">
                    Taux de recouvrement — {{ $anneeScolaire ?? '2025-2026' }}
                </div>
                <div style="font-size:12px;color:#888;">
                    {{ number_format($totalPaye ?? 0, 0, ',', ' ') }} FCFA payés sur {{ number_format($totalAttendu ?? 0, 0, ',', ' ') }} FCFA attendus
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
    <div class="seclbl" style="margin-top:0;">Derniers paiements reçus</div>
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
                            'mtn_momo' => 'MTN MoMo',
                            'orange_money' => 'Orange Money',
                            'carte' => 'Carte',
                            default => $paiement->mode_paiement,
                        } }}
                        ·
                        {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->diffForHumans() : '—' }}
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
                            'valide' => 'Reçu',
                            'en_attente' => 'En attente',
                            'echoue' => 'Échoué',
                            'rembourse' => 'Remboursé',
                            default => $paiement->statut,
                        } }}
                    </span>
                </div>
            </div>
        @empty
            <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
                Aucun paiement enregistré pour le moment.
            </div>
        @endforelse
    </div>

    <div style="display:flex;gap:10px;">
        <form method="POST" action="{{ route('etablissement.impayes.relancer') }}" style="flex:1;">
            @csrf
            <button type="submit" class="btn-p" style="font-size:12px;width:100%;">
                Relancer les {{ $nbDossiersImpayes ?? 0 }} impayés par SMS
            </button>
        </form>
        <a href="{{ route('etablissement.rapports.index') }}" class="btn-o" style="flex:1;font-size:12px;">
            Exporter rapport Excel
        </a>
    </div>

    {{-- ── Info recouvrement pour établissement ── --}}
    <div style="font-size:12px;color:#999;margin-top:16px;text-align:center;">
        💡 Taux de votre établissement : <strong>{{ number_format($tauxRecouvrementDecimal ?? 0, 2, ',', '') }}%</strong>
    </div>

@endsection
