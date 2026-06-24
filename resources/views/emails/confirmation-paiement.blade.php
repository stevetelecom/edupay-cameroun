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
        .title { font-size: 18px; font-weight: bold; color: #0D9E75; margin-bottom: 16px; }
        .text { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 16px; }
        .info-box { background: #f8f9fa; border-radius: 8px; padding: 16px 20px; margin: 20px 0; border-left: 4px solid #0D9E75; }
        .info-box .row { margin-bottom: 12px; font-size: 13px; }
        .info-box .label { color: #888; display: block; margin-bottom: 2px; }
        .info-box .value { font-weight: bold; color: #0D9E75; font-family: monospace; font-size: 15px; }
        .footer { padding: 18px 28px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #f0f0f0; }
        .highlight { color: #0D9E75; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span></div>
        </div>

        <div class="body">
            <div class="title">✓ Paiement confirmé</div>

            <div class="text">
                Bonjour {{ $paiement->user->prenom ?? 'Utilisateur' }},
            </div>

            <div class="text">
                Votre paiement a été <span class="highlight">correctement validé</span>. Merci d'avoir utilisé EduPay Cameroun!
            </div>

            <div class="info-box">
                <div class="row">
                    <span class="label">Enfant / Apprenant</span>
                    <span class="value">{{ $paiement->apprenant->nom }} {{ $paiement->apprenant->prenom }}</span>
                </div>
                <div class="row">
                    <span class="label">Type de frais</span>
                    <span class="value">{{ $paiement->fraisApprenant->categorieFrais->nom ?? 'Frais' }}</span>
                </div>
                <div class="row">
                    <span class="label">Montant payé</span>
                    <span class="value">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</span>
                </div>
                <div class="row">
                    <span class="label">Référence</span>
                    <span class="value">{{ $paiement->reference }}</span>
                </div>
                <div class="row">
                    <span class="label">Mode de paiement</span>
                    <span class="value">
                        @switch($paiement->mode_paiement)
                            @case('mtn_momo') MTN MoMo @break
                            @case('orange_money') Orange Money @break
                            @case('carte') Carte Bancaire @break
                            @default {{ ucfirst($paiement->mode_paiement) }}
                        @endswitch
                    </span>
                </div>
                <div class="row">
                    <span class="label">Date de validation</span>
                    <span class="value">{{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y à H:i') : 'Aujourd\'hui' }}</span>
                </div>
            </div>

            <div class="text">
                Vous pouvez consulter votre reçu PDF et l'historique de vos paiements dans votre espace personnel EduPay.
            </div>

            <div style="background: #e8f5f0; border-radius: 8px; padding: 14px; margin: 20px 0; font-size: 13px; color: #085041;">
                <strong>💡 Besoin d'aide ?</strong><br>
                Contactez le support EduPay : support@edupay-cameroun.cm ou +237 6xx xxx xxx
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
