@extends('layouts.admin')

@section('title', 'Exports réglementaires')

@section('content')

    <div class="mb-5">
        <h1 class="text-xl font-bold text-gray-900">Exports réglementaires COBAC / BEAC</h1>
        <p class="text-sm text-gray-500 mt-0.5">Génération de rapports financiers conformes pour les autorités de régulation.</p>
    </div>

    <div class="space-y-4 max-w-3xl">

        {{-- Rapport mensuel BEAC --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-bold text-gray-900">Rapport mensuel des flux financiers</div>
                    <div class="text-xs text-gray-500 mt-0.5">Conforme directives BEAC · volume, commissions, répartition opérateur</div>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.exports.mensuel') }}" class="flex items-center gap-3 mt-4">
                <input type="month" name="mois" value="{{ $moisDefaut }}"
                       class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#0D9E75]">
                <button type="submit"
                        class="bg-white border border-[#0D9E75] text-[#0D9E75] hover:bg-[#E0F5EE] text-xs font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Générer PDF
                </button>
            </form>
        </div>

        {{-- Déclaration trimestrielle COBAC --}}
        <div class="bg-white border border-gray-200 rounded-xl p-5">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="text-sm font-bold text-gray-900">Déclaration trimestrielle COBAC</div>
                    <div class="text-xs text-gray-500 mt-0.5">Volume, commissions, anomalies (échecs, remboursements, réclamations)</div>
                </div>
            </div>
            <form method="GET" action="{{ route('admin.exports.cobac') }}" class="flex items-center gap-3 mt-4">
                <select name="trimestre"
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-[#0D9E75]">
                    @foreach ($trimestres as $valeur => $label)
                        <option value="{{ $valeur }}" {{ $valeur === $trimestreDefaut ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit"
                        class="bg-white border border-[#0D9E75] text-[#0D9E75] hover:bg-[#E0F5EE] text-xs font-semibold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Générer PDF
                </button>
            </form>
        </div>
    </div>

@endsection
