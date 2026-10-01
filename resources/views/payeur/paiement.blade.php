@extends('layouts.payeur')

@section('title', __('payeur.pay_titre'))

@php
    // Audit D : les montants affiches viennent du controleur, qui les calcule
    // via App\Support\MontantPaiement — exactement la meme source de verite que
    // celle utilisee pour debiter. Avant, la vue divisait le reste du par
    // nb_tranches_max : l'ecran annoncait une tranche que le serveur ne
    // prenait pas en compte (et inversement sur le mobile).
    $resteAPayer    = $montants['reste_du'];
    $nbTranches     = $fraisApprenant->categorieFrais->nb_tranches_max ?? 2;
    $montantTranche = $montants['tranche'];

    // Le serveur refuse toute tranche sous le minimum operateur : on masque
    // l'option plutot que d'afficher un montant non payable.
    $fractionnable  = ($fraisApprenant->categorieFrais->fractionnable ?? false) && ($montants['tranche_valide'] ?? true);

    // Frais de service : calculés par le service AangaraaPay, jamais recopiés
    // ici. Le barème était dupliqué dans cette vue et le recalculait à la
    // main, ce qui pouvait afficher un total différent du montant réellement
    // débité. Les taux viennent des paramètres système modifiables en super
    // admin : les afficher en dur ici les désynchroniserait à nouveau.
    $serviceFrais  = app(\App\Services\AangaraaPayService::class);

    // Le taux depend du profil d'abonnement de l'etablissement (CDC S0 #3) :
    // l'afficher ici avec le meme appel que le serveur evite que le total
    // annonce au payeur diverge du montant reellement debite.
    $etablissementFrais = $fraisApprenant->apprenant?->etablissement;
    $tauxFraisVue  = $serviceFrais->tauxCommissionEtablissement($etablissementFrais);

    $fraisIntegral = $serviceFrais->calculerFrais((int) $resteAPayer, $etablissementFrais);
    $fraisTranche  = $serviceFrais->calculerFrais($montantTranche, $etablissementFrais);
@endphp

