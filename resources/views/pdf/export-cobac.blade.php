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
    .conformite { background:#E6F0FB; border-left:3px solid #185FA5; padding:8px 12px; font-size:10px; color:#0B2545; margin-bottom:16px; }
    .anomalie-card td { text-align:center; background:#FBEAEA; }
    .anomalie-card .val { color:#D94040; }
</style>
</head>
<body>
    <div class="header">
        <h1>{{ __('admin.pdf_cobac_titre') }}</h1>
        <p>{!! __('admin.pdf_generer_le', ['periode' => $periodeLabel, 'date' => now()->format('d/m/Y à H:i')]) !!}</p>
    </div>
    <div class="goldline"></div>

    <div class="content">
        <div class="conformite">{{ __('admin.pdf_cobac_conformite') }}</div>

        <table class="kpis">
            <tr>
                <td><span class="val">{{ number_format($volumeTotal, 0, ',', ' ') }}</span>{{ __('admin.pdf_kpi_volume_trimestre') }}</td>
                <td><span class="val">{{ number_format($commissionsTotal, 0, ',', ' ') }}</span>{{ __('admin.pdf_kpi_commissions_percues') }}</td>
                <td><span class="val">{{ $nbTransactions }}</span>{{ __('admin.pdf_kpi_transactions_validees') }}</td>
                <td><span class="val">{{ $etablissementsActifs }}</span>{{ __('admin.pdf_kpi_etablissements_actifs') }}</td>
            </tr>
        </table>

        <div class="section-title">{{ __('admin.pdf_section_repartition_operateur') }}</div>
        <table>
            <tr><th>{{ __('admin.pdf_col_operateur') }}</th><th>{{ __('admin.pdf_col_nb_transactions') }}</th><th>{{ __('admin.pdf_col_volume') }}</th></tr>
            @forelse ($repartitionOperateur as $op)
                <tr>
                    <td>{{ str_replace('_Cameroon', '', $op->operateur) }}</td>
                    <td>{{ $op->nb }}</td>
                    <td>{{ number_format($op->volume, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('admin.pdf_aucune_transaction_periode') }}</td></tr>
            @endforelse
        </table>

        <div class="section-title">{{ __('admin.pdf_section_anomalies') }}</div>
        <table class="anomalie-card">
            <tr>
                <td><span class="val">{{ $nbEchecs }}</span>{{ __('admin.pdf_kpi_paiements_echoues') }}</td>
                <td><span class="val">{{ $nbRemboursements }}</span>{{ __('admin.pdf_kpi_remboursements') }}</td>
                <td><span class="val">{{ $nbReclamations }}</span>{{ __('admin.pdf_kpi_reclamations_recues') }}</td>
                <td><span class="val">{{ $nbReclamationsOuvertes }}</span>{{ __('admin.pdf_kpi_reclamations_ouvertes') }}</td>
            </tr>
        </table>

        <div class="section-title">{{ __('admin.pdf_section_top5') }}</div>
        <table>
            <tr><th>{{ __('admin.pdf_col_etablissement') }}</th><th>{{ __('admin.pdf_col_volume') }}</th><th>{{ __('admin.pdf_col_commission') }}</th></tr>
            @forelse ($topEtablissements as $te)
                <tr>
                    <td>{{ $te->etablissement->nom ?? '—' }}</td>
                    <td>{{ number_format($te->volume, 0, ',', ' ') }}</td>
                    <td>{{ number_format($te->commission, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">{{ __('admin.pdf_aucune_donnee') }}</td></tr>
            @endforelse
        </table>

        <div class="footer">{{ __('admin.pdf_footer_auto') }}</div>
    </div>
</body>
</html>
