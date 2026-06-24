@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')

    {{-- En-tête de page --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-gray-900">KPIs globaux — {{ $mois }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">Toutes les écoles · Données en temps réel</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-green-700 bg-green-50 border border-green-200 px-3 py-1.5 rounded-full font-medium">
                <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse inline-block"></span>
                Système opérationnel
            </span>
        </div>
    </div>

    {{-- ── KPIs principaux ── --}}
    <div class="grid grid-cols-4 gap-4 mb-6">

        {{-- Volume de transactions --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Volume (mois)</span>
                <div class="w-8 h-8 bg-[#E0F5EE] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-[#0D9E75]">
                {{ number_format($volumeMois, 0, ',', ' ') }}
            </div>
            <div class="text-xs text-gray-500 mt-1">FCFA encaissés</div>
        </div>

        {{-- Commissions --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Commissions</span>
                <div class="w-8 h-8 bg-[#FEF3DC] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#E8A020]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-[#E8A020]">
                {{ number_format($commissionsMois, 0, ',', ' ') }}
            </div>
            <div class="text-xs text-gray-500 mt-1">FCFA ce mois</div>
        </div>

        {{-- Établissements actifs --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Établissements</span>
                <div class="w-8 h-8 bg-[#E6F0FB] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#185FA5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="15"/>
                        <polyline points="16 2 12 7 8 2"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ $etablissementsActifs }}</div>
            <div class="text-xs text-gray-500 mt-1">actifs sur la plateforme</div>
        </div>

        {{-- Transactions --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Transactions</span>
                <div class="w-8 h-8 bg-[#FBEAEA] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#D94040]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                        <line x1="1" y1="10" x2="23" y2="10"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($transactionsMois, 0, ',', ' ') }}</div>
            <div class="text-xs text-gray-500 mt-1">validées ce mois</div>
        </div>
    </div>

    {{-- ── Grille secondaire ── --}}
    <div class="grid grid-cols-2 gap-5 mb-5">

        {{-- Répartition par moyen de paiement --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <h2 class="text-sm font-bold text-gray-900 mb-4">Répartition des paiements</h2>

            @php
                $moyens = [
                    'mtn_momo'     => ['label' => 'MTN Mobile Money', 'couleur' => '#FFCC00', 'bg' => '#FFFBE6'],
                    'orange_money' => ['label' => 'Orange Money',     'couleur' => '#FF6600', 'bg' => '#FFF0E6'],
                    'carte'        => ['label' => 'Carte bancaire',   'couleur' => '#185FA5', 'bg' => '#E6F0FB'],
                ];
                $totalTx = $repartitionMoyens->sum('total') ?: 1;
            @endphp

            <div class="space-y-3">
                @foreach ($moyens as $key => $info)
                    @php
                        $row  = $repartitionMoyens->get($key);
                        $pct  = $row ? round(($row->total / $totalTx) * 100, 1) : 0;
                        $vol  = $row ? number_format($row->volume, 0, ',', ' ') : '0';
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:{{ $info['couleur'] }}"></span>
                                <span class="text-gray-700">{{ $info['label'] }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-gray-900">{{ $pct }}%</span>
                                <span class="text-xs text-gray-400 ml-2">{{ $vol }} FCFA</span>
                            </div>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 style="width:{{ $pct }}%; background:{{ $info['couleur'] }}">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Derniers établissements inscrits --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-gray-900">Derniers établissements inscrits</h2>
                @if (Route::has('admin.etablissements.index'))
                <a href="{{ route('admin.etablissements.index') }}"
                   class="text-xs text-[#0D9E75] hover:underline font-medium">
                    Voir tout
                </a>
                @endif
            </div>
            <div class="space-y-0">
                @forelse ($derniersEtablissements as $etablissement)
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-100 last:border-b-0">
                        <div>
                            <div class="text-sm font-semibold text-gray-800">{{ $etablissement->nom }}</div>
                            <div class="text-xs text-gray-400">{{ $etablissement->ville }} · {{ $etablissement->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium
                            {{ $etablissement->statut === 'actif' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($etablissement->statut) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 text-center py-4">Aucun établissement encore inscrit.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── Taux de commission — configurable ── --}}
    <div class="bg-[#FEF3DC] rounded-xl border-l-4 border-[#E8A020] px-5 py-4 flex items-center justify-between">
        <div>
            <div class="text-sm font-bold text-[#854F0B]">Taux de commission — configurable</div>
            <div class="text-xs text-[#BA7517] mt-1">
                <strong>{{ number_format($tauxCommission * 100, 1, ',', '') }}%</strong>
                par transaction · Profil Standard · Conforme COBAC/BEAC
            </div>
        </div>
        @if (Route::has('admin.commissions.index'))
        <a href="{{ route('admin.commissions.index') }}"
           class="bg-[#854F0B] hover:bg-[#6B3E09] text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Modifier le taux
        </a>
        @endif
    </div>

@endsection