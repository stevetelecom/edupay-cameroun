<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Paiement non abouti</title>
</head>
<body style="margin:0;padding:0;background:#f1f3f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f3f5;padding:30px 0;">
<tr><td align="center">
<table width="480" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

<tr><td style="background:#0B2545;padding:20px 28px;">
<span style="color:#ffffff;font-size:18px;font-weight:700;">Edu<span style="color:#5DCAA5;">Pay</span> Cameroun</span>
</td></tr>

<tr><td style="padding:28px;">

<div style="width:48px;height:48px;border-radius:50%;background:#FBEAEA;color:#D94040;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;margin-bottom:16px;">!</div>

<div style="font-size:17px;font-weight:700;color:#1a1a2e;margin-bottom:8px;">Paiement non abouti</div>

<p style="font-size:13px;color:#555;line-height:1.6;margin:0 0 18px;">
Bonjour {{ $paiement->user->prenom ?? '' }},<br><br>
Votre tentative de paiement pour <strong>{{ $paiement->apprenant->prenom ?? '' }} {{ $paiement->apprenant->nom ?? '' }}</strong>
n'a pas pu être confirmée par l'opérateur Mobile Money.
</p>

<table width="100%" cellpadding="8" cellspacing="0" style="background:#f8f9fa;border-radius:8px;font-size:13px;color:#333;margin-bottom:18px;">
<tr><td style="color:#888;">Référence</td><td align="right"><strong>{{ $paiement->reference }}</strong></td></tr>
<tr><td style="color:#888;">Catégorie</td><td align="right">{{ $paiement->fraisApprenant->categorieFrais->nom ?? '—' }}</td></tr>
<tr><td style="color:#888;">Montant</td><td align="right"><strong>{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</strong></td></tr>
<tr><td style="color:#888;">Moyen</td><td align="right">{{ $paiement->mode_paiement === 'mtn_momo' ? 'MTN Mobile Money' : 'Orange Money' }}</td></tr>
@if($raison)
<tr><td style="color:#888;">Motif</td><td align="right" style="color:#D94040;">{{ $raison }}</td></tr>
@endif
</table>

<p style="font-size:12px;color:#888;line-height:1.6;margin:0 0 20px;">
Vérifiez votre solde Mobile Money puis réessayez depuis l'application EduPay.
Si le montant a tout de même été débité de votre compte, il sera automatiquement régularisé — vous n'avez rien à faire de plus.
</p>

<a href="{{ config('app.url') }}/espace/historique" style="display:inline-block;background:#0D9E75;color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;">
Voir mon historique
</a>

</td></tr>

<tr><td style="padding:16px 28px;background:#f8f9fa;font-size:11px;color:#999;text-align:center;">
EduPay Cameroun — Paiement scolaire simplifié
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
