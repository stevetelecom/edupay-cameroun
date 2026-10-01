@extends('layouts.admin')
@section('title', __('admin.gestion_abonnements'))

@push('modals')
{{-- ══ MODAL : Nouvel abonnement ══ --}}
<div id="modal-new-abo" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-lg flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="display:flex;align-items:center;gap:9px;">
        <span class="ep-ico vert ep-ico-side"><span class="material-symbols-outlined">add_circle</span></span>
        {{ __('admin.activer_abonnement_btn') }}
      </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-new-abo')">×</button>
    </div>
    <form method="POST" action="{{ route('admin.abonnements.store') }}" class="flex flex-col flex-1 min-h-0">
      @csrf
      <div class="ep-modal-body space-y-4 overflow-y-auto flex-1 min-h-0">
        <div>
          <label class="lbl">{{ __('messages.etablissement') }} *</label>
          <select name="etablissement_id" required
                  class="select">
            <option value="">-- {{ __('admin.choisir_etablissement') }} --</option>
            @foreach(\App\Models\Etablissement::where('statut','actif')->orderBy('nom')->get() as $etab)
              <option value="{{ $etab->id }}">{{ $etab->nom }} — {{ $etab->ville }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="lbl">{{ __('admin.plan_etoile') }}</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   style="border-color: {{ $plan['couleur'] }}20;"
                   id="plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-lg font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">{{ __('admin.fcfa_mois') }}</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.date_debut') }}</label>
          <input type="date" name="date_debut" id="date-debut-new" required value="{{ now()->format('Y-m-d') }}"
                 onchange="majResume('periode-prevue-new', 'montant-prevu-new', this.value, document.getElementById('duree-mois-new').value, planCourant)"
                 class="inp"/>
        </div>
        <div>
          <label class="lbl">{{ __('admin.duree_abonnement') }}</label>
          <select name="duree_mois" id="duree-mois-new"
                  onchange="majResume('periode-prevue-new', 'montant-prevu-new', document.getElementById('date-debut-new').value, this.value, planCourant)"
                  class="inp">
            @foreach(\App\Models\Abonnement::DUREES_MOIS as $mois)
              <option value="{{ $mois }}">{{ $mois }} {{ __('admin.mois') }}</option>
            @endforeach
          </select>
          {{-- Recapitulatif calcule : impossible de saisir une periode qui ne
               correspond pas a la duree choisie, sans meme lire le code. --}}
          <div id="periode-prevue-new"
               class="mt-2 text-xs text-[#0D9E75] bg-[#E8F7F1] rounded-lg px-3 py-2 font-medium"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.montant_a_encaisser') }}</label>
          <div id="montant-prevu-new"
               class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-bold"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.ref_paiement_recu') }}</label>
          <input type="text" name="reference_paiement" placeholder="{{ __('admin.ph_ref_mtn') }}"
                 class="inp"/>
        </div>
        <div>
          <label class="lbl">{{ __('admin.notes') }}</label>
          <textarea name="notes" rows="2" placeholder="{{ __('admin.notes_ph') }}"
                    class="inp"></textarea>
        </div>
      </div>
      <div class="ep-modal-foot shrink-0">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-new-abo')">{{ __('messages.annuler') }}</button>
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('admin.activer_abonnement_title') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Renouveler ══ --}}
<div id="modal-renew-abo" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-md flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="display:flex;align-items:center;gap:9px;">
        <span class="ep-ico or ep-ico-side"><span class="material-symbols-outlined" aria-hidden="true">autorenew</span></span>
        {{ __('admin.renouveler_abonnement') }}
      </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-renew-abo')">×</button>
    </div>
    <form id="form-renew" method="POST" action="">
      @csrf @method('PATCH')
      <div class="ep-modal-body space-y-4 overflow-y-auto flex-1 min-h-0">
        <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-700">
          {{ __('admin.renouvellement_pour') }} <strong id="renew-nom"></strong><br/>
          {{ __('admin.plan_actuel_label') }} <strong id="renew-plan"></strong>
        </div>
        <div>
          <label class="lbl">{{ __('admin.duree_abonnement') }}</label>
          <select name="duree_mois" id="duree-mois-renew" onchange="majResume('periode-prevue-renew', 'montant-prevu-renew', renouvellementDebut(), this.value, planRenouvellement)"
                  class="inp">
            @foreach(\App\Models\Abonnement::DUREES_MOIS as $mois)
              <option value="{{ $mois }}">{{ $mois }} {{ __('admin.mois') }}</option>
            @endforeach
          </select>
          <div id="periode-prevue-renew"
               class="mt-2 text-xs text-[#0D9E75] bg-[#E8F7F1] rounded-lg px-3 py-2 font-medium"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.montant_a_encaisser') }}</label>
          <div id="montant-prevu-renew"
               class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-bold"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.ref_paiement_recu') }}</label>
          <input type="text" name="reference_paiement" placeholder="{{ __('admin.ph_ref_om') }}"
                 class="inp"/>
        </div>
        <div>
          <label class="lbl">{{ __('admin.notes') }}</label>
          <textarea name="notes" rows="2"
                    class="inp"></textarea>
        </div>
      </div>
      <div class="ep-modal-foot shrink-0">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-renew-abo')">{{ __('messages.annuler') }}</button>
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 20px;background:var(--ep-blue,#185FA5);">
          {{ __('admin.confirmer_renouvellement') }}
        </button>
      </div>
    </form>
  </div>
</div>
{{-- ══ MODAL : Modifier le plan ══ --}}
<div id="modal-edit-abo" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-md flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="display:flex;align-items:center;gap:9px;">
        <span class="ep-ico bleu ep-ico-side"><span class="material-symbols-outlined">tune</span></span>
        {{ __('admin.modifier_plan') }}
      </h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-edit-abo')">×</button>
    </div>
    <form id="form-edit-abo" method="POST" action="">
      @csrf @method('PATCH')
      <div class="ep-modal-body space-y-4 overflow-y-auto flex-1 min-h-0">
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
          {{ __('messages.etablissement') }} : <strong id="edit-abo-nom"></strong><br/>
          {{ __('admin.periode_actuelle') }} : <strong id="edit-abo-periode"></strong>
          <input type="hidden" id="edit-abo-debut" value="">
        </div>
        <div>
          <label class="lbl">{{ __('admin.nouveau_plan') }}</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   id="edit-plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selEditPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-sm font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">{{ __('admin.fcfa_mois') }}</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.duree_abonnement') }}</label>
          <select name="duree_mois" id="duree-mois-edit" onchange="majResume('periode-prevue-edit', 'montant-prevu-edit', document.getElementById('edit-abo-debut').value, this.value, planEdition)"
                  class="inp">
            @foreach(\App\Models\Abonnement::DUREES_MOIS as $mois)
              <option value="{{ $mois }}">{{ $mois }} {{ __('admin.mois') }}</option>
            @endforeach
          </select>
          <div id="periode-prevue-edit"
               class="mt-2 text-xs text-[#0D9E75] bg-[#E8F7F1] rounded-lg px-3 py-2 font-medium"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.montant_a_encaisser') }}</label>
          <div id="montant-prevu-edit"
               class="text-sm text-gray-800 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 font-bold"></div>
        </div>
        <div>
          <label class="lbl">{{ __('admin.ref_paiement') }}</label>
          <input type="text" name="reference_paiement" placeholder="{{ __('admin.ph_ref_mtn') }}"
                 class="inp"/>
        </div>
        <div>
          <label class="lbl">{{ __('admin.notes') }}</label>
          <textarea name="notes" rows="2"
                    class="inp"></textarea>
        </div>
      </div>
      <div class="ep-modal-foot shrink-0">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-edit-abo')">{{ __('messages.annuler') }}</button>
        <button type="submit"
                class="btn-p" style="width:auto;padding:8px 20px;">
          {{ __('messages.enregistrer') }}
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer abonnement ══ --}}
<div id="modal-delete-abo" class="ep-modal-overlay overflow-y-auto"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="ep-modal ep-modal-sm flex flex-col max-h-[calc(100vh-2rem)]">
    <div class="ep-modal-head shrink-0">
      <h3 style="color:var(--ep-red);">{{ __('admin.supprimer_abonnement') }}</h3>
      <button class="ep-modal-close" onclick="fermerModal('modal-delete-abo')">×</button>
    </div>
    <div class="ep-modal-body overflow-y-auto flex-1 min-h-0">
      <p class="text-sm text-gray-600 leading-relaxed">
        {!! __('admin.confirm_suppr_abonnement', ['nom' => '<span id="delete-abo-nom" class="text-red-600"></span>']) !!}
      </p>
    </div>
    <div class="ep-modal-foot shrink-0">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;" onclick="fermerModal('modal-delete-abo')">{{ __('messages.annuler') }}</button>
      <form id="form-delete-abo" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="btn-r" style="width:auto;padding:8px 18px;">
          {{ __('admin.supprimer') }}
        </button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
