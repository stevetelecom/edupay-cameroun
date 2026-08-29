@php
    $profilBadge = match($payeur->profil) {
        'parent'   => 'bg-green-100 text-green-800',
        'eleve'    => 'bg-yellow-100 text-yellow-800',
        'etudiant' => 'bg-gray-100 text-gray-700',
        default    => 'bg-gray-100 text-gray-600',
    };
    $profilLabel = match($payeur->profil) {
        'parent'   => __('admin.profil_parent'),
        'eleve'    => __('admin.profil_eleve'),
        'etudiant' => __('admin.profil_etudiant'),
        default    => ucfirst($payeur->profil),
    };
@endphp

<div class="space-y-5">

    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-14 h-14 rounded-full bg-[#0B2545] text-white flex items-center justify-center text-lg font-bold shrink-0">
                {{ $payeur->initiales }}
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-bold text-gray-900 break-words">{{ $payeur->nom_complet }}</h3>
                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $profilBadge }}">{{ $profilLabel }}</span>
            </div>
        </div>
        <span class="text-xs px-2.5 py-1 rounded-full font-medium shrink-0 {{ $payeur->suspendu ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
            {{ $payeur->suspendu ? 'Suspendu' : 'Actif' }}
        </span>
    </div>

    @if($payeur->suspendu)
    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm">
        <div class="font-semibold text-red-800 mb-1">{{ __('admin.compte_suspendu') }}</div>
        <div class="text-red-700 text-xs">{{ $payeur->suspendu_raison ?? __('admin.aucune_raison_precisee') }}</div>
        <div class="text-red-500 text-xs mt-1">{{ __('admin.depuis') }} {{ $payeur->suspendu_at?->format('d/m/Y à H:i') ?? '—' }}</div>
    </div>
    @endif

    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.coordonnees') }}</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('messages.telephone') }}</div>
                <div class="font-medium text-gray-800 break-words">{{ $payeur->telephone ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.email') }}</div>
                <div class="font-medium text-gray-800 break-all">{{ $payeur->email ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('messages.ville') }}</div>
                <div class="font-medium text-gray-800 break-words">{{ $payeur->ville ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.quartier') }}</div>
                <div class="font-medium text-gray-800 break-words">{{ $payeur->quartier ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.activite') }}</h4>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-[#E0F5EE] rounded-lg p-3">
                <div class="text-xl font-bold text-[#0D9E75]">{{ $payeur->apprenants_count }}</div>
                <div class="text-xs text-[#085041] mt-0.5">{{ __('admin.enfants_rattaches') }}</div>
            </div>
            <div class="bg-[#FEF3DC] rounded-lg p-3">
                <div class="text-xl font-bold text-[#854F0B]">{{ $totalPaiements }}</div>
                <div class="text-xs text-[#854F0B] mt-0.5">{{ __('messages.paiements') }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-lg font-bold text-gray-700">{{ number_format($montantTotalPaye, 0, ',', ' ') }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.fcfa_payes') }}</div>
            </div>
        </div>
    </div>

    @if($payeur->apprenants->isNotEmpty())
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.enfants_apprenants_rattaches') }}</h4>
        <div class="space-y-2">
            @foreach($payeur->apprenants as $apprenant)
            <div class="bg-gray-50 rounded-lg p-3 flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="font-medium text-gray-800 text-sm break-words">{{ $apprenant->prenom }} {{ $apprenant->nom }}</div>
                    <div class="text-xs text-gray-400 break-words">{{ $apprenant->etablissement->nom ?? '—' }} · {{ ucfirst($apprenant->pivot->lien ?? '—') }}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100">
        <span>{{ __('admin.inscrit_le') }} {{ $payeur->created_at->format('d/m/Y') }}</span>
        <span>{{ __('admin.mis_a_jour') }} {{ $payeur->updated_at->diffForHumans() }}</span>
    </div>
</div>
