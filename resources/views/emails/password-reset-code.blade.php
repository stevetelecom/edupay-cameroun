<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        .header { background: linear-gradient(135deg, #0B2545 0%, #0D9E75 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .content { background: white; padding: 30px; border-radius: 0 0 8px 8px; }
        .code-box { background: #f0f8ff; border-left: 4px solid #0D9E75; padding: 20px; border-radius: 6px; text-align: center; margin: 25px 0; }
        .code-box .code { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #0B2545; font-family: 'Courier New', monospace; }
        .code-box .expires { color: #666; font-size: 12px; margin-top: 10px; }
        .warning { background: #fff8dc; border-left: 4px solid #e8a020; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .warning p { margin: 0; color: #333; font-size: 13px; }
        .footer { text-align: center; color: #999; font-size: 11px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .btn { display: inline-block; background: #0D9E75; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('auth.reset_titre') }}</h1>
            <p style="margin: 10px 0 0; opacity: 0.9;">EduPay Cameroun</p>
        </div>
        
        <div class="content">
            {!! __('auth.em_bonjour_nom', ['nom' => $userName]) !!}
            
            <p>{{ __('auth.em_reset_demande') }}</p>
            
            <div class="code-box">
                <div class="code">{{ $code }}</div>
                <div class="expires">{!! __('auth.em_reset_code_expire', ['duree' => $expiresIn]) !!}</div>
            </div>

            <p>{{ __('auth.em_reset_entrez_code') }}</p>

            <div class="warning">
                <p>{!! __('auth.em_reset_attention') !!}</p>
            </div>

            <p style="color: #666; font-size: 13px; margin-top: 25px;">
                <strong>{{ __('auth.em_reset_questions') }}</strong> {{ __('auth.em_reset_contactez_support') }}
                <a href="mailto:edupay@mekontso.gsi2026.com" style="color: #0D9E75;">edupay@mekontso.gsi2026.com</a>
            </p>
        </div>

        <div class="footer">
            <p>{{ __('auth.em_footer_tous_droits') }}</p>
            <p>{{ __('auth.em_reset_footer_sent') }}</p>
        </div>
    </div>
</body>
</html>
