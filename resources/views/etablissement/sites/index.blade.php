@extends('layouts.etablissement')

@section('title', 'Multi-sites')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div>
        <div style="font-size:17px;font-weight:700;">Gestion du groupe scolaire</div>
        <div style="font-size:12px;color:#888;">{{ $sitePrincipal->nom }} et ses sites rattachés</div>
    </div>
    @if($estSitePrincipal)
        <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;"
                onclick="document.getElementById('site-box').style.display='block';this.style.display='none';">
            + Ajouter un site
        </button>
    @endif
</div>

<div class="g3" style="margin-bottom:16px;">
    <div class="kpi">
        <div class="kval">{{ $kpisParSite->count() }}</div>
        <div class="klbl">Sites dans le groupe</div>
    </div>
    <div class="kpi">
        <div class="kval">{{ number_format($totalGroupeApprenants, 0, ',', ' ') }}</div>
        <div class="klbl">Apprenants (tous sites)</div>
    </div>
    <div class="kpi">
        <div class="kval">{{ number_format($totalGroupeEncaisse, 0, ',', ' ') }}</div>
        <div class="klbl">FCFA encaissés (groupe)</div>
    </div>
</div>

@if($estSitePrincipal)
<div id="site-box" class="epcard" style="display:none;margin-bottom:16px;border-left:3px solid var(--ep-gold);">
    <div class="seclbl" style="margin-top:0;">Ajouter un nouveau site</div>

    @if($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('etablissement.sites.store') }}">
        @csrf
        <div class="g2">
            <div>
                <div class="lbl">Nom du site</div>
                <input class="inp" name="nom" required placeholder="Ex : Annexe Mvolyé" />
            </div>
            <div>
                <div class="lbl">Ville</div>
                <input class="inp" name="ville" required placeholder="Ex : Yaoundé" />
            </div>
        </div>
        <div class="g2">
            <div>
                <div class="lbl">Quartier (optionnel)</div>
                <input class="inp" name="quartier" placeholder="Ex : Mvolyé" />
            </div>
            <div>
                <div class="lbl">Téléphone du site</div>
                <input class="inp" name="telephone" required placeholder="Ex : 6XX XXX XXX" />
            </div>
        </div>
        <div class="lbl">Email du site</div>
        <input class="inp" type="email" name="email" required />

        <div class="divider"></div>
        <div class="seclbl" style="margin-top:0;">Compte du directeur de ce site</div>

        <div class="g2">
            <div>
                <div class="lbl">Prénom</div>
                <input class="inp" name="directeur_prenom" required />
            </div>
            <div>
                <div class="lbl">Nom</div>
                <input class="inp" name="directeur_nom" required />
            </div>
        </div>
        <div class="lbl">Email du directeur</div>
        <input class="inp" type="email" name="directeur_email" required />
        <div style="font-size:11px;color:#888;margin:-6px 0 12px;">
            Un mot de passe provisoire sera généré et envoyé à cette adresse.
        </div>

        <div style="display:flex;gap:8px;margin-top:6px;">
            <button type="submit" class="btn-p" style="width:auto;padding:9px 18px;font-size:12px;">
                Créer le site
            </button>
            <button type="button" class="btn-o" style="width:auto;padding:9px 18px;font-size:12px;"
                    onclick="document.getElementById('site-box').style.display='none';">
                Annuler
            </button>
        </div>
    </form>
</div>
@endif

<div class="epcard">
    <div class="seclbl" style="margin-top:0;">Sites du groupe</div>
    @foreach($kpisParSite as $k)
        <div class="row">
            <div>
                <div style="font-size:13px;font-weight:600;">
                    {{ $k['etablissement']->nom }} — {{ $k['etablissement']->ville }}
                    @if($k['etablissement']->id === $sitePrincipal->id)
                        <span class="pill pg" style="margin-left:6px;">Site principal</span>
                    @else
                        <span class="pill pb" style="margin-left:6px;">Site secondaire</span>
                    @endif
                </div>
                <div style="font-size:11px;color:#888;">
                    {{ number_format($k['nb_apprenants'], 0, ',', ' ') }} apprenants
                    · {{ number_format($k['total_encaisse'], 0, ',', ' ') }} FCFA encaissés
                </div>
            </div>
            <span class="pill {{ $k['etablissement']->statut === 'actif' ? 'pg' : 'pa' }}">
                {{ ucfirst($k['etablissement']->statut) }}
            </span>
        </div>
    @endforeach
</div>

@endsection
