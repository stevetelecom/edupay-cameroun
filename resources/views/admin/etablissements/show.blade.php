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
        'public'=>'Public','prive_laic'=>'Privé laïc','prive_catholique'=>'Privé catholique',
        'prive_protestant'=>'Privé protestant','prive_islamique'=>'Privé islamique',
    ];
    $nbElevesLabels = [
        'moins_100'=>'Moins de 100','100_300'=>'100 à 300','300_500'=>'300 à 500',
        '500_1000'=>'500 à 1000','plus_1000'=>'Plus de 1000',
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
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">🏫 Informations établissement</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Type</div>
                <div class="font-medium text-gray-800 text-sm">{{ ucfirst(str_replace('_',' ',$etablissement->type ?? "—")) }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Statut juridique</div>
                <div class="font-medium text-gray-800 text-sm">{{ $statutJuridiqueLabels[$etablissement->statut_juridique] ?? $etablissement->statut_juridique ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">N° agrément</div>
                <div class="font-medium text-gray-800 text-sm break-words">{{ $etablissement->numero_agrement ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Effectif déclaré</div>
                <div class="font-medium text-gray-800 text-sm">{{ $nbElevesLabels[$etablissement->nb_eleves] ?? '—' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Région</div>
                <div class="font-medium text-gray-800 text-sm">{{ $regionLabels[$etablissement->region] ?? $etablissement->region }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Ville / Quartier</div>
                <div class="font-medium text-gray-800 text-sm">{{ $etablissement->ville }}{{ $etablissement->quartier ? ' — '.$etablissement->quartier : '' }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Boîte postale</div>
                <div class="font-medium text-gray-800 text-sm">{{ $etablissement->boite_postale ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Mobile Money principal</div>
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
            <div class="text-xs text-gray-400 mb-1">Description</div>
            <div class="font-medium text-gray-800 text-sm" style="white-space:pre-wrap;">{{ $etablissement->description }}</div>
        </div>
        @endif
    </div>

    {{-- ── Contact établissement ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">📞 Contact établissement</h4>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;">
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Téléphone</div>
                <div class="font-medium text-gray-800 text-sm break-words">{{ $etablissement->telephone ?? "—" }}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Email</div>
                <div class="font-medium text-gray-800 text-sm break-words">{{ $etablissement->email ?? "—" }}</div>
            </div>
            @if($etablissement->site_web)
            <div class="bg-gray-50 rounded-lg p-3">
                <div class="text-xs text-gray-400 mb-1">Site web</div>
                <a href="{{ $etablissement->site_web }}" target="_blank" class="font-medium text-[#0D9E75] text-sm break-words">{{ $etablissement->site_web }}</a>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Document d'agrément ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">📄 Document d'agrément</h4>
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
                    <div style="font-size:13px;font-weight:600;color:#854F0B;">Voir le document d'agrément</div>
                    <div style="font-size:11px;color:#92400E;">Cliquer pour ouvrir dans un nouvel onglet</div>
                </div>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#92400E" stroke-width="2" style="flex-shrink:0;">
                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                </svg>
            </a>
        @else
            <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-400">Aucun document fourni.</div>
        @endif
    </div>

    {{-- ── Responsable / Directeur ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">👤 Responsable de l'établissement</h4>
        @if($responsable)
        <div style="background:#E6F0FB;border:1px solid #BFDBFE;border-radius:10px;padding:14px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <div style="width:42px;height:42px;border-radius:50%;background:#185FA5;color:#fff;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    {{ Str::substr($responsable->prenom,0,1) }}{{ Str::substr($responsable->nom,0,1) }}
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#0B2545;">{{ $responsable->prenom }} {{ $responsable->nom }}</div>
                    <div style="font-size:11px;color:#185FA5;">Directeur / Responsable principal</div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;">
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">Email</div>
                    <div class="font-medium text-gray-800 text-sm break-words">{{ $responsable->email }}</div>
                </div>
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">Téléphone</div>
                    <div class="font-medium text-gray-800 text-sm">{{ $responsable->telephone ?? '—' }}</div>
                </div>
                <div class="bg-white rounded-lg p-2.5">
                    <div class="text-xs text-gray-400 mb-0.5">Compte créé le</div>
                    <div class="font-medium text-gray-800 text-sm">{{ $responsable->created_at->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-400">Aucun responsable trouvé pour cet établissement.</div>
        @endif
    </div>

    {{-- ── Activité ── --}}
    <div>
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">📊 Activité</h4>
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
        <span>Inscrit le {{ $etablissement->created_at->format("d/m/Y à H:i") }}</span>
        <span>Mis à jour {{ $etablissement->updated_at->diffForHumans() }}</span>
    </div>
</div>
