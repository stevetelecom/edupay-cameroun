<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; }
        .header { background: #0B2545; padding: 22px 28px; }
        .header .logo { font-size: 20px; font-weight: bold; color: #fff; }
        .header .logo span { color: #5DCAA5; }
        .body { padding: 28px; }
        .title { font-size: 18px; font-weight: bold; color: #E74C3C; margin-bottom: 16px; }
        .text { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 16px; }
        .alert-box { background: #fff3cd; border-radius: 8px; padding: 16px 20px; margin: 20px 0; border-left: 4px solid #E74C3C; }
        .alert-box .row { margin-bottom: 12px; font-size: 13px; }
        .alert-box .label { color: #888; display: block; margin-bottom: 2px; }
        .alert-box .value { font-weight: bold; color: #E74C3C; font-family: monospace; font-size: 15px; }
        .footer { padding: 18px 28px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #f0f0f0; }
        .highlight { color: #E74C3C; font-weight: bold; }
        .btn { display: inline-block; background: #E74C3C; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-top: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span></div>
        </div>

        <div class="body">
            <div class="title">⚠️ Alerte : Paiement impayé</div>

            <div class="text">
                Bonjour {{ $apprenant->parents->first()?->prenom ?? 'Parent' }},
            </div>

            <div class="text">
                Un paiement <span class="highlight">n'a pas encore été effectué</span> pour <strong>{{ $apprenant->nom }} {{ $apprenant->prenom }}</strong>.
            </div>

            <div class="alert-box">
                <div class="row">
                    <span class="label">Apprenant</span>
                    <span class="value">{{ $apprenant->nom }} {{ $apprenant->prenom }}</span>
                </div>
                <div class="row">
                    <span class="label">Type de frais</span>
                    <span class="value">{{ $categorieFraisNom }}</span>
                </div>
                <div class="row">
                    <span class="label">Montant dû</span>
                    <span class="value">{{ number_format($montantDu, 0, ',', ' ') }} FCFA</span>
                </div>
                @if($dateEcheance)
                <div class="row">
                    <span class="label">Date limite</span>
                    <span class="value">{{ $dateEcheance }}</span>
                </div>
                @endif
            </div>

            <div class="text">
                Pour éviter des complications, nous vous recommandons de <span class="highlight">régler ce paiement dans les plus brefs délais</span> via l'application EduPay Cameroun.
            </div>

            <div class="text">
                <strong>Modes de paiement acceptés :</strong><br>
                • MTN MoMo<br>
                • Orange Money<br>
                • Carte bancaire<br>
            </div>

            <div style="text-align: center; margin: 24px 0;">
                <a href="https://edupay-cameroun.cm" class="btn">Payer maintenant</a>
            </div>

            <div style="background: #fce8e8; border-radius: 8px; padding: 14px; margin: 20px 0; font-size: 13px; color: #8b3a3a;">
                <strong>📞 Besoin d'aide ?</strong><br>
                Contactez l'établissement ou le support EduPay : support@edupay-cameroun.cm
            </div>

            <div class="text" style="margin-top: 24px; color: #999; font-size: 12px;">
                Cordialement,<br>
                <strong>L'équipe EduPay Cameroun</strong>
            </div>
        </div>

        <div class="footer">
            © 2026 EduPay Cameroun — Tous droits réservés.<br>
            Gestion intelligente des frais de scolarité.
        </div>
    </div>
</body>
</html>
