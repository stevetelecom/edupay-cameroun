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
        <h1>EduPay Cameroun — Rapport financier</h1>
        <p>{{ Auth::user()->etablissement->nom ?? '' }} — Année {{ $anneeScolaire }} — Généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>
    <div class="goldline"></div>

    <div class="content">
        <table class="kpis">
            <tr>
                <td><span class="val">{{ number_format($totalEncaisseAnnee, 0, ',', ' ') }}</span>FCFA encaissés</td>
                <td><span class="val">{{ number_format($totalImpayeAnnee, 0, ',', ' ') }}</span>FCFA impayés</td>
                <td><span class="val">{{ $tauxRecouvrement }}%</span>Taux de recouvrement</td>
                <td><span class="val">{{ $nbApprenants }}</span>Apprenants suivis</td>
            </tr>
        </table>

        <div class="section-title">Répartition par moyen de paiement</div>
        <table>
            <tr><th>Moyen de paiement</th><th>Pourcentage</th></tr>
            @forelse($repartitionMoyens as $m)
                <tr>
                    <td>{{ match($m['mode']) { 'mtn_momo' => 'MTN Mobile Money', 'orange_money' => 'Orange Money', 'carte' => 'Carte bancaire', default => $m['mode'] } }}</td>
                    <td>{{ $m['pourcentage'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="2">Aucune donnée disponible.</td></tr>
            @endforelse
        </table>

        <div class="section-title">Recouvrement par classe</div>
        <table>
            <tr><th>Classe</th><th>Apprenants</th><th>Taux de recouvrement</th></tr>
            @forelse($repartitionClasses as $c)
                <tr><td>{{ $c['nom'] }}</td><td>{{ $c['nb_apprenants'] }}</td><td>{{ $c['taux'] }}%</td></tr>
            @empty
                <tr><td colspan="3">Aucune donnée disponible.</td></tr>
            @endforelse
        </table>

        <div class="footer">Document généré automatiquement par la plateforme EduPay Cameroun — Réf. CDC-EDUPAY-CM-2026-001</div>
    </div>
</body>
</html>
