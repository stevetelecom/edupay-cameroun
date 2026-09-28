@extends('layouts.admin')
@section('title', __('admin.marge_titre'))

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-xl font-bold text-gray-800">{{ __('admin.marge_titre') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('admin.marge_sous_titre') }}</p>
    </div>
</div>

{{-- Periode : jour / semaine / mois / annee --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
    <form method="GET" action="{{ route('admin.marge.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="periode" class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.periode') }}</label>
            <select name="periode" id="periode" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white">
                @foreach (['jour' => __('admin.periode_jour'), 'semaine' => __('admin.periode_semaine'), 'mois' => __('admin.periode_mois'), 'annee' => __('admin.periode_annee')] as $cle => $libelle)
                    <option value="{{ $cle }}" @selected($periode === $cle)>{{ $libelle }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date" class="block text-xs font-medium text-gray-600 mb-1">{{ __('admin.date_reference') }}</label>
            <input type="date" name="date" id="date" value="{{ $debut->toDateString() }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <button type="submit" class="px-4 py-2 bg-[#E8A020] text-white text-sm font-semibold rounded-lg hover:opacity-90">
            {{ __('admin.filtrer') }}
        </button>
    </form>
    <p class="text-xs text-gray-500 mt-3">
        {{ __('admin.periode_du') }} <strong>{{ $debut->format('d/m/Y') }}</strong>
        {{ __('admin.au') }} <strong>{{ $fin->format('d/m/Y') }}</strong>
    </p>
</div>

{{-- Solde reel AangaraaPay : seul chiffre opposable --}}
<div class="rounded-xl p-4 mb-5 border {{ $solde['ok'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ __('admin.solde_aangaraa') }}</div>
            @if ($solde['ok'])
                <div class="text-2xl font-bold text-gray-800 mt-1">
                    {{ number_format($solde['solde'], 0, ',', ' ') }} FCFA
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $solde['service_name'] ?? __('admin.service') }}
                    @if ($solde['nbTransactions'] !== null)
                        &middot; {{ number_format($solde['nbTransactions'], 0, ',', ' ') }} {{ __('admin.transactions_succes') }}
                    @endif
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    MTN : {{ number_format($solde['parOperateur']['mtn'], 0, ',', ' ') }}
                    &middot; Orange : {{ number_format($solde['parOperateur']['orange'], 0, ',', ' ') }} FCFA
                </div>
            @else
                <div class="text-sm text-red-700 mt-1">{{ $solde['message'] }}</div>
            @endif
        </div>
    </div>
    <p class="text-xs text-gray-600 mt-3">
        {{ __('admin.solde_aangaraa_explication') }}
    </p>
</div>

{{-- Totaux de la periode --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="text-xs font-medium text-gray-500">{{ __('admin.marge_eduplay') }}</div>
        <div class="text-2xl font-bold text-[#E8A020] mt-1">{{ number_format($global->marge, 0, ',', ' ') }}</div>
        <div class="text-xs text-gray-500 mt-1">FCFA</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="text-xs font-medium text-gray-500">{{ __('admin.frais_preleves') }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($fraisPreleves, 0, ',', ' ') }}</div>
        <div class="text-xs text-gray-500 mt-1">FCFA</div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="text-xs font-medium text-gray-500">{{ __('admin.cout_aangaraa') }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($global->cout, 0, ',', ' ') }}</div>
        <div class="text-xs text-gray-500 mt-1">
            {{ $tauxAangaraa !== null ? number_format($tauxAangaraa * 100, 2, ',', '').' %' : '' }}
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <div class="text-xs font-medium text-gray-500">{{ __('admin.volume_encaisse') }}</div>
        <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalPaye, 0, ',', ' ') }}</div>
        <div class="text-xs text-gray-500 mt-1">FCFA</div>
    </div>
</div>

{{-- Detail par operateur : c'est ici que le vrai cout se lit --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
    <h2 class="text-sm font-bold text-gray-800 mb-3">{{ __('admin.detail_par_operateur') }}</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs text-gray-500 border-b">
                    <th class="pb-2">{{ __('admin.operateur') }}</th>
                    <th class="pb-2 text-right">{{ __('messages.transactions') }}</th>
                    <th class="pb-2 text-right">{{ __('admin.frais_preleves') }}</th>
                    <th class="pb-2 text-right">{{ __('admin.cout_reel_estime') }}</th>
                    <th class="pb-2 text-right">{{ __('admin.marge_eduplay') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($parOperateur as $ligne)
                    @php
                        $coutLigne = (float) $ligne->frais_preleves * $tauxAangaraa;
                        $margeLigne = (float) $ligne->marge;
                    @endphp
                    <tr class="border-b border-gray-100">
                        <td class="py-2 font-medium">{{ $ligne->operateur ?? __('admin.non_precise') }}</td>
                        <td class="py-2 text-right">{{ number_format($ligne->nb, 0, ',', ' ') }}</td>
                        <td class="py-2 text-right">{{ number_format($ligne->frais_preleves, 0, ',', ' ') }}</td>
                        <td class="py-2 text-right">{{ number_format($coutLigne, 0, ',', ' ') }}</td>
                        <td class="py-2 text-right font-semibold {{ $margeLigne >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ number_format($margeLigne, 0, ',', ' ') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-gray-400">{{ __('admin.aucune_donnee') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-500 mt-3">{{ __('admin.detail_operateur_explication') }}</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    {{-- Par etablissement --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <h2 class="text-sm font-bold text-gray-800 mb-3">{{ __('admin.detail_par_etablissement') }}</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b">
                        <th class="pb-2">{{ __('admin.etablissement') }}</th>
                        <th class="pb-2 text-right">{{ __('messages.transactions') }}</th>
                        <th class="pb-2 text-right">{{ __('admin.marge_eduplay') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parEtablissement as $ligne)
                        <tr class="border-b border-gray-100">
                            <td class="py-2">{{ $ligne->nom }}</td>
                            <td class="py-2 text-right">{{ number_format($ligne->nb, 0, ',', ' ') }}</td>
                            <td class="py-2 text-right font-semibold">{{ number_format($ligne->marge, 0, ',', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-gray-400">{{ __('admin.aucune_donnee') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Par statut de reversement --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4">
        <h2 class="text-sm font-bold text-gray-800 mb-3">{{ __('admin.detail_par_statut') }}</h2>
        <div class="space-y-2">
            @foreach (['calculee', 'en_cours', 'prelevee', 'a_verifier', 'echec'] as $statut)
                @php
                    $ligne  = $parStatut->get($statut);
                    // Clé construite à partir de la constante : le test de
                    // traductions vérifie qu'aucune clé ne s'affiche en clair.
                    $statutEnAttente = in_array($statut, App\Models\Commission::STATUTS_A_TRAITER, true);
                @endphp
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">{{ $libellesStatut[$statut] ?? $statut }}</span>
                    <span class="font-semibold {{ $statutEnAttente ? 'text-red-700' : 'text-gray-700' }}">
                        {{ $ligne ? number_format($ligne->marge, 0, ',', ' ').' FCFA' : __('admin.aucune_donnee') }}
                    </span>
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-4">{{ __('admin.detail_statut_explication') }}</p>
    </div>
</div>
@endsection
