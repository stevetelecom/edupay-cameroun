@php
    $niveauStyle = match($log->niveau) {
        "CRITICAL" => "background:#fee2e2;color:#b91c1c;",
        "WARNING"  => "background:#fef9c3;color:#ca8a04;",
        "INFO"     => "background:#dcfce7;color:#166534;",
        default    => "background:#f3f4f6;color:#555;",
    };
@endphp

<div class="space-y-4">
    <div style="display:flex;align-items:start;justify-content:space-between;">
        <div>
            <div style="font-size:15px;font-weight:700;color:#111;">{{ $log->action }}</div>
            <div style="font-size:12px;color:#888;">{{ $log->created_at->format("d/m/Y a H:i:s") }}</div>
        </div>
        <span style="font-size:11px;font-weight:600;padding:3px 10px;border-radius:20px;{{ $niveauStyle }}">
            {{ $log->niveau }}
        </span>
    </div>

    <div style="background:#f9fafb;border-radius:8px;padding:12px;">
        <div style="font-size:11px;color:#999;margin-bottom:4px;">Detail</div>
        <div style="font-size:13px;color:#333;line-height:1.5;">{{ $log->detail ?? "—" }}</div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">Adresse IP</div>
            <div style="font-size:13px;font-weight:600;font-family:monospace;color:#111;">{{ $log->ip_address ?? "—" }}</div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">Acteur</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ class_basename($log->acteur_type) }} #{{ $log->acteur_id ?? "anonyme" }}
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;grid-column:span 2;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">User Agent</div>
            <div style="font-size:11px;color:#555;word-break:break-all;">{{ $log->user_agent ?? "—" }}</div>
        </div>
    </div>

    @if($log->donnees_avant || $log->donnees_apres)
    <div class="grid grid-cols-2 gap-3">
        @if($log->donnees_avant)
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#b91c1c;margin-bottom:6px;font-weight:600;">Avant</div>
            <pre style="font-size:11px;color:#333;white-space:pre-wrap;margin:0;">{{ json_encode($log->donnees_avant, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif
        @if($log->donnees_apres)
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#166534;margin-bottom:6px;font-weight:600;">Apres</div>
            <pre style="font-size:11px;color:#333;white-space:pre-wrap;margin:0;">{{ json_encode($log->donnees_apres, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif
    </div>
    @endif
</div>
