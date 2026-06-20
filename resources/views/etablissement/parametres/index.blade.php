@extends('layouts.etablissement')

@section('title', 'Paramètres')

@section('content')

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">Paramètres de l'établissement</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">Informations générales et configuration des frais.</div>

    @php($etab = $etablissement ?? Auth::user()->etablissement)

    <div class="g2">

        {{-- ── Informations établissement ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:14px;">Informations générales</div>

            <form method="POST" action="{{ route('etablissement.parametres.update') }}">
                @csrf
                @method('PUT')

                <div class="lbl">Nom de l'établissement</div>
                <input type="text" name="nom" value="{{ old('nom', $etab->nom ?? '') }}" class="inp" required>

                <div class="inp-row">
                    <div>
                        <div class="lbl">Téléphone</div>
                        <input type="text" name="telephone" value="{{ old('telephone', $etab->telephone ?? '') }}" class="inp">
                    </div>
                    <div>
                        <div class="lbl">Email</div>
                        <input type="email" name="email" value="{{ old('email', $etab->email ?? '') }}" class="inp">
                    </div>
                </div>

                <div class="inp-row">
                    <div>
                        <div class="lbl">Ville</div>
                        <input type="text" name="ville" value="{{ old('ville', $etab->ville ?? '') }}" class="inp">
                    </div>
                    <div>
                        <div class="lbl">Quartier</div>
                        <input type="text" name="quartier" value="{{ old('quartier', $etab->quartier ?? '') }}" class="inp">
                    </div>
                </div>

                <div class="lbl">Moyen Mobile Money principal</div>
                <select name="mobile_money_principal" class="select">
                    <option value="mtn" @selected(($etab->mobile_money_principal ?? '') === 'mtn')>MTN Mobile Money</option>
                    <option value="orange" @selected(($etab->mobile_money_principal ?? '') === 'orange')>Orange Money</option>
                    <option value="les_deux" @selected(($etab->mobile_money_principal ?? '') === 'les_deux')>Les deux</option>
                </select>

                <button type="submit" class="btn-p" style="margin-top:8px;">Enregistrer les modifications</button>
            </form>
        </div>

        {{-- ── Statut & infos lecture seule ── --}}
        <div>
            <div class="epcard" style="margin-bottom:14px;">
                <div style="font-size:14px;font-weight:700;margin-bottom:12px;">Statut du compte</div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">Code établissement</span>
                    <strong style="font-size:13px;">{{ $etab->code_etablissement ?? '—' }}</strong>
                </div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">Statut</span>
                    <span class="pill {{ match($etab->statut ?? '') {
                        'actif' => 'pg', 'en_attente' => 'pa', 'suspendu' => 'pr', default => 'pa',
                    } }}">
                        {{ match($etab->statut ?? '') {
                            'actif' => 'Actif', 'en_attente' => 'En attente', 'suspendu' => 'Suspendu', default => '—',
                        } }}
                    </span>
                </div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">Taux de commission</span>
                    <strong style="font-size:13px;">{{ number_format(($etab->taux_commission ?? 0) * 100, 2, ',', '') }}%</strong>
                </div>
            </div>

            {{-- ── Catégories de frais ── --}}
            <div class="epcard">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div style="font-size:14px;font-weight:700;">Catégories de frais</div>
                    @if(Route::has('etablissement.categories-frais.create'))
                        <a href="{{ route('etablissement.categories-frais.create') }}" style="font-size:11px;color:var(--ep-teal);text-decoration:none;">+ Ajouter</a>
                    @endif
                </div>
                @forelse (($categoriesFrais ?? []) as $cat)
                    <div class="row">
                        <div>
                            <div style="font-size:13px;font-weight:600;">{{ $cat->nom }}</div>
                            <div style="font-size:10px;color:#888;">
                                {{ number_format($cat->montant_total, 0, ',', ' ') }} FCFA
                                @if($cat->fractionnable) · Fractionnable en {{ $cat->nb_tranches_max }} @endif
                            </div>
                        </div>
                        <span class="pill {{ $cat->actif ? 'pg' : 'pr' }}">{{ $cat->actif ? 'Actif' : 'Inactif' }}</span>
                    </div>
                @empty
                    <div style="text-align:center;color:#999;font-size:13px;padding:14px 0;">Aucune catégorie de frais définie.</div>
                @endforelse
            </div>
        </div>
    </div>

@endsection