<div class="ep-bandeau succes" style="align-items:center;margin-bottom:16px;">
  <div class="ep-bandeau-ico"><span class="material-symbols-outlined">check_circle</span></div>
  <div class="ep-bandeau-corps"><div class="ep-bandeau-texte">{{ session('success') }}</div></div>
</div>
@endif
@if(session('error'))
<div class="ep-bandeau danger" style="align-items:center;margin-bottom:16px;">
  <div class="ep-bandeau-ico"><span class="material-symbols-outlined">error</span></div>
  <div class="ep-bandeau-corps"><div class="ep-bandeau-texte">{{ session('error') }}</div></div>
</div>
@endif

{{-- En-tête --}}
<div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:22px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="ep-ico purple ep-ico-entete"><span class="material-symbols-outlined">workspace_premium</span></div>
    <div>
      <h3 style="margin:0;">{{ __('admin.gestion_abonnements') }}</h3>
      <p class="text-sm text-gray-500 mt-0.5 ep-sous-titre" style="margin-top:2px;">{{ __('admin.suivi_abonnements') }}</p>
    </div>
  </div>
  <button onclick="ouvrirModal('modal-new-abo')"
          class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
    {{ __('admin.activer_abonnement_btn') }}
  </button>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
  <div class="kpi ep-kpi">
    <div class="ep-ico vert"><span class="material-symbols-outlined">workspace_premium</span></div>
    <div>
      <div class="kval" id="kpi-actifs" data-ep-count>{{ $stats['actifs'] }}</div>
      <div class="klbl">{{ __('admin.abonnements_actifs') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico or"><span class="material-symbols-outlined">hourglass_bottom</span></div>
    <div>
      <div class="kval" id="kpi-grace" data-ep-count>{{ $stats['grace_period'] }}</div>
      <div class="klbl">{{ __('admin.en_grace_period') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico rouge"><span class="material-symbols-outlined">event_busy</span></div>
    <div>
      <div class="kval" id="kpi-expires" data-ep-count>{{ $stats['expires'] }}</div>
      <div class="klbl">{{ __('admin.expires') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico navy"><span class="material-symbols-outlined">savings</span></div>
    <div>
      <div class="kval" id="kpi-revenus">{{ number_format($stats['revenus_mois'],0,',',' ') }}</div>
      <div class="klbl">{{ __('admin.fcfa_encaisse_mois') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico or"><span class="material-symbols-outlined">autorenew</span></div>
    <div>
      <div class="kval" id="kpi-a-renouveler" data-ep-count>{{ $stats['a_renouveler'] }}</div>
      <div class="klbl">{{ __('admin.a_renouveler') }}</div>
    </div>
  </div>
</div>

{{-- Filtres --}}
<div class="flex flex-wrap items-center gap-3 mb-4">
  <div class="flex flex-wrap gap-2">
    <select id="filter-statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">{{ __('admin.tous_statuts') }}</option>
      <option value="actif">{{ __('admin.actif') }}</option>
      <option value="grace_period">{{ __('admin.grace_period') }}</option>
      <option value="expire">{{ __('admin.expire') }}</option>
    </select>
    <select id="filter-plan" class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">{{ __('admin.tous_plans') }}</option>
      <option value="basique">{{ __('admin.plan_basique') }}</option>
      <option value="standard">{{ __('admin.plan_standard') }}</option>
      <option value="premium">{{ __('admin.plan_premium') }}</option>
    </select>
  </div>
  <div class="flex items-center gap-2 ml-auto">
    <input id="filter-search" type="text" placeholder="{{ __('admin.rechercher_etab') }}"
           class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white w-64" />
    <button type="button" onclick="resetAbonnementFilters()"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">{{ __('admin.reinitialiser') }}</button>
  </div>
</div>

{{-- Tableau responsive --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <table id="dt-abonnements" class="ep-dt text-sm" style="width:100%">
    <thead>
      <tr>
        <th>{{ __('messages.etablissement') }}</th>
        <th>{{ __('admin.plan') }}</th>
        <th>{{ __('admin.periode') }}</th>
        <th>{{ __('messages.statut') }}</th>
        <th>{{ __('admin.montant') }}</th>
        <th data-orderable="false">{{ __('messages.actions') }}</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

@endsection

@push('scripts')
<script>
function ouvrirModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden');
    el.style.display = 'flex';
    // Le scroll de la page derriere le modal est verrouille : sans cela on
    // empilait la barre de defilement du document ET celle du modal, et le
    // fond bougeait pendant qu'on remplissait le formulaire.
    document.body.style.overflow = 'hidden';
    // Le premier champ reçoit le focus pour que la tabulation reste dans le
    // modal au lieu de repartir vers la page cachee dessous.
    var premier = el.querySelector('input:not([type=hidden]), select, textarea');
    if (premier) { premier.focus(); }
}
function fermerModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden');
    el.style.display = 'none';
    // On ne libere le scroll que si plus aucun modal n'est ouvert.
    var ouvert = Array.prototype.some.call(
        document.querySelectorAll('[id^="modal-"]'),
        function (m) { return m.style.display === 'flex'; }
    );
    if (!ouvert) { document.body.style.overflow = ''; }
}
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    var ouvert = document.querySelector('[id^="modal-"][style*="display: flex"]');
    if (ouvert) { fermerModal(ouvert.id); }
});
document.addEventListener('DOMContentLoaded', function () {
    majPeriode(document.getElementById('date-debut-new').value,
               document.getElementById('duree-mois-new').value, 'periode-prevue-new');
    majPeriode(renouvellementDebut(),
               document.getElementById('duree-mois-renew').value, 'periode-prevue-renew');
});
const PLANS = @json(\App\Models\Abonnement::PLANS);

/** Montant total a encaisser = prix du plan x duree souscrite. */
function majMontant(cible, plan, mois) {
    var el = document.getElementById(cible);
    if (!el) return;
    var prix = PLANS[plan] ? PLANS[plan].montant : null;
    if (prix === null) { el.textContent = ''; return; }
    var n = parseInt(mois, 10) || 1;
    el.innerHTML = nb(prix * n) + ' FCFA'
        + ' <span class="opacity-70 font-normal">(' + nb(prix) + ' \u00d7 ' + n + ')</span>';
}
function nb(n) { return n.toLocaleString('fr-FR'); }
function majResume(ciblePeriode, cibleMontant, debut, mois, plan) {
    majPeriode(debut, mois, ciblePeriode);
    majMontant(cibleMontant, plan, mois);
}
function selPlan(plan) {
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('plan-card-' + p);
        if (card) card.style.opacity = p === plan ? '1' : '0.5';
    });
    planCourant = plan;
    majMontant('montant-prevu-new', plan, document.getElementById('duree-mois-new').value);
}
var planCourant = '';
var planRenouvellement = '';
var planEdition = '';
/**
 * Récapitulatif de la période qui sera enregistrée.
 *
 * Le calcul serveur fait foi (Abonnement::periode) ; celui-ci n'est qu'un
 * aperçu. Le deuxieme peut diverger sur un cas limite de fin de mois
 * (31 janvier + 1 mois), le serveur tranche toujours.
 */
// meme algorithme que Abonnement::dateFinPour() : ajout de mois sans
// debordement (le 31/01 + 1 mois vaut le 28/02, pas le 03/03), puis -1 jour.
function ajouterMoisSansDebordement(d, n) {
    var jour = d.getDate();
    var cible = new Date(d.getTime());
    cible.setDate(1);
    cible.setMonth(cible.getMonth() + n);
    var dernierJour = new Date(cible.getFullYear(), cible.getMonth() + 1, 0).getDate();
    cible.setDate(Math.min(jour, dernierJour));
    return cible;
}
function fmtJour(d) {
    return String(d.getDate()).padStart(2, '0') + '/'
        + String(d.getMonth() + 1).padStart(2, '0') + '/' + d.getFullYear();
}
function majPeriode(debut, mois, cible) {
    var el = document.getElementById(cible);
    if (!el) return;
    if (!debut) { el.textContent = ''; return; }
    var d = new Date(debut + 'T00:00:00');
    if (isNaN(d)) { el.textContent = ''; return; }
    var fin = ajouterMoisSansDebordement(d, parseInt(mois, 10));
    fin.setDate(fin.getDate() - 1);
    var grace = new Date(fin.getTime());
    grace.setDate(grace.getDate() + 7);
    el.textContent = debut.split('-').reverse().join('/') + ' au ' + fmtJour(fin)
        + ' · {{ __('admin.grace_libelle') }} ' + fmtJour(grace);
}
function renouvellementDebut() {
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}
function modifierAbo(id, nom, planActuel, debut, duree, periode) {
    document.getElementById('edit-abo-nom').textContent = nom;
    document.getElementById('edit-abo-debut').value = debut;
    document.getElementById('edit-abo-periode').textContent = periode;

    // Une periode historique peut avoir une duree hors catalogue (les lignes
    // #6 / #7 valent 13 mois). Sans option correspondante, le select renvoyait
    // une valeur vide et l'enregistrement reecrasait la periode en 1 mois.
    const select = document.getElementById('duree-mois-edit');
    const catalogue = @json(\App\Models\Abonnement::DUREES_MOIS);
    const connue = Array.from(select.options).some(o => o.value === String(duree));
    if (!connue && !select.querySelector('option[data-hors-offre]')) {
        const opt = document.createElement('option');
        opt.value = String(duree);
        opt.textContent = duree + ' {{ __('admin.mois') }} ({{ __('admin.duree_hors_offre') }})';
        opt.setAttribute('data-hors-offre', '1');
        select.appendChild(opt);
    }
    select.value = duree;
    planEdition = planActuel;
    majResume('periode-prevue-edit', 'montant-prevu-edit', debut, duree, planActuel);
    document.getElementById('form-edit-abo').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id;
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('edit-plan-card-' + p);
        if (card) {
            card.style.opacity = '1';
            card.style.borderColor = p === planActuel ? '#0D9E75' : '';
            const input = card.querySelector('input');
            if (input) input.checked = (p === planActuel);
        }
    });
    ouvrirModal('modal-edit-abo');
}
function selEditPlan(plan) {
    majMontant('montant-prevu-edit', plan, document.getElementById('duree-mois-edit').value);
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('edit-plan-card-' + p);
        if (card) card.style.borderColor = p === plan ? '#0D9E75' : '';
    });
}
function supprimerAbo(id, nom) {
    document.getElementById('delete-abo-nom').textContent = nom;
    document.getElementById('form-delete-abo').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id;
    ouvrirModal('modal-delete-abo');
}
function renouveler(id, nom, plan) {
    planRenouvellement = plan;
    document.getElementById('renew-nom').textContent  = nom;
    document.getElementById('renew-plan').textContent = PLANS[plan] ? PLANS[plan].nom : plan;
    majResume('periode-prevue-renew', 'montant-prevu-renew', renouvellementDebut(),
              document.getElementById('duree-mois-renew').value, plan);
    document.getElementById('form-renew').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id + '/renouveler';
    ouvrirModal('modal-renew-abo');
}

