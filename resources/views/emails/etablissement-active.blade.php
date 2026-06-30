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
        .badge-actif { display:inline-flex; align-items:center; gap:8px; background:#ECFDF5; border:1.5px solid #0D9E75; border-radius:99px; padding:8px 18px; margin-bottom:20px; }
        .badge-actif span { font-size:13px; font-weight:700; color:#065F46; }
        .title { font-size:18px; font-weight:700; color:#0B2545; margin-bottom:8px; }
        .text { font-size:13px; color:#555; line-height:1.7; margin-bottom:16px; }
        .info-box { background:#f8f9fa; border-radius:10px; padding:16px; margin:20px 0; }
        .info-row { display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #f0f0f0; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:600; color:#333; text-align:right; max-width:60%; word-break:break-word; }
        .btn { display:inline-block; background:#0D9E75; color:#fff; text-decoration:none; padding:13px 28px; border-radius:8px; font-size:14px; font-weight:700; margin-top:8px; }
        .steps { background:#E0F5EE; border-radius:10px; padding:16px; margin:20px 0; }
        .steps-title { font-size:12px; font-weight:700; color:#085041; margin-bottom:10px; text-transform:uppercase; letter-spacing:.05em; }
        .step { display:flex; align-items:flex-start; gap:10px; margin-bottom:8px; font-size:12px; color:#065F46; line-height:1.5; }
        .step-num { width:20px; height:20px; background:#0D9E75; color:#fff; border-radius:50%; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
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
            <div class="badge-actif">
                <span>✅ Établissement activé</span>
            </div>
            <div class="title">Félicitations, {{ $responsable->prenom }} !</div>
            <div class="text">
                Votre dossier a été examiné et validé par l'équipe EduPay Cameroun.
                Votre établissement <strong>{{ $etablissement->nom }}</strong> est maintenant
                <strong style="color:#0D9E75;">actif sur la plateforme</strong> et peut commencer
                à collecter les frais de scolarité en ligne.
            </div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Établissement</span>
                    <span class="info-val">{{ $etablissement->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Code établissement</span>
                    <span class="info-val" style="color:#0D9E75;font-family:monospace;">{{ $etablissement->code_etablissement }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ville</span>
                    <span class="info-val">{{ $etablissement->ville }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Statut</span>
                    <span class="info-val" style="color:#0D9E75;">✅ Actif</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Activé le</span>
                    <span class="info-val">{{ now()->format('d/m/Y à H:i') }}</span>
                </div>
            </div>
            <div class="steps">
                <div class="steps-title">🚀 Prochaines étapes</div>
                <div class="step"><div class="step-num">1</div><div>Connectez-vous à votre back-office avec vos identifiants</div></div>
                <div class="step"><div class="step-num">2</div><div>Configurez vos catégories de frais et échéanciers</div></div>
                <div class="step"><div class="step-num">3</div><div>Importez votre liste d'apprenants</div></div>
                <div class="step"><div class="step-num">4</div><div>Partagez votre code <strong>{{ $etablissement->code_etablissement }}</strong> aux parents</div></div>
            </div>
            <a href="{{ config('app.url') }}/connexion" class="btn">
                Accéder à mon back-office →
            </a>
        </div>
        <div class="footer">
            EduPay Cameroun — Plateforme de paiement des frais scolaires<br/>
            © {{ date('Y') }} · Cet email vous a été envoyé car votre établissement vient d'être activé.
        </div>
    </div>
</body>
</html>
