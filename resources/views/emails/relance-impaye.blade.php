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
        .info-row { display:flex; justify-content:space-between; font-size:13px; padding:8px 0; border-bottom:1px solid #f0f0f0; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:600; color:#333; }
        .reste { color:#D94040; font-size:18px; font-weight:800; }
        .cta { display:block; text-align:center; background:#0D9E75; color:#fff; font-size:14px; font-weight:600; padding:12px 20px; border-radius:8px; text-decoration:none; margin:24px 0; }
        .warning { background:#FFFBEB; border-left:4px solid #E8A020; border-radius:6px; padding:14px 16px; margin:20px 0; }
        .warning p { margin:0; font-size:12px; color:#92400E; line-height:1.6; }
        .footer { padding:18px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span> Cameroun</div>
            <div class="header-sub">{{ __('payeur.em_relance_header_sub') }}</div>
        </div>
        <div class="body">
            <div class="title">{{ __('payeur.em_relance_titre') }}</div>
            <div class="text">
                {!! __('payeur.em_relance_bonjour', ['nom' => $apprenant->prenom.' '.$apprenant->nom]) !!}
                {{ __('payeur.em_relance_intro') }}
            </div>

            <div style="background:#f8f9fa;border-radius:8px;padding:14px 16px;margin-bottom:20px;">
                <div class="info-row">
                    <span class="info-label">{{ __('payeur.em_relance_apprenant') }}</span>
                    <span class="info-val">{{ $apprenant->prenom }} {{ $apprenant->nom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('payeur.em_relance_motif') }}</span>
                    <span class="info-val">{{ $categorieFraisNom }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('payeur.em_relance_reste') }}</span>
                    <span class="info-val reste">{{ number_format($resteAPayer, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>

            <a href="{{ config('app.url') }}/connexion" class="cta">{{ __('payeur.em_relance_btn') }}</a>

            <div class="warning">
                <p>{{ __('payeur.em_relance_aide') }}</p>
            </div>
        </div>
        <div class="footer">
            {{ __('auth.em_footer_plateforme') }}<br/>
            © {{ date('Y') }} · {{ __('auth.em_otp_footer_securite') }}
        </div>
    </div>
</body>
</html>
