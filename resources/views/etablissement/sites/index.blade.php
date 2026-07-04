@extends('layouts.etablissement')
@section('title', 'Multi-sites')

@push('modals')

{{-- ══ MODAL : Ajouter un site ══ --}}
<div id="modal-add-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>+ Ajouter un nouveau site</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-add-site')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.sites.store') }}">
      @csrf
      <div class="ep-modal-body">

        {{-- Infos du site --}}
        <div class="seclbl" style="margin-top:0;">Informations du site</div>
        <div class="g2">
          <div>
            <div class="lbl">Nom du site *</div>
            <input class="inp" name="nom" value="{{ old('nom') }}"
                   required placeholder="Ex : Annexe Mvolyé" />
          </div>
          <div>
            <div class="lbl">Ville *</div>
            <input class="inp" name="ville" value="{{ old('ville') }}"
                   required placeholder="Ex : Yaoundé" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Quartier (optionnel)</div>
            <input class="inp" name="quartier" value="{{ old('quartier') }}"
                   placeholder="Ex : Mvolyé" />
          </div>
          <div>
            <div class="lbl">Téléphone du site *</div>
            <input class="inp" name="telephone" value="{{ old('telephone') }}"
                   required placeholder="6XX XXX XXX" />
          </div>
        </div>
        <div class="lbl">Email du site *</div>
        <input class="inp" type="email" name="email" value="{{ old('email') }}"
               required placeholder="annexe@lycee.cm" />

        <div class="divider"></div>

        {{-- Directeur du site --}}
        <div class="seclbl">Compte du directeur de ce site</div>
        <div class="g2">
          <div>
            <div class="lbl">Prénom *</div>
            <input class="inp" name="directeur_prenom" value="{{ old('directeur_prenom') }}"
                   required placeholder="Jean" />
          </div>
          <div>
            <div class="lbl">Nom *</div>
            <input class="inp" name="directeur_nom" value="{{ old('directeur_nom') }}"
                   required placeholder="MVONDO" />
          </div>
        </div>
        <div class="lbl">Email du directeur *</div>
        <input class="inp" type="email" name="directeur_email" value="{{ old('directeur_email') }}"
               required placeholder="directeur@annexe.cm" />
        <div style="background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#1A4F8A;margin-top:-4px;">
          Un mot de passe provisoire sera généré et envoyé à cette adresse par email.
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-add-site')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Créer le site
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Modifier un site ══ --}}
<div id="modal-edit-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>Modifier le site</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-edit-site')">×</button>
    </div>
    <form method="POST" id="form-edit-site" action="">
      @csrf
      @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Nom du site *</div>
            <input class="inp" name="nom" id="edit-nom" required />
          </div>
          <div>
            <div class="lbl">Ville *</div>
            <input class="inp" name="ville" id="edit-ville" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">Quartier (optionnel)</div>
            <input class="inp" name="quartier" id="edit-quartier" />
          </div>
          <div>
            <div class="lbl">Téléphone du site *</div>
            <input class="inp" name="telephone" id="edit-telephone" required />
          </div>
        </div>
        <div class="lbl">Email du site *</div>
        <input class="inp" type="email" name="email" id="edit-email" required />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-edit-site')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Détail d'un site ══ --}}
<div id="modal-detail-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="detail-site-titre">Détail du site</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-site')">×</button>
    </div>
    <div class="ep-modal-body" id="detail-site-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;"
              onclick="epModal.close('modal-detail-site')">Fermer</button>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(!($multiSitesAutorise ?? true))
<div style="background:#FEF3DC;border:1.5px solid #E8A020;border-radius:10px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
    <div style="font-size:20px;flex-shrink:0;">🔒</div>
    <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:2px;">
            Fonctionnalité non disponible — Plan {{ ucfirst($planActuel ?? 'Basique') }}
        </div>
        <div style="font-size:12px;color:#92400E;opacity:.85;line-height:1.5;">
            La gestion multi-sites est disponible à partir du plan <strong>Standard</strong>.
            Vous pouvez consulter vos sites existants mais pas en créer de nouveaux.
            Contactez EduPay pour upgrader votre plan.
        </div>
    </div>
</div>
@endif

@if(session('success'))
<div class="epcard" style="background:#d1fae5;border-left:4px solid #059669;color:#065f46;margin-bottom:16px;padding:12px 16px;">
  ✓ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="epcard" style="background:var(--ep-red-lt);border-left:4px solid var(--ep-red);color:#9B2C2C;margin-bottom:16px;padding:12px 16px;">
  {{ session('error') }}
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
  <div>
    <div style="font-size:17px;font-weight:700;">Gestion du groupe scolaire</div>
    <div style="font-size:12px;color:#888;">{{ $sitePrincipal->nom }} et ses sites rattachés</div>
  </div>
  @if($estSitePrincipal && Auth::user()->hasRole('directeur'))
  <button class="btn-p" style="width:auto;"
          onclick="epModal.open('modal-add-site')">
    + Ajouter un site
  </button>
  @endif
</div>

{{-- KPIs groupe --}}
<div class="g3" style="margin-bottom:20px;">
  <div class="kpi">
    <div class="kval">{{ $kpisParSite->count() }}</div>
    <div class="klbl">Sites dans le groupe</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($totalGroupeApprenants,0,',',' ') }}</div>
    <div class="klbl">Apprenants (tous sites)</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($totalGroupeEncaisse,0,',',' ') }}</div>
    <div class="klbl">FCFA encaissés (groupe)</div>
  </div>
</div>

