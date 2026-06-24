@php
    $statutClasses = match($etablissement->statut) {
        "actif"      => "bg-green-100 text-green-800",
        "en_attente" => "bg-yellow-100 text-yellow-800",
        "suspendu"   => "bg-red-100 text-red-800",
        default      => "bg-gray-100 text-gray-600",
    };
@endphp

<div class="space-y-5">
    <div>
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-lg font-bold text-gray-900">{{ $etablissement->nom }}</h3>
                <p class="text-sm text-gray-400">{{ $etablissement->code_etablissement }}</p>
            </div>
            <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statutClasses }}">
                {{ ucfirst(str_replace("_", " ", $etablissement->statut)) }}
            </span>
        </div>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Type</div>
                <div class="font-medium text-gray-800">{{ ucfirst($etablissement->type ?? "—") }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Statut juridique</div>
                <div class="font-medium text-gray-800">{{ $etablissement->statut_juridique ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Localisation</div>
                <div class="font-medium text-gray-800">{{ $etablissement->ville }}, {{ $etablissement->region }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">N° agrement</div>
                <div class="font-medium text-gray-800">{{ $etablissement->numero_agrement ?? "—" }}</div>
            </div>
        </div>
    </div>
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Contact</h4>
        <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Telephone</div>
                <div class="font-medium text-gray-800">{{ $etablissement->telephone ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Email</div>
                <div class="font-medium text-gray-800">{{ $etablissement->email ?? "—" }}</div>
            </div>
        </div>
    </div>
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Activite</h4>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-[#E0F5EE] rounded-lg p-3">
                <div class="text-xl font-bold text-[#0D9E75]">{{ $etablissement->apprenants_count }}</div>
                <div class="text-xs text-[#085041] mt-0.5">Apprenants</div>
            </div>
            <div class="bg-[#FEF3DC] rounded-lg p-3">
                <div class="text-xl font-bold text-[#854F0B]">{{ $etablissement->commissions_count }}</div>
                <div class="text-xs text-[#854F0B] mt-0.5">Commissions</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xl font-bold text-gray-700">
                    {{ $etablissement->taux_commission ? number_format($etablissement->taux_commission * 100, 1)."%" : "2,5%" }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">Taux comm.</div>
            </div>
        </div>
    </div>
    <div class="flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100">
        <span>Inscrit le {{ $etablissement->created_at->format("d/m/Y") }}</span>
        <span>Mis a jour {{ $etablissement->updated_at->diffForHumans() }}</span>
    </div>
</div>
