@extends('layouts.payeur')

@section('title', __('payeur.recu_titre'))

@section('content')

{{-- En-tête de page v3 : grande pastille époxy + titre Poppins (style dashboard) --}}
<div class="ep-entete ep-entete-page" style="margin-bottom:16px;">
    <div class="ep-ico or ep-ico-entete"><span class="material-symbols-outlined">receipt_long</span></div>
    <div>
        <h3 style="margin:0;">{{ __('payeur.recu_titre') }}</h3>
        <div class="ep-sous-titre" style="margin-top:2px;">{{ __('payeur.recu_soustitre') }}</div>
    </div>
</div>

<div class="seclbl" style="margin-top:0;">{{ __('payeur.recus_pdf') }}</div>
<div class="epcard" style="margin-bottom:16px;">
    @forelse($recus as $paiement)
        <div class="row">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                {{-- Icône document : Material Symbols (grammaire v3, plus de SVG inline) --}}
                <span class="ep-ico rouge" style="width:36px;height:36px;border-radius:10px;flex-shrink:0;">
                    <span class="material-symbols-outlined" style="font-size:20px;">description</span>
                </span>
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:600;">Reçu_{{ $paiement->reference }}.pdf</div>
                    <div style="font-size:11px;color:#888;">
                        {{ $paiement->fraisApprenant->categorieFrais->nom ?? __('payeur.paiement') }}
                        — {{ $paiement->apprenant->prenom ?? '' }}
                        · {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
            <a href="{{ route('payeur.recus.telecharger', $paiement) }}" class="btn-o" style="width:auto;padding:6px 12px;font-size:11px;flex-shrink:0;">
                {{ __('payeur.recu_telecharger') }}
            </a>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            {{ __('payeur.recu_aucun') }}
        </div>
    @endforelse
</div>

<div class="seclbl">{{ __('payeur.recu_certificats_titre') }}</div>
<div class="g2">
    @forelse($apprenants as $apprenant)
        @php
            $aFrais = $apprenant->frais->isNotEmpty();
            $anneeC = $aFrais ? ($apprenant->frais->first()->annee_scolaire ?? '') : '';
            $fraisC = $aFrais && $anneeC ? $apprenant->frais->where('annee_scolaire', $anneeC) : collect();
            $totalC = $fraisC->sum('montant_total');
            $payeC  = $fraisC->sum('montant_paye');
            $resteC = $totalC - $payeC;
            $pourcentageC = $totalC > 0 ? round(($payeC / $totalC) * 100) : 0;
            $aJour = $aFrais && $resteC <= 0;
        @endphp
        <div class="epcard" style="border-left:3px solid {{ $aJour ? 'var(--ep-gold)' : '#ccc' }};{{ $aFrais && $aJour ? '' : 'opacity:.6;' }}">
            <div class="ep-frais-titre" style="margin-bottom:4px;">
                {{ $apprenant->prenom }} {{ $apprenant->nom }} — {{ $apprenant->etablissement->nom ?? '—' }}
            </div>
            <div style="font-size:11.5px;color:#888;margin-bottom:10px;font-weight:500;">
                @if(!$aFrais)
                    {{ __('payeur.recu_aucun_frais') }}
                @elseif($aJour)
                    {{ __('payeur.recu_attestation_a_jour', ['pct' => $pourcentageC]) }}
                @else
                    {{ __('payeur.recu_indisponible_impaye', ['montant' => number_format($resteC, 0, ',', ' ')]) }}
                @endif
            </div>
            @if($aJour)
                <a href="{{ route('payeur.recus.certificat', $apprenant) }}" class="btn-o" style="font-size:12px;display:inline-block;text-decoration:none;">
                    {{ __('payeur.recu_generer_certificat') }}
                </a>
            @elseif(!$aFrais)
                <button class="btn-o" style="font-size:12px;" disabled>{{ __('payeur.recu_aucun_frais') }}</button>
            @else
                <button class="btn-o" style="font-size:12px;" disabled>{{ __('payeur.recu_regulariser') }}</button>
            @endif
        </div>
    @empty
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;grid-column:1/-1;">
            {{ __('payeur.recu_aucun_enfant') }}
        </div>
    @endforelse
</div>

@endsection
