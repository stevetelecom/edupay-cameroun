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

        @php
            $operateurAffiche = match($paiement->operateur ?? null) {
                'MTN_Cameroon'    => ['nom' => 'MTN Mobile Money', 'court' => 'MTN',    'bg' => '#FFFBE6', 'border' => '#FFCC00', 'texte' => '#996600', 'chip_texte' => '#553300'],
                'Orange_Cameroon' => ['nom' => 'Orange Money',      'court' => 'Orange', 'bg' => '#FFF5EE', 'border' => '#FF6600', 'texte' => '#CC4400', 'chip_texte' => '#ffffff'],
                default           => ['nom' => 'Mobile Money',      'court' => __('payeur.pa_votre_operateur'), 'bg' => '#f5f5f5', 'border' => '#ddd', 'texte' => '#555', 'chip_texte' => '#555'],
            };
        @endphp
        <div id="msg-attente">
            <div id="msg-attente-titre" style="font-size:17px;font-weight:700;margin-bottom:8px;">{{ __('payeur.pa_attente_titre') }}</div>
            <div style="font-size:13px;color:#888;margin-bottom:14px;">
                {!! __('payeur.pa_attente_confirm', ['montant' => number_format($paiement->montant_total_paye ?? $paiement->montant, 0, ',', ' '), 'tel' => $paiement->telephone_paiement]) !!}<br><br>
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
            </div>
            <div id="msg-attente-detail" style="font-size:12px;color:#555;margin-bottom:10px;line-height:1.6;">
                {!! __('payeur.pa_attente_notif') !!}<br>
                <strong>{{ __('payeur.pa_attente_si30s') }}</strong>
                <span style="background:#f0fdf4;color:#085041;font-weight:700;
                             padding:2px 8px;border-radius:4px;font-family:monospace;">*126#</span>
                {!! __('payeur.pa_attente_menu') !!}
                <strong>{{ __('payeur.pa_attente_rejetez') }}</strong> {{ __('payeur.pa_attente_validez') }}
            </div>
            <div id="msg-attente-prolonge" style="display:none;background:#FEF9EC;border-radius:8px;padding:10px 12px;margin-bottom:10px;font-size:12px;color:#854F0B;line-height:1.6;text-align:left;">
                {!! __('payeur.pa_attente_prolonge', ['ref' => $paiement->reference]) !!}
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
                {!! __('payeur.pa_valide_confirme', ['montant' => number_format($paiement->montant_total_paye ?? $paiement->montant, 0, ',', ' ')]) !!}
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
    fin: @json(__('payeur.pa_attente_fin')),
};

const statutUrl = "{{ route('payeur.paiement.statut', $paiement) }}";

// Phase 1 : vérification rapide, toutes les 5s, pendant 6 minutes (72 tentatives)
// Phase 2 : si rien de définitif après la phase 1, on NE déclare JAMAIS d'échec
//           côté client — on continue à vérifier, plus espacé, jusqu'à 20 minutes
//           au total. Seule une vraie réponse de l'opérateur (SUCCESSFUL / FAILED)
//           peut faire passer l'écran en "valide" ou "échec".
const PHASE1_TENTATIVES = 72;   // 72 × 5s  = 6 min
const PHASE2_INTERVALLE = 10000; // 10s
const PHASE2_TENTATIVES = 84;   // 84 × 10s = 14 min supplémentaires (total ≈ 20 min)

let tentatives = 0;
let enPhase2 = false;
let verificationManuelleEnCours = false;

function afficher(etat) {
    document.getElementById('icone-attente').style.display = etat === 'attente' ? 'flex' : 'none';
    document.getElementById('icone-valide').style.display  = etat === 'valide'  ? 'flex' : 'none';
    document.getElementById('icone-echec').style.display   = etat === 'echec'   ? 'flex' : 'none';
    document.getElementById('msg-attente').style.display   = etat === 'attente' ? '' : 'none';
    document.getElementById('msg-valide').style.display    = etat === 'valide'  ? '' : 'none';
    document.getElementById('msg-echec').style.display     = etat === 'echec'   ? '' : 'none';
}

function passerEnPhase2() {
    if (enPhase2) return;
    enPhase2 = true;
    document.getElementById('msg-attente-prolonge').style.display = 'block';
    document.getElementById('btn-verifier-maintenant').style.display = 'inline-block';
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
    tentatives++;
    const data = await appelStatut();

    if (data && data.statut === 'valide') {
        afficher('valide');
        return;
    }

    // Seule une réponse EXPLICITE 'echoue' de l'API (donc un vrai FAILED/CANCELLED
    // confirmé par AangaraaPay/MTN) fait passer l'écran en échec — jamais un timeout.
    if (data && data.statut === 'echoue') {
        const detail = document.getElementById('msg-echec-detail');
        if (detail && data.message) detail.textContent = data.message;
        afficher('echec');
        return;
    }

    // Toujours en attente : on continue, en passant en phase 2 (espacée) après
    // la phase 1 rapide — sans jamais déclarer d'échec depuis le client.
    if (tentatives === PHASE1_TENTATIVES) {
        passerEnPhase2();
    }

    if (!enPhase2 && tentatives < PHASE1_TENTATIVES) {
        setTimeout(verifier, 5000);
        return;
    }

    if (enPhase2 && (tentatives - PHASE1_TENTATIVES) < PHASE2_TENTATIVES) {
        setTimeout(verifier, PHASE2_INTERVALLE);
        return;
    }

    // Fin de la phase 2 (~20 min) sans réponse définitive : on arrête le polling
    // automatique mais on NE déclare PAS d'échec — l'utilisateur peut vérifier
    // manuellement ou contacter le support avec la référence.
    passerEnPhase2();
    document.getElementById('msg-attente-detail').textContent = PAYEUR_L10N.fin;
}

async function verifierMaintenant() {
    if (verificationManuelleEnCours) return;
    verificationManuelleEnCours = true;
    const btn = document.getElementById('btn-verifier-maintenant');
    btn.textContent = PAYEUR_L10N.verification;
    btn.disabled = true;

    const data = await appelStatut();

    if (data && data.statut === 'valide') {
        afficher('valide');
    } else if (data && data.statut === 'echoue') {
        const detail = document.getElementById('msg-echec-detail');
        if (detail && data.message) detail.textContent = data.message;
        afficher('echec');
    } else {
        btn.textContent = PAYEUR_L10N.verifier_btn;
        btn.disabled = false;
        verificationManuelleEnCours = false;
    }
}

setTimeout(verifier, 5000);
</script>
@endpush
