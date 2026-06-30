<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
<style>
body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:0;}
.wrap{max-width:480px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;}
.hd{background:#0B2545;padding:20px 24px;}
.logo{font-size:20px;font-weight:700;color:#fff;}
.logo span{color:#5DCAA5;}
.bd{padding:28px;}
.code{background:#0B2545;color:#5DCAA5;font-size:32px;font-weight:700;letter-spacing:10px;
      text-align:center;padding:20px;border-radius:10px;font-family:monospace;margin:20px 0;}
.ft{padding:16px;text-align:center;font-size:11px;color:#aaa;border-top:1px solid #eee;}
</style></head><body>
<div class="wrap">
  <div class="hd"><div class="logo">Edu<span>Pay</span> Cameroun</div></div>
  <div class="bd">
    <p style="font-size:16px;font-weight:700;color:#0B2545;margin-bottom:8px;">Réinitialisation de mot de passe</p>
    <p style="font-size:13px;color:#555;margin-bottom:16px;">
      Bonjour <strong>{{ $admin->prenom }}</strong>,<br>
      Voici votre code de réinitialisation. Il expire dans <strong>10 minutes</strong>.
    </p>
    <div class="code">{{ $code }}</div>
    <p style="font-size:12px;color:#888;">
      Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
      Votre mot de passe ne sera pas modifié.
    </p>
  </div>
  <div class="ft">© 2026 EduPay Cameroun · Accès Super Admin · TLS 1.3</div>
</div>
</body></html>
