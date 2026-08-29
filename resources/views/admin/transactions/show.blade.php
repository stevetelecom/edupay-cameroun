@php
    $statutClasses = match($paiement->statut) {
        "valide"     => "bg-green-100 text-green-800",
        "en_attente" => "bg-yellow-100 text-yellow-800",
        "echoue"     => "bg-red-100 text-red-800",
        "rembourse"  => "bg-blue-100 text-blue-800",
        default      => "bg-gray-100 text-gray-600",
    };
    $statutLabel = match($paiement->statut) {
        "valide"     => __("admin.valide"),
        "en_attente" => __("admin.en_attente"),
        "echoue"     => __("admin.echoue"),
        "rembourse"  => __("admin.rembourse"),
        default      => $paiement->statut,
    };
@endphp

<div class="space-y-4">
    <div class="flex items-start justify-between">
        <div>
            <div style="font-size:16px;font-weight:700;color:#111;">{{ $paiement->reference }}</div>
            <div style="font-size:12px;color:#888;">{{ $paiement->created_at->format("d/m/Y a H:i") }}</div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statutClasses }}">{{ $statutLabel }}</span>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.ecole") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $paiement->fraisApprenant?->categorieFrais?->etablissement?->nom ?? "—" }}
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.apprenant") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $paiement->apprenant ? $paiement->apprenant->nom." ".$paiement->apprenant->prenom : "—" }}
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.montant") }}</div>
            <div style="font-size:18px;font-weight:700;color:#0D9E75;">
                {{ number_format($paiement->montant, 0, ",", " ") }} FCFA
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.operateur") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $paiement->operateur ?? "—" }}
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __('messages.telephone') }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ $paiement->telephone_paiement ?? "—" }}
            </div>
        </div>
        <div style="background:#f9fafb;border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:#999;margin-bottom:4px;">{{ __("admin.type") }}</div>
            <div style="font-size:13px;font-weight:600;color:#111;">
                {{ ucfirst($paiement->type_paiement ?? "—") }}
            </div>
        </div>
    </div>

    @if($paiement->pay_token)
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;">
        <div style="font-size:11px;color:#166534;margin-bottom:2px;">{{ __("admin.token_aangaraa") }}</div>
        <div style="font-size:12px;font-family:monospace;color:#166534;">{{ $paiement->pay_token }}</div>
    </div>
    @endif
</div>
