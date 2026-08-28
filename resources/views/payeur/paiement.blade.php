@extends('layouts.payeur')

@section('title', __('payeur.pay_titre'))

@php
    $resteAPayer    = $fraisApprenant->montant_total - $fraisApprenant->montant_paye;
    $nbTranches     = $fraisApprenant->categorieFrais->nb_tranches_max ?? 2;
    $montantTranche = (int) round($resteAPayer / $nbTranches);
    $fractionnable  = $fraisApprenant->categorieFrais->fractionnable ?? false;

    // Calcul frais de service selon barème dégressif
    function calculerFraisService(int $montant): array {
        $fraisVisibles = match(true) {
            $montant <= 10000  => 200,
            $montant <= 25000  => 400,
            $montant <= 50000  => 800,
            $montant <= 100000 => 1500,
            default            => 2500,
        };
        return [
            'frais'  => $fraisVisibles,
            'total'  => $montant + $fraisVisibles,
        ];
    }

    $fraisIntegral = calculerFraisService((int) $resteAPayer);
    $fraisTranche  = calculerFraisService($montantTranche);
@endphp

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">← {{ __('payeur.pay_retour') }}</a>
        <span style="display:flex;align-items:center;gap:6px;font-size:11px;color:#999;margin-left:auto;">
            <span style="background:var(--ep-teal);width:7px;height:7px;border-radius:50%;display:inline-block;"></span>
            {{ __('payeur.pay_connexion_securee') }}
        </span>
    </div>

    <div style="max-width:600px;margin:0 auto;">

        {{-- ── Récap frais ── --}}
        <div class="epcard" style="background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:11px;color:#0F6E56;margin-bottom:3px;">{{ __('payeur.pay_pour') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#085041;">
                        {{ $fraisApprenant->apprenant->nom }} {{ $fraisApprenant->apprenant->prenom }}
                        · {{ $fraisApprenant->apprenant->etablissement->nom ?? '' }}
                    </div>
                    <div style="font-size:12px;color:#1B9E75;">
                        {{ $fraisApprenant->categorieFrais->nom ?? __('payeur.pay_frais_scolaires') }} — {{ $fraisApprenant->annee_scolaire }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:26px;font-weight:700;color:#085041;">{{ number_format($resteAPayer, 0, ',', ' ') }}</div>
                    <div style="font-size:11px;color:#0F6E56;">FCFA</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('payeur.paiement.initier', $fraisApprenant) }}">
            @csrf

            {{-- ── Options de paiement ── --}}
            <div class="seclbl">{{ __('payeur.pay_option') }}</div>
            @if($fractionnable)
                <div style="font-size:11px;color:#888;margin-bottom:8px;">
                    {{ __('payeur.pay_echeancier') }}
                    <strong style="color:#0F6E56;">{{ __('payeur.pay_nb_tranches', ['count' => $nbTranches]) }}</strong>
                </div>
            @endif

            <div style="display:flex;gap:10px;margin-bottom:16px;">
                <label id="opt1" style="flex:1;padding:12px;border:2px solid var(--ep-teal);border-radius:var(--radius-md);cursor:pointer;background:var(--ep-teal-lt);text-align:center;display:block;">
                    <input type="radio" name="type_paiement" value="integral" checked style="display:none;" onclick="selOpt(1)">
                    <div style="font-size:11px;font-weight:700;color:#0F6E56;">{{ __('payeur.pay_integral') }}</div>
                    <div style="font-size:10px;color:#1B9E75;margin-bottom:2px;">{{ __('payeur.pay_solde_total') }}</div>
                    <div style="font-size:18px;font-weight:700;color:#085041;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</div>
                </label>

                @if($fractionnable)
                    <label id="opt2" style="flex:1;padding:12px;border:1px solid #ddd;border-radius:var(--radius-md);cursor:pointer;text-align:center;display:block;">
                        <input type="radio" name="type_paiement" value="tranche" style="display:none;" onclick="selOpt(2)">
                        <div style="font-size:11px;font-weight:700;color:#888;">{{ __('payeur.pay_tranche_suivante', ['n' => ($fraisApprenant->numero_tranche_suivante ?? 1), 'nb' => $nbTranches]) }}</div>
                        <div style="font-size:10px;color:#aaa;margin-bottom:2px;">{{ __('payeur.pay_montant_auto') }}</div>
                        <div style="font-size:18px;font-weight:700;">{{ number_format($montantTranche, 0, ',', ' ') }} FCFA</div>
                        @if($fraisApprenant->prochaine_echeance ?? false)
                            <div style="font-size:10px;color:#aaa;margin-top:2px;">
                                {{ __('payeur.pay_echeance') }} {{ \Carbon\Carbon::parse($fraisApprenant->prochaine_echeance)->format('d M. Y') }}
                            </div>
                        @endif
                    </label>
                @endif
            </div>

            {{-- ── Moyen de paiement — MTN + Orange uniquement ── --}}
            <div class="seclbl">{{ __('payeur.pay_moyen') }}</div>
            <div style="display:flex;gap:10px;margin-bottom:16px;">
                <label id="pm1" style="flex:1;padding:12px;border:2px solid #FFCC00;border-radius:var(--radius-md);background:#FFFBE6;cursor:pointer;text-align:center;display:block;">
                    <input type="radio" name="mode_paiement" value="mtn_momo" checked style="display:none;" onclick="selPay(1)">
                    <div style="font-size:12px;font-weight:700;color:#996600;">MTN</div>
                    <div style="font-size:11px;color:#664400;">Mobile Money</div>
                </label>
                <label id="pm2" style="flex:1;padding:12px;border:1px solid #ddd;border-radius:var(--radius-md);cursor:pointer;text-align:center;display:block;">
                    <input type="radio" name="mode_paiement" value="orange_money" style="display:none;" onclick="selPay(2)">
                    <div style="font-size:12px;font-weight:700;color:#FF6600;">Orange</div>
                    <div style="font-size:11px;">Money</div>
                </label>
            </div>

            {{-- ── Numéro téléphone ── --}}
            <div class="lbl" id="pay-momo-lbl">{{ __('payeur.pay_numero_mtn') }}</div>
            <input type="text"
                   name="telephone_paiement"
                   id="pay-momo-input"
                   value="{{ old('telephone_paiement', $telephonePrefill ?? Auth::user()->telephone ?? '') }}"
                   class="inp"
                   placeholder="{{ __('payeur.pay_placeholder_tel') }}"
                   inputmode="tel"
                   autocomplete="tel"
                   oninput="surSaisieTelephone(this.value)"
                   required>
            <div style="font-size:11px;color:#888;margin-top:-6px;margin-bottom:10px;">
                {!! __('payeur.pay_explication_237') !!}
            </div>
            <div id="pay-op-avertissement" style="display:none;background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:9px 12px;margin-top:-6px;margin-bottom:12px;font-size:11px;color:#854F0B;">
                {!! __('payeur.pay_op_avertissement') !!}
            </div>
            @error('telephone_paiement')
                <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div>
            @enderror

            {{-- ── Avertissement USSD ── --}}
            <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:12px;margin-bottom:16px;font-size:12px;color:#854F0B;display:flex;gap:8px;align-items:flex-start;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                {{ __('payeur.pay_ussd_notif') }}
            </div>

            {{-- ── Récap montant ── --}}
            <div style="font-size:13px;color:#888;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span>{{ __('payeur.pay_montant') }}</span>
                <span style="font-weight:600;color:#333;" id="pay-montant-recap">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="font-size:13px;color:#888;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span>{{ __('payeur.pay_tranche') }}</span>
                <span style="font-weight:600;color:#888;" id="pay-tranche-recap">{{ __('payeur.pay_integral_label') }}</span>
            </div>
            <div style="font-size:13px;color:#888;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span>{{ __('payeur.pay_frais_service') }}
                    <span style="font-size:10px;color:#aaa;display:block;">{{ __('payeur.pay_frais_service_sous') }}</span>
                </span>
                <span style="font-weight:600;color:#555;" id="pay-frais-recap">{{ number_format($fraisIntegral['frais'], 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="border-top:1px solid #eee;padding-top:12px;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span style="font-size:15px;font-weight:700;">{{ __('payeur.pay_total_a_payer') }}</span>
                <span style="font-size:22px;font-weight:700;color:var(--ep-teal);" id="pay-total-recap">{{ number_format($fraisIntegral['total'], 0, ',', ' ') }} FCFA</span>
            </div>

            {{-- ── Indicateur opérateur ── --}}
            <div style="font-size:12px;color:#888;margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                <span id="pay-op-dot" style="width:8px;height:8px;border-radius:50%;background:#FFCC00;display:inline-block;"></span>
                <span id="pay-op-label">{{ __('payeur.pay_op_mtn') }}</span>
            </div>

            <button type="submit" class="btn-p" style="width:100%;padding:13px;">
                {{ __('payeur.pay_confirm_payer') }} →
            </button>

        </form>
    </div>

@endsection

@push('scripts')
<script>
const PAYEUR_L10N = {
    integ: @json(__('payeur.pay_integral_label')),
    tranche: @json(__('payeur.pay_tranche')),
    numero_mtn: @json(__('payeur.pay_numero_mtn')),
    numero_orange: @json(__('payeur.pay_numero_orange')),
    ph_mtn: @json(__('payeur.pay_placeholder_mtn')),
    ph_orange: @json(__('payeur.pay_placeholder_orange')),
    op_mtn: @json(__('payeur.pay_op_mtn')),
    op_orange: @json(__('payeur.pay_op_orange')),
};

const montantIntegral = {{ (int) $resteAPayer }};
const montantTranche  = {{ $montantTranche }};

// Barème frais de service (identique au backend AangaraaPayService::calculerFrais)
function calculerFrais(montant) {
    let frais;
    if      (montant <= 10000)  frais = 200;
    else if (montant <= 25000)  frais = 400;
    else if (montant <= 50000)  frais = 800;
    else if (montant <= 100000) frais = 1500;
    else                        frais = 2500;
    return { frais, total: montant + frais };
}

function fmt(n) {
    return n.toLocaleString('fr-FR') + ' FCFA';
}

function selOpt(n) {
    [1,2].forEach(i => {
        const el = document.getElementById('opt'+i);
        if (!el) return;
        el.style.border     = i===n ? '2px solid var(--ep-teal)' : '1px solid #ddd';
        el.style.background = i===n ? 'var(--ep-teal-lt)' : '#fff';
    });
    const montant = n===1 ? montantIntegral : montantTranche;
    const f = calculerFrais(montant);
    document.getElementById('pay-montant-recap').textContent = fmt(montant);
    document.getElementById('pay-frais-recap').textContent   = fmt(f.frais);
    document.getElementById('pay-total-recap').textContent   = fmt(f.total);
    document.getElementById('pay-tranche-recap').textContent = n===1 ? PAYEUR_L10N.integ : PAYEUR_L10N.tranche;
}

function selPay(n) {
    const cfg = {
        1: { border:'#FFCC00', bg:'#FFFBE6', dot:'#FFCC00', lbl:PAYEUR_L10N.numero_mtn,     ph:PAYEUR_L10N.ph_mtn,     op:PAYEUR_L10N.op_mtn },
        2: { border:'#FF6600', bg:'#FFF5EE', dot:'#FF6600', lbl:PAYEUR_L10N.numero_orange,   ph:PAYEUR_L10N.ph_orange,   op:PAYEUR_L10N.op_orange },
    };
    [1,2].forEach(i => {
        const el = document.getElementById('pm'+i);
        if (!el) return;
        el.style.border     = i===n ? '2px solid '+cfg[i].border : '1px solid #ddd';
        el.style.background = i===n ? cfg[i].bg : '#fff';
    });
    document.getElementById('pay-momo-lbl').textContent         = cfg[n].lbl;
    document.getElementById('pay-momo-input').placeholder       = cfg[n].ph;
    document.getElementById('pay-op-dot').style.background      = cfg[n].dot;
    document.getElementById('pay-op-label').textContent         = cfg[n].op;
}

// ── Détection automatique MTN / Orange à la saisie du numéro ──
// Règles confirmées 2026 : MTN = 650-654 / 670-679 · Orange = 655-659 / 690-699
// Tout préfixe hors de ces plages (ex. Nexttel/Camtel/68x) reste volontairement
// "inconnu" plutôt que de deviner au hasard — le payeur choisit alors manuellement.
function detecterOperateurLocal(valeur) {
    let numero = (valeur || '').replace(/\D/g, '');
    if (numero.startsWith('237')) numero = numero.slice(3);
    numero = numero.replace(/^0+/, '');
    if (numero.length < 3) return null;
    const prefixe = parseInt(numero.slice(0, 3), 10);
    if ((prefixe >= 650 && prefixe <= 654) || (prefixe >= 670 && prefixe <= 679)) return 'mtn';
    if ((prefixe >= 655 && prefixe <= 659) || (prefixe >= 690 && prefixe <= 699)) return 'orange';
    return 'inconnu';
}

function surSaisieTelephone(valeur) {
    const resultat = detecterOperateurLocal(valeur);
    const avertissement = document.getElementById('pay-op-avertissement');
    if (resultat === 'mtn') {
        selPay(1);
        if (avertissement) avertissement.style.display = 'none';
    } else if (resultat === 'orange') {
        selPay(2);
        if (avertissement) avertissement.style.display = 'none';
    } else if (resultat === 'inconnu') {
        if (avertissement) avertissement.style.display = 'block';
    } else if (avertissement) {
        avertissement.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const champ = document.getElementById('pay-momo-input');
    if (champ && champ.value) surSaisieTelephone(champ.value);
});
</script>
@endpush
