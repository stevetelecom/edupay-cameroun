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
            <div class="header-sub">{{ __('admin.em_2fa_header_sub') }}</div>
        </div>
        <div class="body">
            <div class="title">{{ __('admin.em_2fa_titre') }}</div>
            <div class="text">
                {!! __('admin.em_2fa_bonjour', ['nom' => $admin->prenom.' '.$admin->nom]) !!}
                {{ __('admin.em_2fa_connexion_detectee') }}
                {{ __('admin.em_2fa_code_usage_unique') }}
            </div>

            <div class="code-box">
                <div class="code">{{ $otpCode }}</div>
                <div class="expires">{{ __('admin.em_2fa_code_expire') }}</div>
            </div>

            <div style="background:#f8f9fa;border-radius:8px;padding:14px 16px;margin-bottom:20px;">
                <div class="info-row">
                    <span class="info-label">{{ __('admin.em_label_compte') }}</span>
                    <span class="info-val">{{ $admin->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('admin.em_label_date') }}</span>
                    <span class="info-val">{{ now()->format('d/m/Y à H:i') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">URL</span>
                    <span class="info-val">{{ config('app.url') }}/admin-ep2026/login</span>
                </div>
            </div>

            <div class="warning">
                <p>{!! __('admin.em_2fa_securite_label') !!}
                {{ __('admin.em_2fa_securite_texte') }}
                {{ __('admin.em_2fa_code_confidentiel') }}</p>
            </div>
        </div>
        <div class="footer">
            {{ __('admin.em_footer_plateforme') }}<br/>
            © {{ date('Y') }} · {{ __('admin.em_2fa_footer_tls') }}
        </div>
    </div>
</body>
</html>
