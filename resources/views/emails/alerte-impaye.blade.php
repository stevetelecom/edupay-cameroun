<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Alerte impayé — EduPay</title>
  <style>
    * { box-sizing:border-box; margin:0; padding:0; }
    body { font-family:Arial, sans-serif; background:#f5f5f5; margin:0; padding:0; -webkit-text-size-adjust:100%; }
    .wrapper { width:100%; background:#f5f5f5; padding:16px 8px; }
    .container { max-width:520px; margin:0 auto; background:#fff;
                 border-radius:12px; overflow:hidden; border:1px solid #e0e0e0; }
    .header { background:#0B2545; padding:20px 24px; }
    .logo { font-size:22px; font-weight:bold; color:#fff; }
    .logo span { color:#5DCAA5; }
    .body { padding:24px 20px; }
    .title { font-size:17px; font-weight:bold; color:#D94040; margin-bottom:14px; }
    .text { font-size:14px; color:#555; line-height:1.6; margin-bottom:14px; }
    .montant-block { background:#FBEAEA; border-radius:8px; padding:16px;
                     text-align:center; margin:16px 0; }
    .montant { font-size:28px; font-weight:700; color:#D94040; }
    .montant-label { font-size:12px; color:#9B2C2C; margin-top:4px; }
    .info-box { background:#fff3cd; border-radius:8px; padding:14px 16px;
                margin:16px 0; border-left:4px solid #D94040; }
    .info-row { display:flex; justify-content:space-between; align-items:flex-start;
                padding:8px 0; border-bottom:1px solid rgba(0,0,0,0.06); font-size:13px; gap:8px; }
    .info-row:last-child { border-bottom:none; }
    .info-label { color:#888; flex-shrink:0; }
    .info-value { font-weight:bold; color:#9B2C2C; text-align:right; word-break:break-word; }
    .modes { background:#f8f9fa; border-radius:8px; padding:14px 16px; margin:16px 0; font-size:13px; color:#555; }
    .btn-wrap { text-align:center; margin:20px 0; }
    .btn { display:inline-block; background:#D94040; color:#fff;
           text-decoration:none; padding:13px 28px; border-radius:8px;
           font-size:14px; font-weight:600; }
    .tip { background:#fce8e8; border-radius:8px; padding:14px;
           font-size:13px; color:#8b3a3a; margin:16px 0; }
    .footer { background:#f8f9fa; padding:16px 20px; text-align:center;
              font-size:11px; color:#aaa; border-top:1px solid #eee; }
    @media only screen and (max-width:480px) {
      .wrapper { padding:8px 0; }
      .container { border-radius:0; border-left:none; border-right:none; }
      .body { padding:20px 16px; }
      .montant { font-size:24px; }
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
    <div class="title">⚠️ Alerte — Paiement impayé</div>

    <div class="text">
      Bonjour <strong>{{ $apprenant->parents->first()?->prenom ?? 'Parent' }}</strong>,<br>
      Un paiement <strong style="color:#D94040;">n'a pas encore été effectué</strong>
      pour <strong>{{ $apprenant->nom }} {{ $apprenant->prenom }}</strong>.
    </div>

    <div class="montant-block">
      <div class="montant">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</div>
      <div class="montant-label">Montant dû</div>
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
      @if($dateEcheance)
      <div class="info-row">
        <span class="info-label">Date limite</span>
        <span class="info-value">{{ $dateEcheance }}</span>
      </div>
      @endif
    </div>

    <div class="modes">
      <strong>💳 Modes de paiement acceptés :</strong><br><br>
      • MTN MoMo<br>
      • Orange Money<br>
      • Carte bancaire
    </div>

    <div class="btn-wrap">
      <a href="{{ config('app.url') }}/payeur/dashboard" class="btn">
        Régulariser maintenant
      </a>
    </div>

    <div class="tip">
      <strong>📞 Besoin d'aide ?</strong><br>
      Contactez le support : <a href="mailto:edupay@mekontso.gsi2026.com" style="color:#D94040;">edupay@mekontso.gsi2026.com</a>
    </div>

    <div class="text" style="color:#999; font-size:12px; margin-top:20px;">
      Cordialement,<br><strong>L'équipe EduPay Cameroun</strong>
    </div>
  </div>

  <div class="footer">
    © 2026 EduPay Cameroun — Tous droits réservés.<br>
    Gestion intelligente des frais de scolarité.
  </div>

</div>
</div>
</body>
</html>
