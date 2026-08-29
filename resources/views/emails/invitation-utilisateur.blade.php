<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f3f5; margin: 0; padding: 0; }
        .container { max-width: 480px; margin: 30px auto; background: #fff; border-radius: 12px; overflow: hidden; }
        .header { background: #0B2545; padding: 22px 28px; }
        .header .logo { font-size: 20px; font-weight: bold; color: #fff; }
        .header .logo span { color: #5DCAA5; }
        .body { padding: 28px; }
        .title { font-size: 18px; font-weight: bold; color: #1a1a2e; margin-bottom: 8px; }
        .text { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 16px; }
        .credentials-box { background: #f8f9fa; border-radius: 8px; padding: 16px 20px; margin: 20px 0; }
        .credentials-box .row { margin-bottom: 8px; font-size: 13px; }
        .credentials-box .label { color: #888; }
        .credentials-box .value { font-weight: bold; color: #1a1a2e; font-family: monospace; }
        .btn { display: inline-block; background: #0D9E75; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; margin-top: 10px; }
        .footer { padding: 18px 28px; font-size: 11px; color: #999; text-align: center; border-top: 1px solid #f0f0f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Edu<span>Pay</span> Cameroun</div>
        </div>
        <div class="body">
            <div class="title">{{ __('etablissement.em_invitation_titre', ['prenom' => $utilisateur->prenom]) }}</div>
            <div class="text">
                {!! __('etablissement.em_invitation_intro', [
                    'etab' => $utilisateur->etablissement->nom ?? __('etablissement.em_invitation_etab_fallback'),
                    'role' => $roleLabel
                ]) !!}
            </div>

            <div class="credentials-box">
                <div class="row"><span class="label">{{ __('etablissement.email') }} :</span> <span class="value">{{ $utilisateur->email }}</span></div>
                <div class="row"><span class="label">{{ __('etablissement.em_label_mdp_temporaire') }}</span> <span class="value">{{ $motDePasseTemporaire }}</span></div>
            </div>

            <div class="text">
                {{ __('etablissement.em_invitation_connectez') }}
            </div>

            <a href="{{ route('login') }}" class="btn">{{ __('etablissement.em_btn_invitation') }} →</a>
        </div>
        <div class="footer">
            {{ __('etablissement.em_footer_plateforme') }}<br/>
            {{ __('etablissement.em_invitation_footer_note') }}
        </div>
    </div>
</body>
</html>
