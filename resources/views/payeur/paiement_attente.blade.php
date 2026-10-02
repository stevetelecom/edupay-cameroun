@extends('layouts.payeur')

@section('title', __('payeur.pa_titre'))

@section('content')

<div style="max-width:480px;margin:40px auto;text-align:center;">

    <div class="epcard" style="padding:32px;">

        {{-- Icône animée --}}
        <div id="icone-attente" style="margin-bottom:16px;display:flex;justify-content:center;">
            <span class="material-symbols-outlined"
                  style="font-size:56px;color:#0D9E75;animation:spin 2s linear infinite;
                         font-variation-settings:'FILL' 0,'wght' 300,'GRAD' 0,'opsz' 48;">
                progress_activity
            </span>
        </div>
        <div id="icone-valide" style="margin-bottom:16px;display:none;justify-content:center;">
            <span class="material-symbols-outlined"
                  style="font-size:56px;color:#0D9E75;
                         font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 48;">
                check_circle
            </span>
        </div>
        <div id="icone-echec" style="margin-bottom:16px;display:none;justify-content:center;">
            <span class="material-symbols-outlined"
                  style="font-size:56px;color:#D94040;
                         font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 48;">
                cancel
            </span>
        </div>
        <div id="icone-annule" style="margin-bottom:16px;display:none;justify-content:center;">
            <span class="material-symbols-outlined"
                  style="font-size:56px;color:#6B7280;
                         font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 48;">
                block
            </span>
        </div>

        @php
            $operateurAffiche = match($paiement->operateurAffiche()) {
                'MTN_Cameroon'    => ['nom' => 'MTN Mobile Money', 'court' => 'MTN',    'bg' => '#FFFBE6', 'border' => '#FFCC00', 'texte' => '#996600', 'chip_texte' => '#553300'],
                'Orange_Cameroon' => ['nom' => 'Orange Money',      'court' => 'Orange', 'bg' => '#FFF5EE', 'border' => '#FF6600', 'texte' => '#CC4400', 'chip_texte' => '#ffffff'],
                default           => ['nom' => 'Mobile Money',      'court' => __('payeur.pa_votre_operateur'), 'bg' => '#f5f5f5', 'border' => '#ddd', 'texte' => '#555', 'chip_texte' => '#555'],
            };
