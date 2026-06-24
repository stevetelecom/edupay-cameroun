<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        @page { margin: 40px 50px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            margin: 0;
        }
        .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #0D9E75;
            padding-bottom: 16px;
            margin-bottom: 30px;
        }
        .header .logo {
            display: table-cell;
            vertical-align: middle;
            font-size: 24px;
            font-weight: bold;
            color: #0B2545;
        }
        .header .logo span { color: #0D9E75; }
        .header .ref {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #999;
        }
        .title {
            text-align: center;
            font-size: 19px;
            font-weight: bold;
            color: #0B2545;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .subtitle {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-bottom: 36px;
        }
        .body-text {
            font-size: 13px;
            line-height: 1.9;
            text-align: justify;
            margin-bottom: 30px;
        }
        .body-text strong { color: #0B2545; }
        .stamp-box {
            background: #E0F5EE;
            border: 1px solid #9FE1CB;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 26px 0;
            text-align: center;
        }
        .stamp-box .pct { font-size: 26px; font-weight: bold; color: #085041; }
        .stamp-box .lbl { font-size: 11px; color: #085041; margin-top: 2px; }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 26px 0;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 12px;
        }
        .info-table td:first-child { color: #888; width: 45%; }
        .info-table td:last-child { font-weight: bold; }
        .signature-block {
            margin-top: 60px;
            display: table;
            width: 100%;
        }
        .signature-block .left { display: table-cell; width: 50%; }
        .signature-block .right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: bottom;
        }
        .signature-block .sign-line {
            margin-top: 50px;
            border-top: 1px solid #333;
            width: 180px;
            font-size: 10px;
            color: #888;
            padding-top: 4px;
            display: inline-block;
        }
        .footer {
            margin-top: 50px;
            padding-top: 14px;
            border-top: 1px solid #eee;
            font-size: 9px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo">Edu<span>Pay</span> Cameroun</div>
        <div class="ref">Réf. CERT-{{ now()->format('Y') }}-{{ str_pad($apprenant->id, 5, '0', STR_PAD_LEFT) }}</div>
    </div>

    <div class="title">Attestation de paiement à jour</div>
    <div class="subtitle">Année scolaire {{ $anneeScolaire }}</div>

    <div class="body-text">
        EduPay Cameroun atteste par la présente que les frais scolaires de
        <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong>,
        inscrit(e) en classe de <strong>{{ $apprenant->classe }}</strong>
        à <strong>{{ $apprenant->etablissement->nom ?? '—' }}</strong>,
        sont à jour à la date d'émission du présent document, selon les paiements
        enregistrés sur la plateforme EduPay.
    </div>

    <div class="stamp-box">
        <div class="pct">{{ $pourcentage }}% réglé</div>
        <div class="lbl">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA payés sur {{ number_format($montantTotal, 0, ',', ' ') }} FCFA dus</div>
    </div>

    <table class="info-table">
        <tr>
            <td>Apprenant</td>
            <td>{{ $apprenant->prenom }} {{ $apprenant->nom }}</td>
        </tr>
        <tr>
            <td>Matricule</td>
            <td>{{ $apprenant->matricule ?? '—' }}</td>
        </tr>
        <tr>
            <td>Établissement</td>
            <td>{{ $apprenant->etablissement->nom ?? '—' }}@if($apprenant->etablissement?->ville), {{ $apprenant->etablissement->ville }}@endif</td>
        </tr>
        <tr>
            <td>Classe</td>
            <td>{{ $apprenant->classe }}</td>
        </tr>
        <tr>
            <td>Année scolaire</td>
            <td>{{ $anneeScolaire }}</td>
        </tr>
    </table>

    <div class="signature-block">
        <div class="left">
            <div class="sign-line">Cachet EduPay Cameroun</div>
        </div>
        <div class="right">
            Fait à Yaoundé, le {{ now()->format('d/m/Y') }}
            <div class="sign-line">Signature électronique</div>
        </div>
    </div>

    <div class="footer">
        EduPay Cameroun — Plateforme de paiement des frais scolaires<br/>
        Document généré automatiquement et valable sans signature manuscrite. Vérifiable via la référence ci-dessus.
    </div>

</body>
</html>
