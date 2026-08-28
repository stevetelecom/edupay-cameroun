@extends('layouts.etablissement')
@section('title', __('messages.multi_sites'))

@push('modals')

{{-- ══ MODAL : Ajouter un site ══ --}}
<div id="modal-add-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.ajouter_site') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-add-site')">×</button>
    </div>
    <form method="POST" action="{{ route('etablissement.sites.store') }}">
      @csrf
      <div class="ep-modal-body">

        {{-- Infos du site --}}
        <div class="seclbl" style="margin-top:0;">{{ __('etablissement.infos_site') }}</div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.nom_site') }}</div>
            <input class="inp" name="nom" value="{{ old('nom') }}"
                   required placeholder="Ex : Annexe Mvolyé" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.ville_site') }}</div>
            <input class="inp" name="ville" value="{{ old('ville') }}"
                   required placeholder="Ex : Yaoundé" />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.quartier_opt') }}</div>
            <input class="inp" name="quartier" value="{{ old('quartier') }}"
                   placeholder="Ex : Mvolyé" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.tel_site') }}</div>
            <input class="inp" name="telephone" value="{{ old('telephone') }}"
                   required placeholder="6XX XXX XXX" />
          </div>
        </div>
        <div class="lbl">{{ __('etablissement.email_site') }}</div>
        <input class="inp" type="email" name="email" value="{{ old('email') }}"
               required placeholder="annexe@lycee.cm" />

        <div class="divider"></div>

        {{-- Directeur du site --}}
        <div class="seclbl">{{ __('etablissement.compte_directeur') }}</div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.lbl_prenom') }}</div>
            <input class="inp" name="directeur_prenom" value="{{ old('directeur_prenom') }}"
                   required placeholder="Jean" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
            <input class="inp" name="directeur_nom" value="{{ old('directeur_nom') }}"
                   required placeholder="MVONDO" />
          </div>
        </div>
        <div class="lbl">{{ __('etablissement.email_directeur') }}</div>
        <input class="inp" type="email" name="directeur_email" value="{{ old('directeur_email') }}"
               required placeholder="directeur@annexe.cm" />
        <div style="background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:10px 12px;font-size:12px;color:#1A4F8A;margin-top:-4px;">
          {{ __('etablissement.mdp_provisoire_hint') }}
        </div>
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-add-site')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('etablissement.creer_site') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Modifier un site ══ --}}
<div id="modal-edit-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-lg">
    <div class="ep-modal-head">
      <h3>{{ __('etablissement.modifier_site') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-edit-site')">×</button>
    </div>
    <form method="POST" id="form-edit-site" action="">
      @csrf
      @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.nom_site') }}</div>
            <input class="inp" name="nom" id="edit-nom" required />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.ville_site') }}</div>
            <input class="inp" name="ville" id="edit-ville" required />
          </div>
        </div>
        <div class="g2">
          <div>
            <div class="lbl">{{ __('etablissement.quartier_opt') }}</div>
            <input class="inp" name="quartier" id="edit-quartier" />
          </div>
          <div>
            <div class="lbl">{{ __('etablissement.tel_site') }}</div>
            <input class="inp" name="telephone" id="edit-telephone" required />
          </div>
        </div>
        <div class="lbl">{{ __('etablissement.email_site') }}</div>
        <input class="inp" type="email" name="email" id="edit-email" required />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-edit-site')">{{ __('etablissement.annuler') }}</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('etablissement.enregistrer') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Détail d'un site ══ --}}
<div id="modal-detail-site" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-md">
    <div class="ep-modal-head">
      <h3 id="detail-site-titre">{{ __('etablissement.detail_site') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detail-site')">×</button>
    </div>
    <div class="ep-modal-body" id="detail-site-body"></div>
    <div class="ep-modal-foot">
      <button class="btn-p" style="width:auto;padding:8px 20px;"
              onclick="epModal.close('modal-detail-site')">{{ __('etablissement.fermer') }}</button>
    </div>
  </div>
</div>

@endpush

@section('content')

<script>
    window.EP_LANG = window.EP_LANG || {};
    window.EP_LANG.sites = {!! json_encode([
        'confirmSupprSite' => __('etablissement.confirmer_suppr_site'),
        'typePrincipal'    => __('etablissement.site_principal'),
        'typeSecondaire'   => __('etablissement.site_secondaire'),
        'statutActif'      => __('etablissement.actif'),
        'typeLbl'          => __('etablissement.type_lbl'),
        'statutLbl'        => __('etablissement.statut'),
        'emailLbl'         => __('etablissement.email'),
        'apprenantsLbl'    => __('etablissement.apprenants'),
        'fcfaEncaisseLbl'  => __('etablissement.fcfa_encaisse'),
        'villeLbl'         => 'Ville',
        'telephoneLbl'     => 'Téléphone',
    ]) !!};
</script>

@if(!($multiSitesAutorise ?? true))
<div style="background:#FEF3DC;border:1.5px solid #E8A020;border-radius:10px;padding:14px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
    <div style="font-size:20px;flex-shrink:0;">🔒</div>
    <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:#92400E;margin-bottom:2px;">
            {{ __('etablissement.fonctionnalite_indispo', ['plan' => ucfirst($planActuel ?? 'Basique')]) }}
        </div>
        <div style="font-size:12px;color:#92400E;opacity:.85;line-height:1.5;">
            {!! __('etablissement.multi_sites_indispo') !!}
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
    <div style="font-size:17px;font-weight:700;">{{ __('etablissement.gestion_groupe') }}</div>
    <div style="font-size:12px;color:#888;">{{ __('etablissement.sites_rattaches', ['nom' => $sitePrincipal->nom]) }}</div>
  </div>
  @if($estSitePrincipal && Auth::user()->hasRole('directeur'))
  <button class="btn-p" style="width:auto;"
          onclick="epModal.open('modal-add-site')">
    {{ __('etablissement.ajouter_site_btn') }}
  </button>
  @endif
</div>

{{-- KPIs groupe --}}
<div class="g3" style="margin-bottom:20px;">
  <div class="kpi">
    <div class="kval">{{ $kpisParSite->count() }}</div>
    <div class="klbl">{{ __('etablissement.sites_groupe') }}</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($totalGroupeApprenants,0,',',' ') }}</div>
    <div class="klbl">{{ __('etablissement.apprenants_tous_sites') }}</div>
  </div>
  <div class="kpi">
    <div class="kval">{{ number_format($totalGroupeEncaisse,0,',',' ') }}</div>
    <div class="klbl">{{ __('etablissement.fcfa_groupe') }}</div>
  </div>