var dtAbonnements;

$(document).ready(function() {
    if ($.fn.DataTable.isDataTable('#dt-abonnements')) {
        $('#dt-abonnements').DataTable().destroy();
    }

    dtAbonnements = epDT('#dt-abonnements', {
        serverSide: true,
        processing: true,
        ajax: {
            url: '{{ route("admin.abonnements.datatable") }}',
            type: 'GET',
            data: function(d) {
                d.statut = $('#filter-statut').val();
                d.plan   = $('#filter-plan').val();
            }
        },
        columns: [
            { data: 0, orderable: true,  responsivePriority: 1 }, // Établissement
            { data: 1, orderable: true,  responsivePriority: 5 }, // Plan
            { data: 2, orderable: true,  responsivePriority: 3 }, // Période
            { data: 3, orderable: true,  responsivePriority: 4 }, // Statut
            { data: 4, orderable: true,  responsivePriority: 6 }, // Montant
            { data: 5, orderable: false, responsivePriority: 2 }, // Actions
        ],
        order: [[0, 'asc']],
    });

    $('#filter-statut, #filter-plan').on('change', function() {
        dtAbonnements.ajax.reload();
    });

    var searchTimer;
    $('#filter-search').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dtAbonnements.search($('#filter-search').val()).draw();
        }, 300);
    });
});

function resetAbonnementFilters() {
    $('#filter-statut').val('');
    $('#filter-plan').val('');
    $('#filter-search').val('');
    dtAbonnements.search('').draw();
}
</script>
@endpush
