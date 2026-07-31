@extends('layouts.payeur')

@section('title', 'Paiement en attente')

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

        <div id="msg-attente">
            <div style="font-size:17px;font-weight:700;margin-bottom:8px;">En attente de confirmation</div>
            <div style="font-size:13px;color:#888;margin-bottom:20px;">
                Confirmez le paiement de <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong>
                sur votre téléphone <strong>{{ $paiement->telephone_paiement }}</strong>.<br><br>
                Réf. : <code>{{ $paiement->reference }}</code>
            </div>
            <div style="font-size:13px;color:#0D9E75;font-weight:600;margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:6px;">
                <span class="material-symbols-outlined"
                      style="font-size:18px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;">
                    smartphone
                </span>
                Consultez votre téléphone maintenant
            </div>
            <div style="font-size:12px;color:#888;margin-bottom:8px;">
                MTN ou Orange envoie une notification USSD — entrez votre code PIN MoMo pour confirmer.
            </div>
            <div style="font-size:11px;color:#aaa;">Vérification automatique toutes les 5 secondes…</div>
        </div>

        <div id="msg-valide" style="display:none;">
            <div style="font-size:17px;font-weight:700;color:#085041;margin-bottom:8px;">Paiement confirmé !</div>
            <div style="font-size:13px;color:#888;margin-bottom:20px;">
                Votre paiement de <strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong> a bien été reçu.
            </div>
            <a href="{{ route('payeur.dashboard') }}" class="btn-p" style="width:auto;padding:10px 24px;">
                Retour au tableau de bord
            </a>
        </div>

        <div id="msg-echec" style="display:none;">
            <div style="font-size:17px;font-weight:700;color:var(--ep-red);margin-bottom:8px;">Paiement échoué</div>
            <div id="msg-echec-detail" style="font-size:13px;color:#888;margin-bottom:20px;">
                Le paiement n'a pas pu être confirmé. Vérifiez votre solde ou réessayez.
            </div>
            <a href="{{ route('payeur.paiement.show', $paiement->fraisApprenant) }}"
               class="btn-p" style="width:auto;padding:10px 24px;margin-bottom:10px;display:inline-block;">
                Réessayer
            </a><br>
            <a href="{{ route('payeur.dashboard') }}" class="btn-o" style="width:auto;padding:10px 24px;">
                Retour au tableau de bord
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
const statutUrl = "{{ route('payeur.paiement.statut', $paiement) }}";
let tentatives = 0;
const maxTentatives = 24; // 2 minutes max (24 × 5s)

function afficher(etat) {
    document.getElementById('icone-attente').style.display = etat === 'attente' ? '' : 'none';
    document.getElementById('icone-valide').style.display  = etat === 'valide'  ? '' : 'none';
    document.getElementById('icone-echec').style.display   = etat === 'echec'   ? '' : 'none';
    document.getElementById('msg-attente').style.display   = etat === 'attente' ? '' : 'none';
    document.getElementById('msg-valide').style.display    = etat === 'valide'  ? '' : 'none';
    document.getElementById('msg-echec').style.display     = etat === 'echec'   ? '' : 'none';
}

async function verifier() {
    tentatives++;
    try {
        const res  = await fetch(statutUrl, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (data.statut === 'valide') {
            afficher('valide'); return;
        }
        if (data.statut === 'echoue') {
            // Afficher le message précis de l'opérateur
            var detail = document.getElementById('msg-echec-detail');
            if (detail && data.message) {
                detail.textContent = data.message;
            }
            afficher('echec'); return;
        }
    } catch (e) { /* réseau — on réessaie */ }

    if (tentatives >= maxTentatives) {
        afficher('echec'); return;
    }

    setTimeout(verifier, 5000);
}

setTimeout(verifier, 5000);
</script>
@endpush
