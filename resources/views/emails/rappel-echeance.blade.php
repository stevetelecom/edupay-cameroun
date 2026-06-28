<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rappel échéance — EduPay</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:0; -webkit-text-size-adjust:100%; }
    .wrapper { width:100%; background:#f5f5f5; padding:16px 8px; }
    .container { max-width:520px; margin:0 auto; background:#fff;
                 border-radius:12px; overflow:hidden; border:1px solid #e0e0e0; }
    .header { background:#0B2545; padding:20px 24px; }
    .logo { font-size:22px; font-weight:bold; color:#fff; }
    .logo span { color:#5DCAA5; }
    .body { padding:24px 20px; }
    .title { font-size:17px; font-weight:bold; color:#E8A020; margin-bottom:14px; }
    .text { font-size:14px; color:#555; line-height:1.6; margin-bottom:14px; }
    .countdown { background:#0B2545; border-radius:10px; padding:20px;
                 text-align:center; margin:16px 0; }
    .days { font-size:48px; font-weight:700; color:#5DCAA5; line-height:1; }
    .days-label { font-size:13px; color:rgba(255,255,255,0.6); margin-top:6px; }
    .info-box { background:#FEF3DC; border-radius:8px; padding:14px 16px;
                margin:16px 0; border-left:4px solid #E8A020; }
    .info-row { display:flex; justify-content:space-between; align-items:flex-start;
                padding:8px 0; border-bottom:1px solid rgba(0,0,0,0.06); font-size:13px; gap:8px; }
    .info-row:last-child { border-bottom:none; }
    .info-label { color:#888; flex-shrink:0; }
    .info-value { font-weight:bold; color:#8B5E10; text-align:right; word-break:break-word; }
    .btn-wrap { text-align:center; margin:20px 0; }
    .btn { display:inline-block; background:#0D9E75; color:#fff;
           text-decoration:none; padding:13px 28px; border-radius:8px;
           font-size:14px; font-weight:600; }
    .tip { background:#f0f9f5; border-radius:8px; padding:14px;
           font-size:13px; color:#085041; margin:16px 0; }
    .footer { background:#f8f9fa; padding:16px 20px; text-align:center;
              font-size:11px; color:#aaa; border-top:1px solid #eee; }
    @media only screen and (max-width:480px) {
      .wrapper { padding:8px 0; }
      .container { border-radius:0; border-left:none; border-right:none; }
      .body { padding:20px 16px; }
      .days { font-size:40px; }
      .info-row { flex-direction:column; gap:2px; }
      .info-value { text-align:left; }
      .btn { display:block; text-align:center; padding:14px; }
    }
  </style>
</head>
<body>
<div class="wrapper">
<div class="container">

  <div class="header">
    <div class="logo">Edu<span>Pay</span></div>
  </div>

  <div class="body">
    <div class="title">⏰ Rappel — Échéance dans 5 jours</div>

    <div class="text">
      Bonjour <strong>{{ $apprenant->parents->first()?->prenom ?? 'Parent' }}</strong>,<br>
      Une échéance approche pour <strong>{{ $apprenant->nom }} {{ $apprenant->prenom }}</strong>.
    </div>

    <div class="countdown">
      <div class="days">J-5</div>
      <div class="days-label">jours restants avant l'échéance</div>
    </div>

    <div class="info-box">
      <div class="info-row">
        <span class="info-label">Apprenant</span>
        <span class="info-value">{{ $apprenant->nom }} {{ $apprenant->prenom }}</span>
      </div>
      <div class="info-row">
        <span class="info-label">Classe</span>
        <span class="info-value">{{ $apprenant->classe }}</span>
      </div>
      <div class="info-row">
        <span class="info-label">Type de frais</span>
        <span class="info-value">{{ $categorieFraisNom }}</span>
      </div>
      <div class="info-row">
        <span class="info-label">Reste à payer</span>
        <span class="info-value">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
      </div>
      <div class="info-row">
        <span class="info-label">Date limite</span>
        <span class="info-value">{{ $dateEcheance }}</span>
      </div>
    </div>

    <div class="btn-wrap">
      <a href="{{ config('app.url') }}/payeur/dashboard" class="btn">
        Payer maintenant sur EduPay
      </a>
    </div>

    <div class="tip">
      <strong>💡 Modes de paiement :</strong><br>
      MTN MoMo &nbsp;·&nbsp; Orange Money &nbsp;·&nbsp; Carte bancaire
    </div>

    <div class="text" style="color:#999; font-size:12px; margin-top:20px;">
      Cordialement,<br><strong>L'équipe EduPay Cameroun</strong>
    </div>
  </div>

  <div class="footer">
    © 2026 EduPay Cameroun — Tous droits réservés.<br>
    Vous recevez cet email car vous avez activé les rappels d'échéances.
  </div>

</div>
</div>
</body>
</html>
