@extends('layouts.payeur')

@section('title', 'Paiement sécurisé')

@php
    $resteAPayer = $fraisApprenant->montant_total - $fraisApprenant->montant_paye;
@endphp

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">← Retour</a>
        <span style="display:flex;align-items:center;gap:6px;font-size:11px;color:#999;margin-left:auto;">
            <span style="background:var(--ep-teal);width:7px;height:7px;border-radius:50%;display:inline-block;"></span>
            Connexion sécurisée TLS 1.3
        </span>
    </div>

    <div style="max-width:600px;margin:0 auto;">

        {{-- ── Récap frais ── --}}
        <div class="epcard" style="background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);margin-bottom:14px;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:11px;color:#0F6E56;margin-bottom:3px;">Paiement pour</div>
                    <div style="font-size:15px;font-weight:700;color:#085041;">
                        {{ $fraisApprenant->apprenant->nom }} {{ $fraisApprenant->apprenant->prenom }}
                        · {{ $fraisApprenant->apprenant->etablissement->nom ?? '' }}
                    </div>
                    <div style="font-size:12px;color:#1B9E75;">
                        {{ $fraisApprenant->categorieFrais->nom ?? 'Frais scolaires' }} — {{ $fraisApprenant->annee_scolaire }}
                    </div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:26px;font-weight:700;color:#085041;">{{ number_format($resteAPayer, 0, ',', ' ') }}</div>
                    <div style="font-size:11px;color:#0F6E56;">FCFA restant</div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('payeur.paiement.initier', $fraisApprenant) }}">
            @csrf

            {{-- ── Option de paiement ── --}}
            <div class="seclbl">Option de paiement</div>
            <div style="display:flex;gap:10px;margin-bottom:16px;">
                <label id="opt1" style="flex:1;padding:12px;border:2px solid var(--ep-teal);border-radius:var(--radius-md);cursor:pointer;background:var(--ep-teal-lt);text-align:center;display:block;">
                    <input type="radio" name="type_paiement" value="integral" checked style="display:none;" onclick="selOpt(1)">
                    <div style="font-size:11px;font-weight:700;color:#0F6E56;">Paiement intégral</div>
                    <div style="font-size:18px;font-weight:700;color:#085041;">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</div>
                </label>
                @if($fraisApprenant->categorieFrais->fractionnable ?? false)
                    <label id="opt2" style="flex:1;padding:12px;border:1px solid #ddd;border-radius:var(--radius-md);cursor:pointer;text-align:center;display:block;">
                        <input type="radio" name="type_paiement" value="tranche" style="display:none;" onclick="selOpt(2)">
                        <div style="font-size:11px;font-weight:700;color:#888;">Tranche 1/{{ $fraisApprenant->categorieFrais->nb_tranches_max ?? 2 }}</div>
                        <div style="font-size:18px;font-weight:700;">{{ number_format($resteAPayer / ($fraisApprenant->categorieFrais->nb_tranches_max ?? 2), 0, ',', ' ') }} FCFA</div>
                    </label>
                @endif
            </div>

            {{-- ── Moyen de paiement ── --}}
            <div class="seclbl">Moyen de paiement</div>
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
                <label id="pm3" style="flex:1;padding:12px;border:1px solid #ddd;border-radius:var(--radius-md);cursor:pointer;text-align:center;display:block;">
                    <input type="radio" name="mode_paiement" value="carte" style="display:none;" onclick="selPay(3)">
                    <div style="font-size:12px;font-weight:700;color:#185FA5;">Carte</div>
                    <div style="font-size:11px;">Visa / MC</div>
                </label>
            </div>

            <div class="lbl">Numéro de téléphone (Mobile Money)</div>
            <input type="text" name="telephone_paiement" value="{{ old('telephone_paiement', Auth::user()->telephone) }}" class="inp" placeholder="6XX XXX XXX" required>
            @error('telephone_paiement') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror

            <div style="background:var(--ep-gold-lt);border-radius:var(--radius-md);padding:12px;margin-bottom:16px;font-size:12px;color:#854F0B;">
                Vous recevrez une notification USSD sur votre téléphone pour confirmer le paiement.
            </div>

            <div style="font-size:13px;color:#888;margin-bottom:6px;display:flex;justify-content:space-between;">
                <span>Montant</span>
                <span style="font-weight:600;color:#333;" id="recap-montant">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
            </div>
            <div style="font-size:13px;color:#888;margin-bottom:12px;display:flex;justify-content:space-between;">
                <span>Frais de transaction</span>
                <span style="font-weight:600;color:var(--ep-teal);">Offerts</span>
            </div>
            <div style="border-top:1px solid #eee;padding-top:12px;margin-bottom:18px;display:flex;justify-content:space-between;">
                <span style="font-size:15px;font-weight:700;">Total à payer</span>
                <span style="font-size:22px;font-weight:700;color:var(--ep-teal);" id="recap-total">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
            </div>

            <button type="submit" class="btn-p">Confirmer et payer →</button>
        </form>
    </div>

@endsection

@push('scripts')
<script>
function selOpt(n) {
    [1,2].forEach(i => {
        const el = document.getElementById('opt'+i);
        if (!el) return;
        el.style.border = i===n ? '2px solid var(--ep-teal)' : '1px solid #ddd';
        el.style.background = i===n ? 'var(--ep-teal-lt)' : '#fff';
    });
}
function selPay(n) {
    [1,2,3].forEach(i => {
        const el = document.getElementById('pm'+i);
        if (!el) return;
        el.style.border = i===n ? '2px solid var(--ep-teal)' : '1px solid #ddd';
        el.style.background = i===n ? 'var(--ep-teal-lt)' : '#fff';
    });
}
</script>
@endpush
