<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>{{ __('admin.em_nouvelle_rec_titre') }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f3f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f3f5;padding:30px 0;">
<tr><td align="center">
<table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;">

<tr><td style="background:#0B2545;padding:20px 28px;">
<span style="color:#ffffff;font-size:18px;font-weight:700;">Edu<span style="color:#5DCAA5;">Pay</span> Cameroun</span>
</td></tr>

<tr><td style="padding:28px;">

<div style="width:48px;height:48px;border-radius:50%;background:#FBEAEA;color:#D94040;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;margin-bottom:16px;">!</div>

<div style="font-size:17px;font-weight:700;color:#1a1a2e;margin-bottom:8px;">{{ __('admin.em_nouvelle_rec_titre') }}</div>

<p style="font-size:13px;color:#555;line-height:1.6;margin:0 0 18px;">
{!! __('admin.em_nouvelle_rec_intro', ['nom' => e($reclamation->user->nom ?? __('admin.em_utilisateur_fallback'))]) !!}
</p>

<table width="100%" cellpadding="8" cellspacing="0" style="background:#f8f9fa;border-radius:8px;font-size:13px;color:#333;margin-bottom:18px;">
<tr><td style="color:#888;width:38%;">{{ __('admin.ticket_col') }}</td><td align="right"><strong>{{ $reclamation->numero_ticket }}</strong></td></tr>
<tr><td style="color:#888;">{{ __('admin.sujet_lbl') }}</td><td align="right"><strong>{{ $reclamation->sujet }}</strong></td></tr>
<tr><td style="color:#888;">{{ __('admin.demandeur_lbl') }}</td><td align="right">{{ trim(($reclamation->user->prenom ?? '').' '.($reclamation->user->nom ?? '')) ?: '—' }}</td></tr>
<tr><td style="color:#888;">{{ __('public.email_label') }}</td><td align="right">{{ $reclamation->user->email ?? '—' }}</td></tr>
<tr><td style="color:#888;">{{ __('admin.date_col') }}</td><td align="right">{{ $reclamation->created_at->format('d/m/Y à H:i') }}</td></tr>
@if($reclamation->paiement)
<tr><td style="color:#888;">{{ __('admin.transaction_liee') }}</td><td align="right">{{ $reclamation->paiement->reference ?? '—' }}</td></tr>
@endif
</table>

<div style="background:#f8f9fa;border-radius:8px;padding:14px;font-size:13px;color:#333;line-height:1.7;margin-bottom:18px;">
{!! nl2br(e($reclamation->description)) !!}
</div>

<p style="font-size:12px;color:#888;line-height:1.6;margin:0 0 20px;">
{{ __('admin.em_nouvelle_rec_replyto') }}
</p>

<a href="{{ config('app.url') }}/admin-ep2026/reclamations" style="display:inline-block;background:#0D9E75;color:#ffffff;text-decoration:none;padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;">
{{ __('admin.em_voir_reclamations') }}
</a>

</td></tr>

<tr><td style="padding:16px 28px;background:#f8f9fa;font-size:11px;color:#999;text-align:center;">
{{ __('admin.em_footer_scolaire_simplifie') }}
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
