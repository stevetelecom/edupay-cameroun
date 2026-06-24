@extends('layouts.etablissement')

@section('title', 'Ajouter un apprenant')

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('etablissement.apprenants.index') }}" style="color:#888;text-decoration:none;font-size:13px;">← Retour</a>
    </div>

    <div class="epcard" style="max-width:640px;">
        <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Nouvel apprenant</div>
        <div style="font-size:12px;color:#888;margin-bottom:20px;">Renseignez les informations de l'élève à inscrire.</div>

        <form method="POST" action="{{ route('etablissement.apprenants.store') }}">
            @csrf

            <div class="inp-row">
                <div>
                    <div class="lbl">Nom *</div>
                    <input type="text" name="nom" value="{{ old('nom') }}" class="inp" required>
                    @error('nom') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <div class="lbl">Prénom *</div>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" class="inp" required>
                    @error('prenom') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inp-row">
                <div>
                    <div class="lbl">Classe *</div>
                    <input type="text" name="classe" value="{{ old('classe') }}" class="inp" placeholder="Ex: 3ème B" required>
                    @error('classe') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <div class="lbl">Matricule</div>
                    <input type="text" name="matricule" value="{{ old('matricule') }}" class="inp" placeholder="Optionnel, généré si vide">
                    @error('matricule') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inp-row">
                <div>
                    <div class="lbl">Date de naissance</div>
                    <input type="date" name="date_naissance" value="{{ old('date_naissance') }}" class="inp">
                </div>
                <div>
                    <div class="lbl">Sexe</div>
                    <select name="sexe" class="select">
                        <option value="">—</option>
                        <option value="M" @selected(old('sexe') === 'M')>Masculin</option>
                        <option value="F" @selected(old('sexe') === 'F')>Féminin</option>
                    </select>
                </div>
            </div>

            <div class="lbl">Statut de l'apprenant</div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#555;margin-bottom:18px;">
                <input type="checkbox" name="actif" value="1" @checked(old('actif', true))>
                Apprenant actif (inscrit pour l'année en cours)
            </label>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn-p">Enregistrer l'apprenant</button>
                <a href="{{ route('etablissement.apprenants.index') }}" class="btn-o" style="text-align:center;">Annuler</a>
            </div>
        </form>
    </div>

@endsection