// Le declenchement de la confirmation n'est PAS le meme selon
            // l'operateur, meme si le-schema est identique (notification,
            // puis code a composer, puis code secret) :
            // MTN : AangaraaPay pousse l'USSD, une fenetre *126# s'ouvre
            // toute seule, le payeur appuie sur 1 puis saisit son code secret.
            // Orange : AangaraaPay envoie un SMS, aucune fenetre USSD ne
            // s'ouvre. Le payeur compose lui-meme #150*50# depuis le numero
            // qui a recu le SMS.
            //
            // Le SMS est donc le declencheur normal des deux cote
            // operateur ; le popup automatique est une specificite MTN.
            // La formulation « le client recoit un prompt sur son telephone »
            // de la doc AangaraaPay decrit ce SMS, pas un push USSD qui
            // s'ouvrirait aussi chez Orange.
            $estOrange = $paiement->operateurAffiche() === 'Orange_Cameroon';
        @endphp
        <div id="msg-attente">
            <div id="msg-attente-titre" style="font-size:17px;font-weight:700;margin-bottom:8px;">{{ __('payeur.pa_attente_titre') }}</div>
            <div style="font-size:13px;color:#888;margin-bottom:14px;">
                {!! __('payeur.pa_attente_confirm', ['montant' => e(number_format($paiement->montant_total_paye ?? $paiement->montant, 0, ',', ' ')), 'tel' => e($paiement->telephone_paiement)]) !!}<br><br>
                {{ __('payeur.pa_ref') }} : <code>{{ $paiement->reference }}</code>
            </div>
            <div style="display:inline-flex;align-items:center;gap:8px;background:{{ $operateurAffiche['bg'] }};border:1px solid {{ $operateurAffiche['border'] }};border-radius:20px;padding:5px 14px;margin-bottom:16px;font-size:12px;font-weight:700;color:{{ $operateurAffiche['texte'] }};">
                <span style="background:{{ $operateurAffiche['border'] }};padding:1px 6px;border-radius:3px;font-size:10px;color:{{ $operateurAffiche['chip_texte'] }};">{{ $operateurAffiche['court'] }}</span>
                {{ $operateurAffiche['nom'] }}
            </div>
            <div id="msg-attente-phase1" style="font-size:13px;color:{{ $operateurAffiche['texte'] }};font-weight:600;margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:6px;">
                <span class="material-symbols-outlined"
                      style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">
                    smartphone
                </span>
                {{ __('payeur.pa_attente_phone', ['operateur' => $operateurAffiche['court']]) }}
                <span id="chrono-attente" style="font-family:monospace;font-weight:700;">00:00</span>
            </div>
            {{-- Parcours MTN : prompt USSD, composition obligatoire --}}
            <div id="msg-attente-detail-mtn" style="font-size:12px;color:#555;margin-bottom:10px;line-height:1.6;{{ $estOrange ? 'display:none;' : '' }}">
                {!! __('payeur.pa_attente_notif') !!}<br>
                <strong>{{ __('payeur.pa_attente_si30s') }}</strong>
                <span style="background:#f0fdf4;color:#085041;font-weight:700;
                             padding:2px 8px;border-radius:4px;font-family:monospace;">*126#</span>
                {!! __('payeur.pa_attente_menu') !!}
                <strong>{{ __('payeur.pa_attente_rejetez') }}</strong> {{ __('payeur.pa_attente_validez') }}
            </div>
            {{-- Parcours Orange : notification + code USSD Orange Money (#150*50#).
                 Le push SMS d'Orange demande explicitement de composer ce code
                 pour valider le paiement : c'est l'equivalent du *126# de MTN.
                 Sources : doc Orange Money Cameroun (api-s1.orange.cm omcoreapis
                 /mp/pay), CinetPay, Digital Virgo, Camplex. La consigne d'Orange
                 elle-meme prime sur la doc AangaraaPay, qui ne mentionne pour
                 Orange qu'une « notification » sans code. --}}
            <div id="msg-attente-detail-orange" style="font-size:12px;color:#555;margin-bottom:10px;line-height:1.6;{{ $estOrange ? '' : 'display:none;' }}">
                {!! __('payeur.pa_attente_notif') !!}<br>
                {{ __('payeur.pa_attente_orange_notif') }}
                <span style="background:#fff5ee;color:#CC4400;font-weight:700;
                             padding:2px 8px;border-radius:4px;font-family:monospace;">#150*50#</span><br>
                <strong>{{ __('payeur.pa_attente_orange_pin') }}</strong><br>
                {{ __('payeur.pa_attente_orange_code') }}<br>
                <strong>{{ __('payeur.pa_attente_rejetez') }}</strong> {{ __('payeur.pa_attente_validez') }}
            </div>
            {{-- Frais : le payeur doit voir ce qu'il debite au total, pas seulement
                 le montant des frais scolaires. La page d'attente ne montrait que
                 le total, d'ou l'incomprehension sur le debit reel. --}}
            <div style="background:{{ $operateurAffiche['bg'] }};border:1px solid {{ $operateurAffiche['border'] }};border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:12px;color:#555;line-height:1.8;text-align:left;">
                <div style="display:flex;justify-content:space-between;">
                    <span>{{ __('payeur.pa_attente_frais_base') }}</span>
                    <span style="font-weight:600;">{{ number_format($paiement->montant ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span>{{ __('payeur.pa_attente_frais_service') }}</span>
                    <span style="font-weight:600;">{{ number_format($paiement->frais_service ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
                <div style="display:flex;justify-content:space-between;border-top:1px solid {{ $operateurAffiche['border'] }};margin-top:6px;padding-top:6px;">
                    <span style="font-weight:700;color:{{ $operateurAffiche['texte'] }};">{{ __('payeur.pa_attente_total_debite') }}</span>
                    <span style="font-weight:700;color:{{ $operateurAffiche['texte'] }};">{{ number_format($paiement->montant_total_paye ?? $paiement->montant ?? 0, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
            <div id="msg-attente-prolonge" style="display:none;background:#FEF9EC;border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:12px;color:#854F0B;line-height:1.6;text-align:left;">
                {!! __('payeur.pa_attente_prolonge', ['ref' => e($paiement->reference)]) !!}
            </div>
            <div style="font-size:11px;color:#aaa;">{{ __('payeur.pa_attente_verification') }}</div>
            <button type="button" onclick="verifierMaintenant()" id="btn-verifier-maintenant"
                    style="display:none;margin-top:12px;background:transparent;border:1px solid #ddd;color:#555;font-size:12px;padding:8px 16px;border-radius:8px;cursor:pointer;">
                {{ __('payeur.pa_attente_verifier_btn') }}
            </button>
        </div>

        <div id="msg-valide" style="display:none;">
            <div style="font-size:17px;font-weight:700;color:#085041;margin-bottom:8px;">{{ __('payeur.pa_valide_titre') }}</div>
            <div style="font-size:13px;color:#888;margin-bottom:20px;">
                {!! __('payeur.pa_valide_confirme', ['montant' => e(number_format($paiement->montant_total_paye ?? $paiement->montant, 0, ',', ' '))]) !!}
            </div>
            <a href="{{ route('payeur.dashboard') }}" class="btn-p" style="width:auto;padding:10px 24px;">
                {{ __('payeur.pa_retour_dashboard') }}
            </a>
        </div>

        <div id="msg-annule" style="display:none;">
            <div style="font-size:17px;font-weight:700;color:#4B5563;margin-bottom:8px;">{{ __('payeur.pa_annule_titre') }}</div>
            <div style="font-size:13px;color:#888;margin-bottom:8px;">{{ __('payeur.pa_annule_detail') }}</div>
            <div style="font-size:12px;color:#888;margin-bottom:20px;">
                {{ __('payeur.pa_ref') }} : <code>{{ $paiement->reference }}</code>
            </div>
            <a href="{{ route('payeur.dashboard') }}" class="btn-p" style="width:auto;padding:10px 24px;">
                {{ __('payeur.pa_retour_dashboard') }}
            </a>
        </div>

        <div id="msg-echec" style="display:none;">
            <div style="font-size:17px;font-weight:700;color:var(--ep-red);margin-bottom:8px;">{{ __('payeur.pa_echec_titre') }}</div>
            <div id="msg-echec-detail" style="font-size:13px;color:#888;margin-bottom:20px;">
                {{ __('payeur.pa_echec_detail') }}
            </div>
            <a href="{{ route('payeur.paiement.show', $paiement->fraisApprenant) }}"
               class="btn-p" style="width:auto;padding:10px 24px;margin-bottom:10px;display:inline-block;">
                {{ __('payeur.pa_reesayer') }}
            </a><br>
            <a href="{{ route('payeur.dashboard') }}" class="btn-o" style="width:auto;padding:10px 24px;">
                {{ __('payeur.pa_retour_dashboard') }}
            </a>
        </div>

    </div>
</div>

@endsection

@push('styles')
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush

@push('scripts')
<script>
const PAYEUR_L10N = {
    verifier_btn: @json(__('payeur.pa_attente_verifier_btn')),
    verification: @json(__('payeur.pa_attente_verification_ellipsis')),
};

const statutUrl = "{{ route('payeur.paiement.statut', $paiement) }}";

// Vérification continue jusqu'à une réponse DÉFINITIVE de l'opérateur.
//
// Pourquoi plus de plafond de tentatives : Orange ne tranche pas toujours en
// quelques minutes. Constat en production le 02/10/2026, paiement
// EP2026-QL7RA (52 FCFA, 693723***200) :
//     07:21:52  initier    -> payToken MP2610025258C8AAE97BB04F1922, PENDING
//     07:54:04  check      -> PENDING
//     07:56:05  check      -> PENDING
//     07:58:05  check      -> PENDING
//     08:00:04  check      -> PENDING
//     08:00:48  check      -> FAILED
// L'échec est arrivé 39 min après l'initiation. L'ancien script abandonnait à
// 20 min et laissait l'écran figé sur le spinner : le payeur voyait « en cours »
// pendant 19 min après la réponse réelle de l'opérateur.
//
// On ne déclare JAMAIS d'échec pour raison de temps : seul un SUCCESSFUL /
// FAILED explicite fait basculer l'écran. Passé le seuil d'affichage prolonged,
// on ralentit la cadence et on affiche le temps écoulé, mais on continue
// d'interroger l'API — c'est le seul moyen de rattraper une confirmation
// tardive, qui est le cas normal d'Orange.
const INTERVALLE_RAPIDE     = 5000;   // 5s  pendant les 3 premières minutes
const SEUIL_RAPIDE_MS        = 180000;
const INTERVALLE_LENT        = 15000;  // 15s ensuite
const SEUIL_AFFICHAGE_LONG_MS = 600000; // 10 min : on affiche le temps écoulé

const debutAttente = Date.now();
let intervalleCourant = INTERVALLE_RAPIDE;
let verificationManuelleEnCours = false;
let minuteur = null;

function formaterDuree(ms) {
    const total = Math.floor(ms / 1000);
    const m = Math.floor(total / 60);
    const s = total % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
}

function majChrono() {
    const el = document.getElementById('chrono-attente');
    if (el) el.textContent = formaterDuree(Date.now() - debutAttente);
}

function afficher(etat) {
    document.getElementById('icone-attente').style.display = etat === 'attente' ? 'flex' : 'none';
    document.getElementById('icone-valide').style.display  = etat === 'valide'  ? 'flex' : 'none';
    document.getElementById('icone-echec').style.display   = etat === 'echec'   ? 'flex' : 'none';
    document.getElementById('icone-annule').style.display  = etat === 'annule'  ? 'flex' : 'none';
    document.getElementById('msg-attente').style.display   = etat === 'attente' ? '' : 'none';
    document.getElementById('msg-valide').style.display    = etat === 'valide'  ? '' : 'none';
    document.getElementById('msg-echec').style.display     = etat === 'echec'   ? '' : 'none';
    document.getElementById('msg-annule').style.display    = etat === 'annule'  ? '' : 'none';
}

function afficherDelaiLong() {
    const box = document.getElementById('msg-attente-prolonge');
    const btn = document.getElementById('btn-verifier-maintenant');
    if (box && box.style.display !== 'block') box.style.display = 'block';
    if (btn && btn.style.display !== 'inline-block') btn.style.display = 'inline-block';
}

async function appelStatut() {
    try {
        const res  = await fetch(statutUrl, { headers: { 'Accept': 'application/json' } });
        return await res.json();
    } catch (e) {
        return null;
    }
}

async function verifier() {
    const ecoule = Date.now() - debutAttente;

    // Ralentissement après 3 min : moins de appels, mais on ne cesse JAMAIS de
    // interroger l'API tant qu'aucune réponse définitive n'est arrivée.
    intervalleCourant = ecoule <= SEUIL_RAPIDE_MS ? INTERVALLE_RAPIDE : INTERVALLE_LENT;

    if (ecoule >= SEUIL_AFFICHAGE_LONG_MS) afficherDelaiLong();

    const data = await appelStatut();

    if (data && data.statut === 'valide') {
        arreterMinuteur();
        afficher('valide');
        return;
    }

    // Annulé entre-temps (autre onglet / API mobile) : état définitif, on arrête.
    if (data && data.statut === 'annule') {
        arreterMinuteur();
        afficher('annule');
        return;
    }

    // Seule une réponse EXPLICITE 'echoue' de l'API (donc un vrai FAILED/CANCELLED
    // confirmé par AangaraaPay/MTN) fait passer l'écran en échec — jamais un timeout.
    if (data && data.statut === 'echoue') {
        arreterMinuteur();
        const detail = document.getElementById('msg-echec-detail');
        if (detail && data.message) detail.textContent = data.message;
        afficher('echec');
        return;
    }

    setTimeout(verifier, intervalleCourant);
}

// Réveil d'onglet : un onglet mis en arrière-plan est bridé par le navigateur,
// ce qui espace les appels au-delà de notre intervalle. À son retour on
// interroge l'API immédiatement plutôt que d'attendre le prochain tick.
document.addEventListener('visibilitychange', function () {
    if (!document.hidden && verificationManuelleEnCours === false) verifier();
});

async function verifierMaintenant() {
    if (verificationManuelleEnCours) return;
    verificationManuelleEnCours = true;
    const btn = document.getElementById('btn-verifier-maintenant');
    btn.textContent = PAYEUR_L10N.verification;
    btn.disabled = true;

    const data = await appelStatut();

    if (data && data.statut === 'valide') {
        arreterMinuteur();
        afficher('valide');
    } else if (data && data.statut === 'annule') {
        arreterMinuteur();
        afficher('annule');
    } else if (data && data.statut === 'echoue') {
        arreterMinuteur();
        const detail = document.getElementById('msg-echec-detail');
        if (detail && data.message) detail.textContent = data.message;
        afficher('echec');
    } else {
        btn.textContent = PAYEUR_L10N.verifier_btn;
        btn.disabled = false;
        verificationManuelleEnCours = false;
    }
}

function arreterMinuteur() {
    if (minuteur !== null) {
        clearInterval(minuteur);
        minuteur = null;
    }
}

// Un paiement deja annule est un etat definitif : aucun polling.
if (@json($paiement->estAnnule())) {
    arreterMinuteur();
    afficher('annule');
} else {
    minuteur = setInterval(majChrono, 1000);
    majChrono();
    setTimeout(verifier, 5000);
}
</script>
@endpush
