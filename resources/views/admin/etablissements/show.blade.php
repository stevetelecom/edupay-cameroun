@php
    $statutClasses = match($etablissement->statut) {
        "actif"      => "bg-green-100 text-green-800",
        "en_attente" => "bg-yellow-100 text-yellow-800",
        "suspendu"   => "bg-red-100 text-red-800",
        default      => "bg-gray-100 text-gray-600",
    };
    $regionLabels = [
        'centre'=>'Centre','littoral'=>'Littoral','ouest'=>'Ouest','nord'=>'Nord',
        'adamaoua'=>'Adamaoua','est'=>'Est','sud'=>'Sud','sud_ouest'=>'Sud-Ouest',
        'nord_ouest'=>'Nord-Ouest','extreme_nord'=>'Extrême-Nord',
    ];
    $statutJuridiqueLabels = [
        'public'=>__('admin.statut_public'),'prive_laic'=>__('admin.statut_prive_laic'),'prive_catholique'=>__('admin.statut_prive_cath'),
        'prive_protestant'=>__('admin.statut_prive_prot'),'prive_islamique'=>__('admin.statut_prive_isl'),
    ];
    $nbElevesLabels = [
        'moins_100'=>__('admin.nb_moins_100'),'100_300'=>__('admin.nb_100_300'),'300_500'=>__('admin.nb_300_500'),
        '500_1000'=>__('admin.nb_500_1000'),'plus_1000'=>__('admin.nb_plus_1000'),
    ];
@endphp

