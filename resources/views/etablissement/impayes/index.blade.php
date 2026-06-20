@extends('layouts.etablissement')

@section('title', 'Impayés')

@section('content')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:17px;font-weight:700;">Dossiers impayés</div>
            <div style="font-size:12px;color:#888;">{{ $fraisImpayes->total() ?? $fraisImpayes->count() }} dossier(s) en attente de règlement</div>
        </div>
        <form method="POST" action="{{ route('etablissement.impayes.relancer') }}">
            @csrf
            <button type="submit" class="btn-p" style="width:auto;">
                Relancer tous les impayés par SMS
            </button>
        </form>
    </div>

    {{-- ── KPI résumé ── --}}
    <div class="g3" style="margin-bottom:16px;">
        <div class="kpi">
            <div class="kval" style="color:var(--ep-red);">{{ number_format($totalImpaye ?? 0, 0, ',', ' ') }}</div>
            <div class="klbl">FCFA total impayé</div>
        </div>
        <div class="kpi">
            <div class="kval">{{ $fraisImpayes->total() ?? $fraisImpayes->count() }}</div>
            <div class="klbl">Dossiers concernés</div>
        </div>
        <div class="kpi">
            <div class="kval" style="color:var(--ep-gold);">{{ $tauxRecouvrement ?? 0 }}%</div>
            <div class="klbl">Taux de recouvrement</div>
        </div>
    </div>

    {{-- ── Filtre classe ── --}}
    <form method="GET" action="{{ route('etablissement.impayes.index') }}" class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:1;">
            <div class="lbl">Classe</div>
            <select name="classe" class="select" style="margin-bottom:0;">
                <option value="">Toutes</option>
                @foreach (($classes ?? []) as $classe)
                    <option value="{{ $classe }}" @selected(request('classe') === $classe)>{{ $classe }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">Filtrer</button>
    </form>

    {{-- ── Tableau ── --}}
    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>Apprenant</th>
                    <th>Classe</th>
                    <th>Catégorie de frais</th>
                    <th>Montant total</th>
                    <th>Reste à payer</th>
                    <th>Statut</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fraisImpayes as $frais)
                    <tr>
                        <td style="font-weight:600;">{{ $frais->apprenant->nom }} {{ $frais->apprenant->prenom }}</td>
                        <td>{{ $frais->apprenant->classe }}</td>
                        <td>{{ $frais->categorieFrais->nom ?? '—' }}</td>
                        <td>{{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA</td>
                        <td style="color:var(--ep-red);font-weight:600;">
                            {{ number_format($frais->montant_total - $frais->montant_paye, 0, ',', ' ') }} FCFA
                        </td>
                        <td>
                            <span class="pill {{ $frais->statut === 'partiel' ? 'pa' : 'pr' }}">
                                {{ $frais->statut === 'partiel' ? 'Partiel' : 'Impayé' }}
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('etablissement.apprenants.show', $frais->apprenant) }}" style="color:var(--ep-teal);text-decoration:none;font-size:12px;">
                                Voir le dossier
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:30px 0;">
                            Aucun impayé — tous les dossiers sont à jour 🎉
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($fraisImpayes ?? null, 'links'))
        <div style="margin-top:16px;">
            {{ $fraisImpayes->links() }}
        </div>
    @endif

@endsection
