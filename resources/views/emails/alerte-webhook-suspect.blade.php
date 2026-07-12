<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f1f3f5; margin:0; padding:0; }
        .container { max-width:560px; margin:30px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .header { background:#7F1D1D; padding:20px 28px; }
        .header-title { font-size:16px; font-weight:800; color:#fff; }
        .body { padding:24px 28px; }
        .alert-box { background:#FEE2E2; border-left:4px solid #DC2626; border-radius:0 8px 8px 0; padding:14px 16px; margin-bottom:18px; font-size:13px; color:#991B1B; line-height:1.6; }
        .info-box { background:#f8f9fa; border-radius:10px; padding:16px; margin-bottom:16px; font-family:monospace; font-size:12px; }
        .info-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #eee; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:700; color:#333; text-align:right; word-break:break-all; max-width:60%; }
        .payload-box { background:#1e1e1e; color:#d4d4d4; border-radius:8px; padding:14px; font-family:monospace; font-size:11px; overflow-x:auto; margin-top:14px; white-space:pre-wrap; word-break:break-all; }
        .footer { padding:16px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">🚨 Alerte sécurité — Webhook paiement suspect</div>
        </div>
        <div class="body">
            <div class="alert-box">
                Un appel au webhook <strong>/webhook/aangaraapay</strong> a annoncé un statut différent
                de celui confirmé par revérification API. Le système a automatiquement ignoré la valeur
                non fiable et s'est basé uniquement sur la vérification serveur-à-serveur, donc
                <strong>aucun paiement frauduleux n'a été validé</strong>. Ceci est une alerte préventive
                à examiner.
            </div>

            <div class="info-box">
                <div class="info-row"><span class="info-label">Référence paiement</span><span class="info-val">{{ $reference }}</span></div>
                <div class="info-row"><span class="info-label">Statut annoncé (non fiable)</span><span class="info-val">{{ $statutAnnonce ?? 'absent' }}</span></div>
                <div class="info-row"><span class="info-label">Statut réel (revérifié API)</span><span class="info-val">{{ $statutReel }}</span></div>
                <div class="info-row"><span class="info-label">Adresse IP appelante</span><span class="info-val">{{ $ip }}</span></div>
                <div class="info-row"><span class="info-label">Date/heure</span><span class="info-val">{{ now()->format('d/m/Y H:i:s') }}</span></div>
            </div>

            <div style="font-size:12px;font-weight:700;color:#555;margin-bottom:6px;">Payload complet reçu :</div>
            <div class="payload-box">{{ json_encode($payloadComplet, JSON_PRETTY_PRINT) }}</div>
        </div>
        <div class="footer">
            EduPay Cameroun — Système de sécurité automatisé<br/>
            Consultez les logs complets : <code>storage/logs/laravel.log</code>
        </div>
    </div>
</body>
</html>
