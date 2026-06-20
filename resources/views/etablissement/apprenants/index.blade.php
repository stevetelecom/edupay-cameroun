@extends('layouts.etablissement')

@section('title', 'Apprenants')

@section('content')

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:17px;font-weight:700;">Apprenants</div>
            <div style="font-size:12px;color:#888;">{{ $apprenants->total() ?? $apprenants->count() }} élève(s) enregistré(s)</div>
        </div>
        <a href="{{ route('etablissement.apprenants.create') }}" class="btn-p" style="width:auto;">
            + Ajouter un apprenant
        </a>
    </div>

    {{-- ── Filtre / recherche ── --}}
    <form method="GET" action="{{ route('etablissement.apprenants.index') }}" class="epcard" style="margin-bottom:16px;display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:2;">
            <div class="lbl">Recherche (nom, prénom, matricule)</div>
            <input type="text" name="q" value="{{ request('q') }}" class="inp" style="margin-bottom:0;" placeholder="Ex: FONO Brice">
        </div>
        <div style="flex:1;">
            <div class="lbl">Classe</div>
            <select name="classe" class="select" style="margin-bottom:0;">
                <option value="">Toutes</option>
                @foreach (($classes ?? []) as $classe)
                    <option value="{{ $classe }}" @selected(request('classe') === $classe)>{{ $classe }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:1;">
            <div class="lbl">Statut paiement</div>
            <select name="statut_paiement" class="select" style="margin-bottom:0;">
                <option value="">Tous</option>
                <option value="regle" @selected(request('statut_paiement') === 'regle')>Réglé</option>
                <option value="partiel" @selected(request('statut_paiement') === 'partiel')>Partiel</option>
                <option value="impaye" @selected(request('statut_paiement') === 'impaye')>Impayé</option>
            </select>
        </div>
        <button type="submit" class="btn-p" style="width:auto;padding:10px 20px;">Filtrer</button>
        @if(request()->hasAny(['q','classe','statut_paiement']))
            <a href="{{ route('etablissement.apprenants.index') }}" class="btn-o" style="width:auto;padding:10px 16px;">Réinitialiser</a>
        @endif
    </form>

    {{-- ── Tableau ── --}}
    <div class="epcard" style="padding:0;overflow:hidden;">
        <table class="ep-table">
            <thead>
                <tr>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Classe</th>
                    <th>Sexe</th>
                    <th>Statut paiement</th>
                    <th>Actif</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($apprenants as $apprenant)
                    <tr>
                        <td style="color:#888;">{{ $apprenant->matricule ?? '—' }}</td>
                        <td style="font-weight:600;">{{ $apprenant->nom }} {{ $apprenant->prenom }}</td>
                        <td>{{ $apprenant->classe }}</td>
                        <td>{{ $apprenant->sexe ?? '—' }}</td>
                        <td>
                            <span class="pill {{ match($apprenant->statut_paiement) {
                                'regle' => 'pg',
                                'partiel' => 'pa',
                                'impaye' => 'pr',
                                default => 'pa',
                            } }}">
                                {{ match($apprenant->statut_paiement) {
                                    'regle' => 'Réglé',
                                    'partiel' => 'Partiel',
                                    'impaye' => 'Impayé',
                                    default => $apprenant->statut_paiement,
                                } }}
                            </span>
                        </td>
                        <td>
                            @if($apprenant->actif)
                                <span class="pill pg">Actif</span>
                            @else
                                <span class="pill pr">Inactif</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <a href="{{ route('etablissement.apprenants.show', $apprenant) }}" style="color:var(--ep-teal);text-decoration:none;font-size:12px;margin-right:10px;">Voir</a>
                            <a href="{{ route('etablissement.apprenants.edit', $apprenant) }}" style="color:#185FA5;text-decoration:none;font-size:12px;margin-right:10px;">Modifier</a>
                            <form method="POST" action="{{ route('etablissement.apprenants.destroy', $apprenant) }}" style="display:inline;" onsubmit="return confirm('Supprimer cet apprenant ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color:var(--ep-red);background:none;border:none;font-size:12px;cursor:pointer;padding:0;">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:#999;padding:30px 0;">
                            Aucun apprenant trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($apprenants ?? null, 'links'))
        <div style="margin-top:16px;">
            {{ $apprenants->links() }}
        </div>
    @endif

@endsection