</div>

{{-- Liste des sites --}}
<div class="epcard" style="padding:0;overflow:hidden;">
  <div style="padding:14px 18px;border-bottom:1px solid #f0f0f0;">
    <span style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.05em;">
      {{ __('etablissement.sites_du_groupe') }}
    </span>
  </div>

  @foreach($kpisParSite as $k)
  @php $site = $k['etablissement']; @endphp
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #f5f5f5;">
    <div style="flex:1;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <div style="font-weight:600;font-size:14px;">{{ $site->nom }}</div>
        @if($site->id === $sitePrincipal->id)
          <span class="pill pg">{{ __('etablissement.site_principal') }}</span>
        @else
          <span class="pill pb">{{ __('etablissement.site_secondaire') }}</span>
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
        <div style="font-size:10px;color:#888;">{{ __('etablissement.apprenants_word') }}</div>
      </div>
      <div>
        <div style="font-size:16px;font-weight:700;color:#085041;">{{ number_format($k['total_encaisse'],0,',',' ') }}</div>
        <div style="font-size:10px;color:#888;">{{ __('etablissement.fcfa_encaisse') }}</div>
      </div>
    </div>

    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <button onclick="voirSite(
        '{{ addslashes($site->nom) }}',
        '{{ $site->id === $sitePrincipal->id ? __('etablissement.site_principal') : __('etablissement.site_secondaire') }}',
        '{{ ucfirst($site->statut) }}',
        '{{ addslashes($site->ville) }}',
        '{{ addslashes($site->quartier ?? '') }}',
        '{{ addslashes($site->telephone) }}',
        '{{ addslashes($site->email) }}',
        {{ $k['nb_apprenants'] }},
        {{ $k['total_encaisse'] }}
    )"
    style="color:#185FA5;background:none;border:none;font-size:12px;cursor:pointer;white-space:nowrap;">
      {{ __('etablissement.voir_detail') }}
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
      {{ __('etablissement.dt_title_modifier') }}
    </button>
    <form method="POST" action="{{ route('etablissement.sites.destroy', $site) }}"
          onsubmit="return confirmerSupprSite('{{ addslashes($site->nom) }}');"
          style="display:inline;">
      @csrf
      @method('DELETE')
      <button type="submit"
              style="color:#9B2C2C;background:none;border:none;font-size:12px;cursor:pointer;white-space:nowrap;">
        {{ __('etablissement.dt_title_supprimer') }}
      </button>
    </form>
    @endif
    </div>
  </div>
  @endforeach
</div>

{{-- Note d'information --}}
<div style="margin-top:14px;background:var(--ep-blue-lt);border-radius:var(--radius-md);padding:12px 16px;font-size:12px;color:#1A4F8A;border-left:3px solid #1A4F8A;">
  {{ __('etablissement.cdc_note_e12') }}
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

function confirmerSupprSite(nom) {
    return confirm(EP_LANG.sites.confirmSupprSite.replace(':nom', nom));
}

function voirSite(nom, type, statut, ville, quartier, tel, email, nbApp, encaisse) {
    document.getElementById('detail-site-titre').textContent = nom;

    var typeClass = type === EP_LANG.sites.typePrincipal ? 'pg' : 'pb';
    var statutClass = statut === EP_LANG.sites.statutActif ? 'pg' : 'pa';

    var html = '<div class="g2" style="gap:16px;margin-bottom:16px;">'
        + '<div><div class="lbl">' + EP_LANG.sites.typeLbl + '</div><span class="pill ' + typeClass + '">' + type + '</span></div>'
        + '<div><div class="lbl">' + EP_LANG.sites.statutLbl + '</div><span class="pill ' + statutClass + '">' + statut + '</span></div>'
        + '<div><div class="lbl">' + EP_LANG.sites.villeLbl + '</div><div style="font-weight:600;">' + ville + (quartier ? ' — ' + quartier : '') + '</div></div>'
        + '<div><div class="lbl">' + EP_LANG.sites.telephoneLbl + '</div><div>' + tel + '</div></div>'
        + '<div><div class="lbl">' + EP_LANG.sites.emailLbl + '</div><div>' + email + '</div></div>'
        + '</div>'
        + '<div class="g2" style="gap:12px;">'
        + '<div class="kpi"><div class="kval">' + Number(nbApp).toLocaleString('fr-FR') + '</div><div class="klbl">' + EP_LANG.sites.apprenantsLbl + '</div></div>'
        + '<div class="kpi"><div class="kval" style="color:#085041;">' + Number(encaisse).toLocaleString('fr-FR') + '</div><div class="klbl">' + EP_LANG.sites.fcfaEncaisseLbl + '</div></div>'
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
