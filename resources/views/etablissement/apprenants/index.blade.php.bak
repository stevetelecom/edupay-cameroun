@extends('layouts.etablissement')

@section('title', 'Apprenants')

@section('content')

    {{-- ── Alertes import CSV ── --}}
    @if (session('success'))
        <div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if (session('import_erreurs'))
        <div class="epcard" style="background:#fef3c7;border-left:4px solid #d97706;color:#92400e;margin-bottom:16px;padding:12px 16px;">
            <strong>⚠ Problèmes détectés lors de l'import :</strong>
            <ul style="margin:8px 0 0;padding-left:18px;">
                @foreach (session('import_erreurs') as $err)
                    <li style="font-size:13px;">{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <div>
            <div style="font-size:17px;font-weight:700;">Apprenants</div>
            <div style="font-size:12px;color:#888;">{{ $apprenants->total() ?? $apprenants->count() }} élève(s) enregistré(s)</div>
        </div>
                <button type="button"
                onclick="document.getElementById('modalImportCsv').style.display='flex'"
                class="btn-o"
                style="width:auto;padding:8px 16px;font-size:13px;margin-right:8px;">
            ↑ Importer CSV
        </button>
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


{{-- ════════════════════════════════════════════════
     MODAL — Import CSV apprenants (E11) — vanilla JS
     ════════════════════════════════════════════════ --}}
<div id="modalImportCsv"
     onclick="if(event.target===this)this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;">

    <div style="background:#fff;border-radius:var(--radius-lg);width:100%;max-width:480px;margin:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);">

        {{-- En-tête --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f0f0f0;">
            <div style="font-size:15px;font-weight:700;">↑ Importer des apprenants (CSV)</div>
            <button onclick="document.getElementById('modalImportCsv').style.display='none'"
                    style="background:none;border:none;font-size:20px;cursor:pointer;color:#888;line-height:1;">×</button>
        </div>

        <form action="{{ route('etablissement.apprenants.import') }}"
              method="POST"
              enctype="multipart/form-data">
            @csrf

            {{-- Corps --}}
            <div style="padding:20px;">

                {{-- Format attendu --}}
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px;margin-bottom:16px;font-size:12px;color:#166534;">
                    <strong>Colonnes attendues :</strong><br>
                    <code style="font-size:11px;">nom, prenom, classe, matricule, date_naissance, sexe</code><br>
                    <span style="color:#4b7c60;margin-top:4px;display:block;">
                        Les colonnes <em>matricule</em>, <em>date_naissance</em> et <em>sexe</em> sont optionnelles.
                    </span>
                </div>

                {{-- Upload --}}
                <div style="margin-bottom:14px;">
                    <div class="lbl">Fichier CSV <span style="color:var(--ep-red);">*</span></div>
                    <input type="file"
                           name="fichier_csv"
                           accept=".csv,.txt"
                           class="inp"
                           style="padding:8px;">
                    @error('fichier_csv')
                        <div style="color:var(--ep-red);font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                    <div style="font-size:11px;color:#888;margin-top:4px;">Taille max. : 2 Mo — encodage UTF-8 recommandé</div>
                </div>

                {{-- Télécharger modèle --}}
                <a href="{{ route('etablissement.apprenants.import.template') }}"
                   style="font-size:12px;color:var(--ep-teal);text-decoration:none;">
                    ↓ Télécharger le fichier modèle (.csv)
                </a>

            </div>

            {{-- Pied --}}
            <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;border-top:1px solid #f0f0f0;">
                <button type="button"
                        onclick="document.getElementById('modalImportCsv').style.display='none'"
                        class="btn-o" style="width:auto;padding:8px 16px;">
                    Annuler
                </button>
                <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
                    Lancer l'import
                </button>
            </div>

        </form>
    </div>
</div>
{{-- ════ FIN MODAL IMPORT CSV ════ --}}

@endsection