{{-- Liste des sites --}}
<div class="epcard" style="padding:0;overflow:hidden;">
  <div style="padding:14px 18px;border-bottom:1px solid #f0f0f0;">
    <span style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;">
      SITES DU GROUPE
    </span>
  </div>

  @foreach($kpisParSite as $k)
  @php $site = $k['etablissement']; @endphp
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f5f5f5;">
    <div style="flex:1;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div style="font-weight:600;font-size:14px;">{{ $site->nom }}</div>
        @if($site->id === $sitePrincipal->id)
          <span class="pill pg">Site principal</span>
        @else
          <span class="pill pb">Site secondaire</span>
        @endif
        <span class="pill {{ $site->statut === 'actif' ? 'pg' : 'pa' }}">
          {{ ucfirst($site->statut) }}
        </span>
      </div>
      <div style="font-size:12px;color:#888;">
        📍 {{ $site->ville }}@if($site->quartier), {{ $site->quartier }}@endif
        &nbsp;·&nbsp; 📞 {{ $site->telephone }}
        &nbsp;·&nbsp; ✉ {{ $site->email }}
      </div>
    </div>

    {{-- KPIs inline --}}
    <div style="display:flex;gap:20px;margin:0 24px;text-align:center;flex-shrink:0;">
      <div>
        <div style="font-size:16px;font-weight:700;">{{ number_format($k['nb_apprenants'],0,',',' ') }}</div>
        <div style="font-size:10px;color:#888;">apprenants</div>
      </div>
      <div>
        <div style="font-size:16px;font-weight:700;color:#085041;">{{ number_format($k['total_encaisse'],0,',',' ') }}</div>
        <div style="font-size:10px;color:#888;">FCFA encaissés</div>
      </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <button onclick="voirSite(
        '{{ addslashes($site->nom) }}',
        '{{ $site->id === $sitePrincipal->id ? 'Site principal' : 'Site secondaire' }}',
        '{{ ucfirst($site->statut) }}',
        '{{ addslashes($site->ville) }}',
        '{{ addslashes($site->quartier ?? '') }}',
        '{{ addslashes($site->telephone) }}',
        '{{ addslashes($site->email) }}',
        {{ $k['nb_apprenants'] }},
        {{ $k['total_encaisse'] }}
    )"
    style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;white-space:nowrap;">
      Voir le détail
    </button>
    @if($estSitePrincipal && Auth::user()->hasRole('directeur') && $site->id !== $sitePrincipal->id)
    <button onclick="modifierSite(
        {{ $site->id }},
        '{{ addslashes($site->nom) }}',
        '{{ addslashes($site->ville) }}',
        '{{ addslashes($site->quartier ?? '') }}',
        '{{ addslashes($site->telephone) }}',
        '{{ addslashes($site->email) }}'
    )"
    style="color:#1A4F8A;background:none;border:none;font-size:12px;cursor:pointer;white-space:nowrap;">
      Modifier
    </button>
    <form method="POST" action="{{ route('etablissement.sites.destroy', $site) }}"
          onsubmit="return confirm('Supprimer définitivement le site « {{ addslashes($site->nom) }} » ?');"
          style="display:inline;">
      @csrf
      @method('DELETE')
      <button type="submit"
              style="color:#9B2C2C;background:none;border:none;font-size:12px;cursor:pointer;white-space:nowrap;">
        Supprimer
      </button>
    </form>
    @endif
    </div>
  </div>
  @endforeach
</div>

{{-- Note d'information --}}
<div style="margin-top:14px;background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:12px 16px;font-size:12px;color:#1A4F8A;border-left:3px solid #1A4F8A;">
  <strong>CDC E12 :</strong> Seul le directeur du site principal peut ajouter un nouveau site.
  Chaque site dispose de son propre espace Back-office et de ses propres utilisateurs internes.
</div>

@endsection

@push('scripts')
<script>
function modifierSite(id, nom, ville, quartier, telephone, email) {
    document.getElementById('edit-nom').value = nom;
    document.getElementById('edit-ville').value = ville;
    document.getElementById('edit-quartier').value = quartier;
    document.getElementById('edit-telephone').value = telephone;
    document.getElementById('edit-email').value = email;
    document.getElementById('form-edit-site').action = '/etablissement/sites/' + id;
    epModal.open('modal-edit-site');
}

function voirSite(nom, type, statut, ville, quartier, tel, email, nbApp, encaisse) {
    document.getElementById('detail-site-titre').textContent = nom;

    var typeClass = type === 'Site principal' ? 'pg' : 'pb';
    var statutClass = statut === 'Actif' ? 'pg' : 'pa';

    var html = '<div class="g2" style="gap:16px;margin-bottom:16px;">'
        + '<div><div class="lbl">Type</div><span class="pill '+typeClass+'">'+type+'</span></div>'
        + '<div><div class="lbl">Statut</div><span class="pill '+statutClass+'">'+statut+'</span></div>'
        + '<div><div class="lbl">Ville</div><div style="font-weight:600;">'+ville+(quartier?' — '+quartier:'')+'</div></div>'
        + '<div><div class="lbl">Téléphone</div><div>'+tel+'</div></div>'
        + '<div><div class="lbl">Email</div><div>'+email+'</div></div>'
        + '</div>'
        + '<div class="g2" style="gap:12px;">'
        + '<div class="kpi"><div class="kval">'+Number(nbApp).toLocaleString('fr-FR')+'</div><div class="klbl">Apprenants</div></div>'
        + '<div class="kpi"><div class="kval" style="color:#085041;">'+Number(encaisse).toLocaleString('fr-FR')+'</div><div class="klbl">FCFA encaissés</div></div>'
        + '</div>';

    document.getElementById('detail-site-body').innerHTML = html;
    epModal.open('modal-detail-site');
}

@if($errors->any() && $estSitePrincipal)
document.addEventListener('DOMContentLoaded', function(){
    epModal.open('modal-add-site');
});
@endif
</script>
@endpush
