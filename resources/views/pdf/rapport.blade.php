<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #1a1a1a; }
    .header { background: #0B2545; color: #fff; padding: 16px 20px; }
    .header h1 { margin:0; font-size:18px; }
    .header p { margin:4px 0 0; font-size:11px; color:#cbd5e1; }
    .goldline { height:4px; background:#E8A020; }
    .content { padding: 20px; }
    table { width:100%; border-collapse: collapse; margin-bottom: 18px; }
    th, td { padding:6px 8px; border-bottom:1px solid #e5e5e5; text-align:left; font-size:11px; }
    th { background:#f5f6f7; font-size:10px; text-transform:uppercase; color:#666; }
    .kpis td { text-align:center; }
    .kpis .val { font-size:16px; font-weight:bold; display:block; }
    .section-title { font-size:13px; font-weight:bold; color:#0B2545; margin: 10px 0 6px; }
    .footer { font-size:9px; color:#888; text-align:center; margin-top: 30px; }
</style>
</head>
<body>
    <div class="header">
        <h1>EduPay Cameroun — {{ __('etablissement.pdf_rapport_titre') }}</h1>
        <p>{!! __('etablissement.pdf_rapport_soustitre', ['etab' => Auth::user()->etablissement->nom ?? '', 'annee' => $anneeScolaire, 'date' => now()->format('d/m/Y à H:i')]) !!}</p>
    </div>
    <div class="goldline"></div>

    <div class="content">
        <table class="kpis">
            <tr>
                <td><span class="val">{{ number_format($totalEncaisseAnnee, 0, ',', ' ') }}</span>{{ __('etablissement.fcfa_encaisse') }}</td>
                <td><span class="val">{{ number_format($totalImpayeAnnee, 0, ',', ' ') }}</span>FCFA {{ __('messages.impayes') }}</td>
                <td><span class="val">{{ $tauxRecouvrement }}%</span>{{ __('etablissement.taux_recouvrement') }}</td>
                <td><span class="val">{{ $nbApprenants }}</span>{{ __('etablissement.apprenants_suivis') }}</td>
            </tr>
        </table>

        <div class="section-title">{{ __('etablissement.pdf_section_moyen_paiement') }}</div>
        <table>
            <tr><th>{{ __('payeur.hist_moyen') }}</th><th>{{ __('etablissement.pdf_col_pourcentage') }}</th></tr>
            @forelse($repartitionMoyens as $m)
                <tr>
                    <td>{{ match($m['mode']) { 'mtn_momo' => __('etablissement.mt_mtn'), 'orange_money' => __('etablissement.mt_orange'), 'carte' => __('etablissement.carte'), default => $m['mode'] } }}</td>
                    <td>{{ $m['pourcentage'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="2">{{ __('admin.pdf_aucune_donnee') }}</td></tr>
            @endforelse
        </table>

        <div class="section-title">{{ __('etablissement.recouvrement_classe') }}</div>
        <table>
            <tr><th>{{ __('etablissement.classe') }}</th><th>{{ __('etablissement.apprenants') }}</th><th>{{ __('etablissement.taux_recouvrement') }}</th></tr>
            @forelse($repartitionClasses as $c)
                <tr><td>{{ $c['nom'] }}</td><td>{{ $c['nb_apprenants'] }}</td><td>{{ $c['taux'] }}%</td></tr>
            @empty
                <tr><td colspan="3">{{ __('admin.pdf_aucune_donnee') }}</td></tr>
            @endforelse
        </table>

        <div class="footer">{{ __('admin.pdf_footer_auto') }}</div>
    </div>
</body>
</html>
