<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 0; padding: 20px; }
    .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0D9E75; padding-bottom: 14px; }
    .logo { font-size: 22px; font-weight: 700; color: #0B2545; }
    .logo span { color: #0D9E75; }
    .titre { font-size: 14px; font-weight: 700; margin-top: 6px; color: #333; }
    .meta { font-size: 10px; color: #888; margin-top: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th { background: #0B2545; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
    td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 10px; }
    tr:nth-child(even) td { background: #f9fafb; }
    .valide    { color: #0D9E75; font-weight: 700; }
    .en_attente{ color: #E8A020; font-weight: 600; }
    .echoue    { color: #D94040; font-weight: 600; }
    .rembourse { color: #185FA5; font-weight: 600; }
    .total-box { margin-top: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 12px 16px; text-align: right; }
    .total-box .lbl { font-size: 11px; color: #555; }
    .total-box .val { font-size: 16px; font-weight: 800; color: #0D9E75; margin-top: 2px; }
    .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #aaa; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

<div class="header">
    <div class="logo">Edu<span>Pay</span> Cameroun</div>
    <div class="titre">{{ __('payeur.pdf_hist_titre') }}</div>
    <div class="meta">
        {{ $user->prenom ?? '' }} {{ $user->nom ?? '' }} &nbsp;·&nbsp;
        {{ $user->email ?? '' }} &nbsp;·&nbsp;
        {{ __('payeur.pdf_generer_le', ['date' => now()->format('d/m/Y à H:i')]) }}
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>{{ __('payeur.hist_reference') }}</th>
            <th>{{ __('payeur.hist_enfant') }}</th>
            <th>{{ __('payeur.hist_categorie') }}</th>
            <th>{{ __('payeur.hist_montant') }}</th>
            <th>{{ __('payeur.hist_moyen') }}</th>
            <th>{{ __('payeur.hist_date') }}</th>
            <th>{{ __('payeur.hist_statut') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($paiements as $p)
        @php
            $statutLabel = match($p->statut) {
                'valide'     => __('payeur.statut_valide'),
                'en_attente' => __('payeur.statut_en_attente'),
                'echoue'     => __('payeur.statut_echoue'),
                'rembourse'  => __('payeur.statut_rembourse'),
                default      => $p->statut,
            };
            $moyenLabel = match($p->mode_paiement) {
                'mtn_momo'     => __('etablissement.mt_mtn'),
                'orange_money' => __('etablissement.mt_orange'),
                'carte'        => __('etablissement.carte'),
                default        => $p->mode_paiement,
            };
        @endphp
        <tr>
            <td style="font-family:monospace;color:#888;">{{ $p->reference }}</td>
            <td style="font-weight:600;">{{ $p->apprenant->nom ?? '—' }} {{ $p->apprenant->prenom ?? '' }}</td>
            <td>{{ $p->fraisApprenant->categorieFrais->nom ?? '—' }}</td>
            <td style="font-weight:700;">{{ number_format($p->montant, 0, ',', ' ') }} FCFA</td>
            <td>{{ $moyenLabel }}</td>
            <td>{{ $p->date_paiement ? \Carbon\Carbon::parse($p->date_paiement)->format('d/m/Y') : '—' }}</td>
            <td class="{{ $p->statut }}">{{ $statutLabel }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;color:#999;padding:20px;">{{ __('payeur.pdf_aucun_paiement') }}</td>
        </tr>
        @endforelse
    </tbody>
</table>

@php
    $totalValide = $paiements->where('statut', 'valide')->sum('montant');
    $nbTotal     = $paiements->count();
@endphp

<div class="total-box">
    <div class="lbl">{{ __('payeur.pdf_total_valide', ['nb' => $nbTotal]) }}</div>
    <div class="val">{{ number_format($totalValide, 0, ',', ' ') }} FCFA</div>
</div>

<div class="footer">
    {{ __('payeur.pdf_footer_hist') }}
</div>

</body>
</html>