<div style="display:grid;gap:20px;">

    {{-- ── En-tête avec logo ── --}}
    <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
        <div style="width:64px;height:64px;border-radius:12px;background:#f3f4f6;flex-shrink:0;overflow:hidden;display:flex;align-items:center;justify-content:center;border:1px solid #e5e7eb;">
            @if($etablissement->logo)
                <img src="{{ asset('storage/' . $etablissement->logo) }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;" />
            @else
                <span style="font-size:22px;font-weight:700;color:#9ca3af;">{{ Str::substr($etablissement->nom, 0, 1) }}</span>
            @endif
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <h3 style="font-size:17px;font-weight:700;color:#111;margin:0;">{{ $etablissement->nom }}</h3>
                <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $statutClasses }}">
                    {{ ucfirst(str_replace("_", " ", $etablissement->statut)) }}
                </span>
            </div>
            <p style="font-size:12px;color:#9ca3af;margin-top:2px;">{{ $etablissement->code_etablissement }}</p>
        </div>
    </div>

    {{-- ── Informations établissement ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.infos_etablissement') }}</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.type_col') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ ucfirst(str_replace('_',' ',$etablissement->type ?? "—")) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.statut_juridique') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ $statutJuridiqueLabels[$etablissement->statut_juridique] ?? $etablissement->statut_juridique ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.numero_agrement') }}</div>
                <div class="font-medium text-gray-800 text-sm wrap-break-word">{{ $etablissement->numero_agrement ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.effectif_declare') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ $nbElevesLabels[$etablissement->nb_eleves] ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.region') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ $regionLabels[$etablissement->region] ?? $etablissement->region }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.ville_quartier') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ $etablissement->ville }}{{ $etablissement->quartier ? ' — '.$etablissement->quartier : '' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.boite_postale') }}</div>
                <div class="font-medium text-gray-800 text-sm">{{ $etablissement->boite_postale ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.mobile_money_principal') }}</div>
                <div class="font-medium text-gray-800 text-sm">
                    @php
                        $mmLabels = ['mtn'=>'MTN MoMo','orange'=>'Orange Money','les_deux'=>'MTN + Orange'];
                    @endphp
                    {{ $mmLabels[$etablissement->mobile_money_principal] ?? "—" }}
                </div>
            </div>
        </div>
        @if($etablissement->description)
        <div class="bg-gray-50 rounded-lg p-3 mt-2">
            <div class="text-xs text-gray-400 mb-1">{{ __('admin.description_field') }}</div>
            <div class="font-medium text-gray-800 text-sm" style="white-space:pre-wrap;">{{ $etablissement->description }}</div>
        </div>
        @endif
    </div>

    {{-- ── Contact établissement ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.contact_etablissement') }}</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('messages.telephone') }}</div>
                <div class="font-medium text-gray-800 text-sm wrap-break-word">{{ $etablissement->telephone ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.email') }}</div>
                <div class="font-medium text-gray-800 text-sm wrap-break-word">{{ $etablissement->email ?? "—" }}</div>
            </div>
            @if($etablissement->site_web)
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">{{ __('admin.site_web') }}</div>
                <a href="{{ $etablissement->site_web }}" target="_blank" class="font-medium text-[#0D9E75] text-sm wrap-break-word">{{ $etablissement->site_web }}</a>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Document d'agrément ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.document_agrement') }}</h4>
        @if($etablissement->document_agrement)
            <a href="{{ asset('storage/' . $etablissement->document_agrement) }}" target="_blank"
               style="display:flex;align-items:center;gap:10px;background:#FEF3DC;border:1px solid #FDE68A;border-radius:10px;padding:12px 14px;text-decoration:none;transition:background .15s;"
               onmouseover="this.style.background='#FDE9B8'" onmouseout="this.style.background='#FEF3DC'">
                <span style="width:34px;height:34px;background:#E8A020;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    </svg>
                </span>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#854F0B;">{{ __('admin.voir_document_agrement') }}</div>
                    <div style="font-size:11px;color:#92400E;">{{ __('admin.ouvrir_nouvel_onglet') }}</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2" style="flex-shrink:0;">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
            </a>
        @else
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-400">{{ __('admin.aucun_document_fourni') }}</div>
        @endif
    </div>

    {{-- ── Responsable / Directeur ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.responsable_etablissement') }}</h4>
        @if($responsable)
        <div style="background:#E6F0FB;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <div style="width:42px;height:42px;border-radius:50%;background:#185FA5;color:#fff;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    {{ Str::substr($responsable->prenom,0,1) }}{{ Str::substr($responsable->nom,0,1) }}
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#0B2545;">{{ $responsable->prenom }} {{ $responsable->nom }}</div>
                    <div style="font-size:11px;color:#185FA5;">{{ __('admin.directeur_responsable') }}</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;">
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">{{ __('admin.email') }}</div>
                    <div class="font-medium text-gray-800 text-sm wrap-break-word">{{ $responsable->email }}</div>
                </div>
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">{{ __('messages.telephone') }}</div>
                    <div class="font-medium text-gray-800 text-sm">{{ $responsable->telephone ?? '—' }}</div>
                </div>
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">{{ __('admin.compte_cree_le') }}</div>
                    <div class="font-medium text-gray-800 text-sm">{{ $responsable->created_at->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-400">{{ __('admin.aucun_responsable') }}</div>
        @endif
    </div>

    {{-- ── Activité ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.activite') }}</h4>
        <div class="grid grid-cols-3 gap-3 text-center">
            <div class="bg-[#E0F5EE] rounded-lg p-3">
                <div class="text-xl font-bold text-[#0D9E75]">{{ $etablissement->apprenants_count }}</div>
                <div class="text-xs text-[#085041] mt-0.5">{{ __('messages.apprenants') }}</div>
            </div>
            <div class="bg-[#FEF3DC] rounded-lg p-3">
                <div class="text-xl font-bold text-[#854F0B]">{{ $etablissement->commissions_count }}</div>
                <div class="text-xs text-[#854F0B] mt-0.5">{{ __('messages.commissions') }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xl font-bold text-gray-700">
                    {{ $etablissement->taux_commission ? number_format($etablissement->taux_commission * 100, 1)."%" : "2,5%" }}
                </div>
                <div class="text-xs text-gray-500 mt-0.5">{{ __('admin.taux_comm') }}</div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between text-xs text-gray-400 pt-2 border-t border-gray-100">
        <span>{{ __('admin.inscrit_le') }} {{ $etablissement->created_at->format("d/m/Y à H:i") }}</span>
        <span>{{ __('admin.mis_a_jour') }} {{ $etablissement->updated_at->diffForHumans() }}</span>
    </div>
</div>