@section('content')

    {{-- En-tête style tableau de bord : pill retour + badge sécurité --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" class="ep-retour-lien">
            <span class="material-symbols-outlined" style="font-size:15px;">arrow_back</span>
            {{ __('payeur.pay_retour') }}
        </a>
        <span style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--ep-gris);margin-left:auto;font-family:'Poppins',sans-serif;">
            <span class="material-symbols-outlined" style="font-size:15px;color:var(--ep-teal);">lock</span>
            {{ __('payeur.pay_connexion_securee') }}
        </span>
    </div>

    <div style="max-width:600px;margin:0 auto;">

        {{-- ── Récap frais — carte pastille époxy (style dashboard) ── --}}
        <div class="epcard" style="margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div style="display:flex;align-items:center;gap:13px;min-width:0;">
                    <div class="ep-ico navy" style="width:46px;height:46px;border-radius:13px;">
                        <span class="material-symbols-outlined" style="font-size:24px;">school</span>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-size:16px;font-weight:700;font-family:'Poppins',sans-serif;color:var(--ep-navy);">
                            {{ $fraisApprenant->apprenant->nom }} {{ $fraisApprenant->apprenant->prenom }}
                        </div>
                        <div style="font-size:12.5px;color:var(--ep-gris);font-family:'Poppins',sans-serif;">
                            {{ $fraisApprenant->apprenant->etablissement->nom ?? '' }}
                        </div>
                        <div style="font-size:12.5px;color:var(--ep-teal2);font-weight:600;font-family:'Poppins',sans-serif;">
                            {{ $fraisApprenant->categorieFrais->nom ?? __('payeur.pay_frais_scolaires') }} — {{ $fraisApprenant->annee_scolaire }}
                        </div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:28px;font-weight:800;font-family:'Poppins',sans-serif;color:var(--ep-navy);">{{ number_format($resteAPayer, 0, ',', ' ') }}</div>
                    <div style="font-size:11.5px;color:var(--ep-gris);font-family:'Poppins',sans-serif;">FCFA</div>
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

            <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
                <label id="opt1" style="flex:1;min-width:180px;padding:14px 12px;border:2px solid var(--ep-teal);border-radius:12px;cursor:pointer;background:var(--ep-teal-lt);text-align:center;display:block;transition:transform .2s ease, box-shadow .2s ease;">
                    <input type="radio" name="type_paiement" value="integral" checked style="display:none;" onclick="selOpt(1)">
                    <div style="font-size:12.5px;font-weight:700;font-family:'Poppins',sans-serif;color:#0F6E56;">{{ __('payeur.pay_integral') }}</div>
                    <div style="font-size:11px;color:#1B9E75;margin-bottom:3px;font-family:'Poppins',sans-serif;">{{ __('payeur.pay_solde_total') }}</div>
                    <div style="font-size:20px;font-weight:800;font-family:'Poppins',sans-serif;color:#085041;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</div>
                </label>

                @if($fractionnable)
                    <label id="opt2" style="flex:1;min-width:180px;padding:14px 12px;border:1.5px solid #ddd;border-radius:12px;cursor:pointer;text-align:center;display:block;transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;">
                        <input type="radio" name="type_paiement" value="tranche" style="display:none;" onclick="selOpt(2)">
                        <div style="font-size:12.5px;font-weight:700;font-family:'Poppins',sans-serif;color:var(--ep-gris);">{{ __('payeur.pay_tranche_suivante', ['n' => ($fraisApprenant->numero_tranche_suivante ?? 1), 'nb' => $nbTranches]) }}</div>
                        <div style="font-size:11px;color:#aaa;margin-bottom:3px;font-family:'Poppins',sans-serif;">{{ __('payeur.pay_montant_auto') }}</div>
                        <div style="font-size:20px;font-weight:800;font-family:'Poppins',sans-serif;">{{ number_format($montantTranche, 0, ',', ' ') }} FCFA</div>
                        @if($fraisApprenant->prochaine_echeance ?? false)
                            <div style="font-size:11px;color:#aaa;margin-top:2px;font-family:'Poppins',sans-serif;">
                                {{ __('payeur.pay_echeance') }} {{ \Carbon\Carbon::parse($fraisApprenant->prochaine_echeance)->format('d M. Y') }}
                            </div>
                        @endif
                    </label>
                @endif
            </div>

            {{-- ── Moyen de paiement — MTN + Orange uniquement ── --}}
            <div class="seclbl">{{ __('payeur.pay_moyen') }}</div>
            <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
                <label id="pm1" style="flex:1;min-width:170px;padding:14px 12px;border:2px solid #FFCC00;border-radius:12px;background:#FFFBE6;cursor:pointer;text-align:center;display:block;transition:transform .2s ease, box-shadow .2s ease;">
                    <input type="radio" name="mode_paiement" value="mtn_momo" checked style="display:none;" onclick="selPay(1)">
                    <div style="font-size:14px;font-weight:800;font-family:'Poppins',sans-serif;color:#996600;">MTN</div>
                    <div style="font-size:12px;color:#664400;font-family:'Poppins',sans-serif;">Mobile Money</div>
                </label>
                <label id="pm2" style="flex:1;min-width:170px;padding:14px 12px;border:1.5px solid #ddd;border-radius:12px;cursor:pointer;text-align:center;display:block;transition:transform .2s ease, border-color .2s ease;">
                    <input type="radio" name="mode_paiement" value="orange_money" style="display:none;" onclick="selPay(2)">
                    <div style="font-size:14px;font-weight:800;font-family:'Poppins',sans-serif;color:#FF6600;">Orange</div>
                    <div style="font-size:12px;font-family:'Poppins',sans-serif;">Money</div>
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
                <span style="font-weight:600;color:#555;" id="pay-frais-recap">{{ number_format($fraisIntegral['frais_service'], 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="border-top:1px solid #eee;padding-top:12px;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span style="font-size:16px;font-weight:700;font-family:'Poppins',sans-serif;">{{ __('payeur.pay_total_a_payer') }}</span>
                <span style="font-size:24px;font-weight:800;font-family:'Poppins',sans-serif;color:var(--ep-teal);" id="pay-total-recap">{{ number_format($fraisIntegral['montant_total_paye'], 0, ',', ' ') }} FCFA</span>
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

// Frais de service : le taux vient du serveur (AangaraaPayService), qui lit
// les paramètres système. On ne recopie plus le barème ici : le payeur
// pouvait voir un total différent de celui réellement débité.
// Arrondis identiques au backend : frais au franc supérieur.
const TAUX_FRAIS = {{ $tauxFraisVue }};
function calculerFrais(montant) {
    const frais = Math.ceil(montant * TAUX_FRAIS);
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
// MTN   : 650-654 / 670-679
// Orange: 640 / 655-659 / 686-689 / 690-699
// Seul le 655-659 et le 690-699 sont documentés par AangaraaPay et confirmés par
// un encaissement réel. Le 640 et le 686-689 viennent du plan national de
// numérotation de l'ART : ce sont des numéros Orange légitimes, on ne doit donc
// pas laisser le payeur choisir à la main. 680-683 = Nexttel/Viettel, ni l'un ni
// l'autre : reste "inconnu", le payeur choisit manuellement.
function detecterOperateurLocal(valeur) {
    let numero = (valeur || '').replace(/\D/g, '');
    if (numero.startsWith('237')) numero = numero.slice(3);
    numero = numero.replace(/^0+/, '');
    if (numero.length < 3) return null;
    const prefixe = parseInt(numero.slice(0, 3), 10);
    if ((prefixe >= 650 && prefixe <= 654) || (prefixe >= 670 && prefixe <= 679)) return 'mtn';
    if (prefixe === 640
        || (prefixe >= 655 && prefixe <= 659)
        || (prefixe >= 686 && prefixe <= 689)
        || (prefixe >= 690 && prefixe <= 699)) return 'orange';
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
