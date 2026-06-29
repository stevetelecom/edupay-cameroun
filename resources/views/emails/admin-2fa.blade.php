<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f1f3f5; margin:0; padding:0; }
        .container { max-width:480px; margin:30px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .header { background:#0B2545; padding:24px 28px; text-align:center; }
        .logo { font-size:22px; font-weight:800; color:#fff; }
        .logo span { color:#5DCAA5; }
        .header-sub { font-size:12px; color:rgba(255,255,255,.5); margin-top:4px; }
        .body { padding:32px 28px; }
        .title { font-size:18px; font-weight:700; color:#0B2545; margin-bottom:6px; }
        .text { font-size:13px; color:#555; line-height:1.7; margin-bottom:20px; }
        .code-box { background:#E0F5EE; border:2px solid #0D9E75; border-radius:12px; padding:24px; text-align:center; margin:24px 0; }
        .code { font-size:48px; font-weight:800; letter-spacing:12px; color:#0B2545; font-family:'Courier New',monospace; }
        .expires { font-size:12px; color:#0D9E75; margin-top:8px; font-weight:600; }
        .warning { background:#FFFBEB; border-left:4px solid #E8A020; border-radius:6px; padding:14px 16px; margin:20px 0; }
        .warning p { margin:0; font-size:12px; color:#92400E; line-height:1.6; }
        .info-row { display:flex; justify-content:space-between; font-size:12px; padding:6px 0; border-bottom:1px solid #f0f0f0; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:600; color:#333; }
        .footer { padding:18px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span> Cameroun</div>
            <div class="header-sub">Espace Super Administrateur · Sécurisé</div>
        </div>
        <div class="body">
            <div class="title">🔐 Code de vérification 2FA</div>
            <div class="text">
                Bonjour <strong>{{ $admin->prenom }} {{ $admin->nom }}</strong>,<br/>
                Une tentative de connexion à votre espace Super Admin a été détectée.
                Voici votre code de vérification à usage unique :
            </div>

            <div class="code-box">
                <div class="code">{{ $otpCode }}</div>
                <div class="expires">⏱ Code valide pendant 5 minutes</div>
            </div>

            <div style="background:#f8f9fa;border-radius:8px;padding:14px 16px;margin-bottom:20px;">
                <div class="info-row">
                    <span class="info-label">Compte</span>
                    <span class="info-val">{{ $admin->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-val">{{ now()->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">URL</span>
                    <span class="info-val">{{ config('app.url') }}/admin-ep2026/login</span>
                </div>
            </div>

            <div class="warning">
                <p><strong>⚠️ Sécurité :</strong> Si vous n'êtes pas à l'origine de cette connexion,
                ignorez cet email et changez immédiatement votre mot de passe.
                Ce code ne doit jamais être partagé.</p>
            </div>
        </div>
        <div class="footer">
            EduPay Cameroun — Plateforme de paiement des frais scolaires<br/>
            © {{ date('Y') }} · Authentification 2FA obligatoire · TLS 1.3
        </div>
    </div>
</body>
</html>
