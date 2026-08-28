@extends('layouts.etablissement')

@section('title', __('etablissement.dt_title_modifier') . ' ' . $apprenant->nom)

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
        <a href="{{ route('etablissement.apprenants.show', $apprenant) }}" style="color:#888;text-decoration:none;font-size:13px;">{{ __('etablissement.retour_fiche') }}</a>
    </div>

    <div class="epcard" style="max-width:640px;">
        <div style="font-size:16px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.modifier_apprenant') }}</div>
        <div style="font-size:12px;color:#888;margin-bottom:20px;">{{ $apprenant->nom }} {{ $apprenant->prenom }} · {{ $apprenant->classe }}</div>

        <form method="POST" action="{{ route('etablissement.apprenants.update', $apprenant) }}">
            @csrf
            @method('PUT')

            <div class="inp-row">
                <div>
                    <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
                    <input type="text" name="nom" value="{{ old('nom', $apprenant->nom) }}" class="inp" required>
                    @error('nom') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
                    <input type="text" name="prenom" value="{{ old('prenom', $apprenant->prenom) }}" class="inp" required>
                    @error('prenom') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inp-row">
                <div>
                    <div class="lbl">{{ __('etablissement.lbl_classe') }}</div>
                    <input type="text" name="classe" value="{{ old('classe', $apprenant->classe) }}" class="inp" required>
                    @error('classe') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
                <div>
                    <div class="lbl">{{ __('etablissement.matricule') }}</div>
                    <input type="text" name="matricule" value="{{ old('matricule', $apprenant->matricule) }}" class="inp">
                    @error('matricule') <div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:10px;">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inp-row">
                <div>
                    <div class="lbl">{{ __('etablissement.ddn') }}</div>
                    <input type="date" name="date_naissance" value="{{ old('date_naissance', optional($apprenant->date_naissance)->format('Y-m-d')) }}" class="inp">
                </div>
                <div>
                    <div class="lbl">{{ __('etablissement.sexe') }}</div>
                    <select name="sexe" class="select">
                        <option value="">—</option>
                        <option value="M" @selected(old('sexe', $apprenant->sexe) === 'M')>{{ __('etablissement.masculin') }}</option>
                        <option value="F" @selected(old('sexe', $apprenant->sexe) === 'F')>{{ __('etablissement.feminin') }}</option>
                    </select>
                </div>
            </div>

            <div class="lbl">{{ __('etablissement.statut_apprenant') }}</div>
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#555;margin-bottom:18px;">
                <input type="checkbox" name="actif" value="1" @checked(old('actif', $apprenant->actif))>
                {{ __('etablissement.apprenant_actif_annee') }}
            </label>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn-p">{{ __('etablissement.enregistrer_modifs') }}</button>
                <a href="{{ route('etablissement.apprenants.show', $apprenant) }}" class="btn-o" style="text-align:center;">{{ __('etablissement.annuler') }}</a>
            </div>
        </form>
    </div>

@endsection
