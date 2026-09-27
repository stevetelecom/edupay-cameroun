<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background:#f1f3f5; margin:0; padding:0; }
        .container { max-width:560px; margin:30px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .header { background:#7F1D1D; padding:20px 28px; }
        .header-title { font-size:16px; font-weight:800; color:#fff; }
        .body { padding:24px 28px; }
        .alert-box { background:#FEE2E2; border-left:4px solid #DC2626; border-radius:0 8px 8px 0; padding:14px 16px; margin-bottom:18px; font-size:13px; color:#991B1B; line-height:1.6; }
        .warn-box { background:#FEF3C7; border-left:4px solid #D97706; border-radius:0 8px 8px 0; padding:14px 16px; margin-bottom:18px; font-size:13px; color:#92400E; line-height:1.6; }
        .info-box { background:#f8f9fa; border-radius:10px; padding:16px; margin-bottom:16px; font-family:monospace; font-size:12px; }
        .info-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #eee; }
        .info-row:last-child { border-bottom:none; }
        .info-label { color:#888; }
        .info-val { font-weight:700; color:#333; text-align:right; word-break:break-all; max-width:60%; }
        .footer { padding:16px 28px; font-size:11px; color:#999; text-align:center; border-top:1px solid #f0f0f0; background:#fafafa; }
        code { background:#eee; padding:1px 4px; border-radius:3px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-title">{{ __('admin.em_reversement_titre') }}</div>
        </div>
        <div class="body">
            <div class="alert-box">
                {!! __('admin.em_reversement_texte') !!}
            </div>

            <div class="warn-box">
                {!! __('admin.em_reversement_piete') !!}
            </div>

            <div class="info-box">
                <div class="info-row"><span class="info-label">{{ __('admin.em_reversement_commission') }}</span><span class="info-val">{{ $commissionId ?? '?' }}</span></div>
                <div class="info-row"><span class="info-label">{{ __('admin.em_reversement_paiement') }}</span><span class="info-val">{{ $paiementId ?? '?' }}</span></div>
                <div class="info-row"><span class="info-label">{{ __('admin.em_reversement_etablissement') }}</span><span class="info-val">{{ $etablissement ?? '?' }}</span></div>
                <div class="info-row"><span class="info-label">{{ __('admin.em_reversement_montant') }}</span><span class="info-val">{{ $montant !== null ? number_format($montant, 0, ',', ' ').' FCFA' : '?' }}</span></div>
                <div class="info-row"><span class="info-label">{{ __('admin.em_reversement_numero') }}</span><span class="info-val">{{ $numeroReversement ?? __('admin.em_absent') }}</span></div>
                <div class="info-row"><span class="info-label">{{ __('admin.em_webhook_date_heure') }}</span><span class="info-val">{{ now()->format('d/m/Y H:i:s') }}</span></div>
            </div>

            <div style="font-size:12px;font-weight:700;color:#555;margin-bottom:6px;">{{ __('admin.em_reversement_raison') }}</div>
            <div style="background:#f8f9fa;border-radius:8px;padding:14px;font-size:13px;line-height:1.6;color:#333;">{{ $raison }}</div>

            <div style="font-size:12px;color:#666;margin-top:18px;line-height:1.6;">
                {!! __('admin.em_reversement_deroulement') !!}
            </div>
        </div>
        <div class="footer">
            EduPay Cameroun — {{ __('admin.em_reversement_footer') }}<br/>
            {{ __('admin.em_webhook_logs') }} <code>storage/logs/laravel.log</code>
        </div>
    </div>
</body>
</html>
