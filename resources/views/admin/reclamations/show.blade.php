@php
    $sc = match($reclamation->statut) {
        "ouvert"   => "bg-blue-100 text-blue-800",
        "en_cours" => "bg-yellow-100 text-yellow-800",
        "resolu"   => "bg-green-100 text-green-800",
        "rejete"   => "bg-red-100 text-red-800",
        default    => "bg-gray-100 text-gray-600",
    };
    $label = match($reclamation->statut) {
        "ouvert"   => __("admin.ouvert"),
        "en_cours" => __("admin.en_cours"),
        "resolu"   => __("admin.resolu"),
        "rejete"   => __("admin.rejete"),
        default    => $reclamation->statut,
    };
@endphp

<div class="space-y-4">
    <div style="display:flex;align-items:start;justify-content:space-between;">
        <div>
            <div style="font-size:15px;font-weight:700;color:#111;">{{ $reclamation->numero_ticket }}</div>
            <div style="font-size:12px;color:#888;">{{ $reclamation->created_at->format("d/m/Y a H:i") }}</div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $sc }}">{{ $label }}</span>
    </div>

    <div style="background:#f9fafb;border-radius:8px;padding:12px;">
        <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.sujet_lbl") }}</div>
        <div style="font-size:13px;font-weight:600;color:#111;">{{ $reclamation->sujet }}</div>
    </div>

    <div style="background:#f9fafb;border-radius:8px;padding:12px;">
        <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.description_lbl") }}</div>
        <div style="font-size:13px;color:#333;line-height:1.5;">{{ $reclamation->description }}</div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.demandeur_lbl") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $reclamation->user->prenom ?? "" }} {{ $reclamation->user->nom ?? "—" }}
            </div>
            <div style="font-size:11px;color:#888;">{{ $reclamation->user->email ?? "" }}</div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.transaction_liee") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $reclamation->paiement->reference ?? __("admin.aucun_lbl") }}
            </div>
            @if($reclamation->paiement)
            <div style="font-size:11px;color:#888;">{{ number_format($reclamation->paiement->montant, 0, ",", " ") }} FCFA</div>
            @endif
        </div>
    </div>

    @if($reclamation->reponse_admin)
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;">
        <div style="font-size:11px;color:#166534;margin-bottom:4px;font-weight:600;">{{ __("admin.reponse_admin") }}</div>
        <div style="font-size:13px;color:#166534;line-height:1.5;">{{ $reclamation->reponse_admin }}</div>
        @if($reclamation->resolu_le)
        <div style="font-size:11px;color:#4ade80;margin-top:6px;">{{ $reclamation->resolu_le->format("d/m/Y a H:i") }}</div>
        @endif
    </div>
    @endif
</div>
