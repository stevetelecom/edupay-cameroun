<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f1f3f5; margin:0; padding:0; }
        .container { max-width:520px; margin:30px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .header { background:#0B2545; padding:24px 28px; text-align:center; }
        .logo { font-size:22px; font-weight:800; color:#fff; }
        .logo span { color:#5DCAA5; }
        .header-sub { font-size:12px; color:rgba(255,255,255,.5); margin-top:4px; }
        .body { padding:32px 28px; }
        .badge { display:inline-flex; align-items:center; gap:8px; background:#FEF3DC; border:1.5px solid #E8A020; border-radius:99px; padding:8px 18px; margin-bottom:20px; }
        .badge span { font-size:13px; font-weight:700; color:#92400E; }
        .title { font-size:18px; font-weight:700; color:#0B2545; margin-bottom:8px; }
        .text { font-size:13px; color:#555; line-height:1.7; margin-bottom:16px; }
        .info-box { background:#f8f9fa; border-radius:10px; padding:16px; margin:20px 0; }
        .info-row { display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #f0f0f0; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:600; color:#333; text-align:right; max-width:60%; word-break:break-word; }
        .raison-box { background:#FEF3DC; border-left:4px solid #E8A020; border-radius:0 8px 8px 0; padding:14px 16px; margin:16px 0; font-size:13px; color:#92400E; line-height:1.6; }
        .contact-box { background:#EFF6FF; border-radius:10px; padding:16px; margin:20px 0; font-size:13px; color:#1E40AF; line-height:1.7; }
        .footer { padding:18px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span> Cameroun</div>
            <div class="header-sub">Plateforme de paiement des frais scolaires</div>
        </div>
        <div class="body">
            <div class="badge"><span>⚠️ Établissement suspendu</span></div>
            <div class="title">Bonjour {{ $responsable->prenom }},</div>
            <div class="text">
                Nous vous informons que votre établissement <strong>{{ $etablissement->nom }}</strong>
                a été <strong style="color:#E8A020;">temporairement suspendu</strong> sur la plateforme EduPay Cameroun.
                Pendant cette période, les paiements en ligne sont désactivés.
            </div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Établissement</span>
                    <span class="info-val">{{ $etablissement->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Code</span>
                    <span class="info-val" style="font-family:monospace;color:#E8A020;">{{ $etablissement->code_etablissement }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-val" style="color:#E8A020;">⚠️ Suspendu</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-val">{{ now()->format('d/m/Y à H:i') }}</span>
                </div>
            </div>
            @if($raison)
            <div class="raison-box">
                <strong>Motif de suspension :</strong><br/>{{ $raison }}
            </div>
            @endif
            <div class="contact-box">
                📧 Pour contester cette décision ou régulariser votre situation, contactez-nous à
                <strong>contact@mekontso.gsi2026.com</strong> en indiquant votre code établissement.
            </div>
        </div>
        <div class="footer">
            EduPay Cameroun — Plateforme de paiement des frais scolaires<br/>
            © {{ date('Y') }} · Notification administrative automatique.
        </div>
    </div>
</body>
</html>
