@extends('layouts.admin')
@section('title', __('admin.marge_titre'))

@section('content')
<div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:18px;">
    <div style="display:flex;align-items:center;gap:12px;">
        <div class="ep-ico or ep-ico-entete"><span class="material-symbols-outlined">payments</span></div>
        <div>
            <h3 style="margin:0;">{{ __('admin.marge_titre') }}</h3>
            <p class="text-sm text-gray-500 mt-1 ep-sous-titre" style="margin-top:2px;">{{ __('admin.marge_sous_titre') }}</p>
        </div>
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

{{-- Solde reel AangaraaPay : seul chiffre opposable — composant ep-bandeau v2 --}}
<div class="ep-bandeau {{ $solde['ok'] ? 'succes' : 'danger' }}">
    <div class="ep-bandeau-ico">
        <span class="material-symbols-outlined">account_balance</span>
    </div>
    <div class="ep-bandeau-corps">
        <div class="ep-bandeau-titre">{{ __('admin.solde_aangaraa') }}</div>
        @if ($solde['ok'])
            <div class="ep-bandeau-texte" style="font-size:12px;opacity:.8;margin-bottom:2px;">
                {{ __('admin.solde_disponible_libelle') }}
            </div>
            <div class="ep-bandeau-texte" style="font-size:24px;font-weight:800;color:var(--ep-navy);line-height:1.2;">
                {{ number_format($solde['solde'], 0, ',', ' ') }} FCFA
            </div>
            <div class="ep-bandeau-texte" style="margin-top:4px;">
                {{ $solde['service_name'] ?? __('admin.service') }}
            </div>

            {{-- Cumul encaisse : volume total passe par le compte sur les
                 transactions reussies. A NE PAS confondre avec le solde
                 disponible ci-dessus : les 774 XAF MTN observes le 02/10/2026
                 sont le cumul encaisse, pas une somme disponible. --}}
            <div class="ep-bandeau-texte" style="margin-top:10px;padding-top:10px;border-top:1px solid var(--ep-bordure,#E4E9EE);">
                <div style="font-size:12px;font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px;">
                    <span class="material-symbols-outlined"
                          style="font-size:16px;vertical-align:-3px;font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 20;">receipt_long</span>
                    {{ __('admin.cumul_encaisse_libelle') }}
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px 18px;font-size:12.5px;">
                    <span>
                        MTN : {{ number_format($solde['cumul']['mtn'], 0, ',', ' ') }}
                        @if ($solde['nbTransactions']['mtn'] !== null)
                            <span style="opacity:.7;">({{ number_format($solde['nbTransactions']['mtn'], 0, ',', ' ') }})</span>
                        @endif
                    </span>
                    <span>
                        Orange : {{ number_format($solde['cumul']['orange'], 0, ',', ' ') }}
                        @if ($solde['nbTransactions']['orange'] !== null)
                            <span style="opacity:.7;">({{ number_format($solde['nbTransactions']['orange'], 0, ',', ' ') }})</span>
                        @endif
                    </span>
                </div>
                <div style="font-size:11.5px;opacity:.75;margin-top:6px;">
                    {{ __('admin.cumul_encaisse_explication') }}
                </div>
            </div>
        @else
            <div class="ep-bandeau-texte" style="color:#991B1B;font-weight:600;">{{ $solde['message'] }}</div>
        @endif
        <div class="ep-bandeau-texte" style="margin-top:6px;opacity:.75;font-size:11.5px;">
            {{ __('admin.solde_aangaraa_explication') }}
        </div>
    </div>
</div>

{{-- Totaux de la periode --}}
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
    <div class="kpi ep-kpi">
        <div class="ep-ico or"><span class="material-symbols-outlined">payments</span></div>
        <div>
            <div class="kval" data-ep-count>{{ number_format($global->marge, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('admin.marge_eduplay') }} · FCFA</div>
        </div>
    </div>
    <div class="kpi ep-kpi">
        <div class="ep-ico bleu"><span class="material-symbols-outlined">account_balance_wallet</span></div>
        <div>
            <div class="kval" data-ep-count>{{ number_format($fraisPreleves, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('admin.frais_preleves') }} · FCFA</div>
        </div>
    </div>
    <div class="kpi ep-kpi">
        <div class="ep-ico rouge"><span class="material-symbols-outlined">trending_down</span></div>
        <div>
            <div class="kval" data-ep-count>{{ number_format($global->cout, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('admin.cout_aangaraa') }} @if ($tauxAangaraa !== null)· {{ number_format($tauxAangaraa * 100, 2, ',', '') }} % @endif</div>
        </div>
    </div>
    <div class="kpi ep-kpi">
        <div class="ep-ico navy"><span class="material-symbols-outlined">credit_card</span></div>
        <div>
            <div class="kval" data-ep-count>{{ number_format($totalPaye, 0, ',', ' ') }}</div>
            <div class="klbl">{{ __('admin.volume_encaisse') }} · FCFA</div>
        </div>
    </div>
</div>

{{-- Detail par operateur : c'est ici que le vrai cout se lit --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
    <h2 class="text-sm font-bold text-gray-800 mb-3">{{ __('admin.detail_par_operateur') }}</h2>
    <div class="overflow-x-auto">
        <table class="ep-table w-full">
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
            <table class="ep-table w-full">
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
