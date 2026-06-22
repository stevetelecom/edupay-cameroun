@extends('layouts.payeur')

@section('title', 'Reçus & Certificats')

@section('content')

<div style="font-size:17px;font-weight:700;margin-bottom:4px;">Reçus &amp; certificats</div>
<div style="font-size:12px;color:#888;margin-bottom:16px;">Téléchargez vos justificatifs de paiement et attestations de scolarité</div>

<div class="seclbl" style="margin-top:0;">Reçus PDF</div>
<div class="epcard" style="margin-bottom:16px;">
    @forelse($recus as $paiement)
        <div class="row">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--ep-red)" stroke-width="2" style="flex-shrink:0;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                </svg>
                <div style="min-width:0;">
                    <div style="font-size:13px;font-weight:600;">Reçu_{{ $paiement->reference }}.pdf</div>
                    <div style="font-size:11px;color:#888;">
                        {{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                        — {{ $paiement->apprenant->prenom ?? '' }}
                        · {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                    </div>
                </div>
            </div>
            <a href="{{ route('payeur.recus.telecharger', $paiement) }}" class="btn-o" style="width:auto;padding:6px 12px;font-size:11px;flex-shrink:0;">
                Télécharger
            </a>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            Aucun reçu disponible pour le moment. Vos reçus apparaîtront ici après chaque paiement validé.
        </div>
    @endforelse
</div>

<div class="seclbl">Certificats de scolarité</div>
<div class="g2">
    @forelse($apprenants as $apprenant)
        @php
            $totalC = $apprenant->frais->sum('montant_total');
            $payeC  = $apprenant->frais->sum('montant_paye');
            $resteC = $totalC - $payeC;
            $pourcentageC = $totalC > 0 ? round(($payeC / $totalC) * 100) : 0;
            $aJour = $resteC <= 0;
        @endphp
        <div class="epcard" style="border-left:3px solid {{ $aJour ? 'var(--ep-gold)' : '#ccc' }};{{ $aJour ? '' : 'opacity:.6;' }}">
            <div style="font-size:13px;font-weight:700;margin-bottom:4px;">
                {{ $apprenant->prenom }} {{ $apprenant->nom }} — {{ $apprenant->etablissement->nom ?? '—' }}
            </div>
            <div style="font-size:11px;color:#888;margin-bottom:10px;">
                @if($aJour)
                    Attestation de paiement à jour — {{ $pourcentageC }}% réglé
                @else
                    Indisponible — solde impayé ({{ number_format($resteC, 0, ',', ' ') }} FCFA restants)
                @endif
            </div>
            @if($aJour)
                <a href="{{ route('payeur.recus.certificat', $apprenant) }}" class="btn-o" style="font-size:12px;display:inline-block;text-decoration:none;">
                    Générer le certificat
                </a>
            @else
                <button class="btn-o" style="font-size:12px;" disabled>Régulariser pour générer</button>
            @endif
        </div>
    @empty
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;grid-column:1/-1;">
            Aucun enfant rattaché à votre compte pour le moment.
        </div>
    @endforelse
</div>

@endsection
