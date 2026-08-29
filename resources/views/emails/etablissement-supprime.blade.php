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
        .badge { display:inline-flex; align-items:center; gap:8px; background:#FEE2E2; border:1.5px solid #DC2626; border-radius:99px; padding:8px 18px; margin-bottom:20px; }
        .badge span { font-size:13px; font-weight:700; color:#991B1B; }
        .title { font-size:18px; font-weight:700; color:#0B2545; margin-bottom:8px; }
        .text { font-size:13px; color:#555; line-height:1.7; margin-bottom:16px; }
        .info-box { background:#f8f9fa; border-radius:10px; padding:16px; margin:20px 0; }
        .info-row { display:flex; justify-content:space-between; font-size:12px; padding:5px 0; border-bottom:1px solid #f0f0f0; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:600; color:#333; text-align:right; max-width:60%; word-break:break-word; }
        .warning-box { background:#FEE2E2; border-left:4px solid #DC2626; border-radius:0 8px 8px 0; padding:14px 16px; margin:16px 0; font-size:13px; color:#991B1B; line-height:1.6; }
        .contact-box { background:#EFF6FF; border-radius:10px; padding:16px; margin:20px 0; font-size:13px; color:#1E40AF; line-height:1.7; }
        .footer { padding:18px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span> Cameroun</div>
            <div class="header-sub">{{ __('etablissement.em_header_plateforme') }}</div>
        </div>
        <div class="body">
            <div class="badge"><span>{{ __('etablissement.em_supprime_badge') }}</span></div>
            <div class="title">{!! __('etablissement.em_bonjour_prenom', ['prenom' => $responsable->prenom]) !!}</div>
            <div class="text">
                {!! __('etablissement.em_supprime_texte', ['etab' => $nomEtablissement]) !!}
                {{ __('etablissement.em_supprime_acces_desactives') }}
            </div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">{{ __('messages.etablissement') }}</span>
                    <span class="info-val">{{ $nomEtablissement }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('etablissement.statut') }}</span>
                    <span class="info-val" style="color:#DC2626;">❌ {{ __('etablissement.em_supprime') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('etablissement.date') }}</span>
                    <span class="info-val">{{ now()->format('d/m/Y à H:i') }}</span>
                </div>
            </div>
            <div class="warning-box">
                ⚠️ {{ __('etablissement.em_supprime_irreversible') }}
            </div>
            <div class="contact-box">
                📧 {{ __('etablissement.em_supprime_contact') }}
            </div>
        </div>
        <div class="footer">
            {{ __('etablissement.em_footer_plateforme') }}<br/>
            © {{ date('Y') }} · {{ __('etablissement.em_footer_notif_admin') }}
        </div>
    </div>
</body>
</html>
