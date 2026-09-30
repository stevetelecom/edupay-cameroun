@extends('layouts.admin')
@section('title', __('messages.commissions'))

@push('modals')
{{-- MODAL TAUX PAR PROFIL D'ABONNEMENT — CDC S0 #3. Un champ par plan, avec
     pour chacun le pourcentage converti en direct. Le plancher AangaraaPay est
     pose sur le HTML ET revalide cote serveur : le cout du prestataire ne peut
     pas etre depasse. --}}
<div id="modal-modifier-taux" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.taux_par_profil') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-taux')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="width:40px;height:40px;background:#FEF3DC;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <span class="material-symbols-outlined" style="font-size:20px;color:#E8A020;" aria-hidden="true">percent</span>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.taux_par_profil') }}</div>
          <div style="font-size:12px;color:#888;">{{ __('admin.taux_par_profil_aide') }}</div>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.commissions.taux-plans') }}">
        @csrf @method('PATCH')

        @foreach($tauxParPlan as $plan => $taux)
          <div style="margin-bottom:14px;">
            <label for="taux-{{ $plan }}"
                   style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
              {{ \App\Models\Abonnement::PLANS[$plan]['nom'] ?? ucfirst($plan) }}
            </label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="number" name="taux_{{ $plan }}" id="taux-{{ $plan }}"
                     step="0.001" min="{{ $tauxAangaraa }}" max="1" required
                     value="{{ number_format($taux, 4, '.', '') }}"
                     data-taux="{{ $plan }}"
                     style="flex:1;padding:10px 12px;font-size:14px;font-weight:600;border:2px solid #E8A020;border-radius:8px;outline:none;text-align:center;" />
              <span style="font-size:13px;color:#888;">= <span id="pct-{{ $plan }}">{{ number_format($taux * 100, 1) }}%</span></span>
            </div>
          </div>
        @endforeach

        <div style="background:#F2F7FC;border-left:3px solid #1E5A8A;border-radius:6px;padding:10px 12px;margin-bottom:16px;">
          <div style="font-size:11px;color:#1E5A8A;display:flex;align-items:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:15px;" aria-hidden="true">info</span>
            <span>{{ __('admin.taux_plancher_aangaraa', ['pct' => number_format($tauxAangaraa * 100, 2, ',', '')]) }}</span>
          </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-modifier-taux')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#E8A020;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ __('messages.enregistrer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL PRELEVER --}}
<div id="modal-prelever" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.marquer_prelevee') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-prelever')">x</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;margin-bottom:16px;">
        {{ __('admin.confirm_marquer_prelevee') }}
      </p>
      <form id="form-prelever" method="POST">
        @csrf @method('PATCH')
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-prelever')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ __('admin.confirmer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endpush

@section('content')

<div class="ep-entete ep-entete-page" style="justify-content:space-between;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:12px;">
    <div class="ep-ico or ep-ico-entete"><span class="material-symbols-outlined">trending_up</span></div>
    <div>
    <h3 style="margin:0;">{{ __('messages.commissions') }}</h3>
    <p class="text-sm text-gray-500 mt-0.5 ep-sous-titre" style="margin-top:2px;">{{ __('admin.suivi_commission') }}</p>
    </div>
  </div>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-4 gap-4 mb-6">
  <div class="kpi ep-kpi">
    <div class="ep-ico or"><span class="material-symbols-outlined">trending_up</span></div>
    <div>
      <div class="kval" data-ep-count>{{ number_format($stats['total_mois'], 0, ',', ' ') }}</div>
      <div class="klbl">{{ __('admin.fcfa_ce_mois') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico bleu"><span class="material-symbols-outlined">receipt_long</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['nb_mois'] }}</div>
      <div class="klbl">{{ __('admin.ce_mois') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico or"><span class="material-symbols-outlined">pending_actions</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['calculees'] }}</div>
      <div class="klbl">{{ __('admin.a_prelever') }}</div>
    </div>
  </div>
  <div class="kpi ep-kpi">
    <div class="ep-ico vert"><span class="material-symbols-outlined">savings</span></div>
    <div>
      <div class="kval" data-ep-count>{{ $stats['prelevees'] }}</div>
      <div class="klbl">{{ __('admin.prelevees') }}</div>
    </div>
  </div>
  {{-- Argent bloque : reverse en echec OU dont le sort est inconnu. Ces
       reversements partent de la carte ci-dessus, pas du chiffre « à prélever »
       (qui ne compte que les 'calculee'), l'argent était donc invisible. --}}
  <div class="kpi ep-kpi" {{ $stats['a_traiter'] > 0 ? 'style=background:var(--ep-red-lt);border:1.5px solid rgba(217,64,64,.35)' : '' }}>
    <div class="ep-ico {{ $stats['a_traiter'] > 0 ? 'rouge' : 'navy' }}"><span class="material-symbols-outlined">{{ $stats['a_traiter'] > 0 ? 'gpp_bad' : 'gpp_good' }}</span></div>
    <div>
      <div class="kval" data-ep-count style="{{ $stats['a_traiter'] > 0 ? 'color:#9B2C2C' : '' }}">{{ $stats['a_traiter'] }}</div>
      <div class="klbl" style="{{ $stats['a_traiter'] > 0 ? 'color:#B03A2E' : '' }}">
        {{ __('admin.a_traiter') }} —
        {{ number_format($stats['montant_bloque'], 0, ',', ' ') }} FCFA
      </div>
    </div>
  </div>
</div>

@if($stats['a_traiter'] > 0)
<div class="ep-bandeau danger">
  <div class="ep-bandeau-ico">
    <span class="material-symbols-outlined">report</span>
  </div>
  <div class="ep-bandeau-corps">
    <div class="ep-bandeau-titre">
      {{ __('admin.a_traiter_titre') }}
      <span class="ep-bandeau-badge">{{ $stats['a_traiter'] }}</span>
    </div>
    <div class="ep-bandeau-texte">{!! __('admin.a_traiter_texte') !!}</div>
    <a href="{{ route('admin.commissions.index', ['statut' => 'echec']) }}"
       style="display:inline-flex;align-items:center;gap:5px;margin-top:8px;font-size:12px;font-weight:700;color:#991B1B;text-decoration:underline;">
      {{ __('admin.voir_echecs') }} →
    </a>
  </div>
</div>
@endif

{{-- Taux par profil d'abonnement — CDC S0 #3 : « Configuration du taux de
     commission preleve par transaction selon le profil d'abonnement ».
     Le taux affich dans le tableau est celui fige sur la commission au moment
     du prelevement, pas le taux global. --}}
<div class="ep-bandeau attente" style="align-items:center;flex-wrap:wrap;gap:12px;">
  <div class="ep-bandeau-ico">
    <span class="material-symbols-outlined">percent</span>
  </div>
  <div class="ep-bandeau-corps">
    <div class="ep-bandeau-titre">{{ __('admin.taux_par_profil') }}</div>
    <div class="ep-bandeau-texte" style="display:flex;gap:14px;flex-wrap:wrap;margin-top:2px;">
      @foreach($tauxParPlan as $plan => $taux)
        <span>
          <span class="text-xs" style="color:#854F0B;">{{ \App\Models\Abonnement::PLANS[$plan]['nom'] ?? ucfirst($plan) }}</span>
          &nbsp;<strong style="font-size:14px;color:#854F0B;">{{ number_format($taux * 100, 2, ',', '') }}%</strong>
        </span>
      @endforeach
    </div>
  </div>
  <button onclick="ouvrirModifierTauxParPlan()" class="btn-p" style="width:auto;display:inline-flex;align-items:center;gap:6px;padding:9px 16px;font-size:12px;flex-shrink:0;">
    <span class="material-symbols-outlined" style="font-size:15px;">edit</span>
    {{ __('admin.modifier_taux') }}
  </button>
  <div style="width:100%;font-size:11px;color:#92400E;display:flex;align-items:center;gap:5px;">
    <span class="material-symbols-outlined" style="font-size:14px;">info</span>
    {{ __('admin.taux_plancher_aangaraa', ['pct' => number_format($tauxAangaraa * 100, 2, ',', '')]) }}
  </div>
</div>

{{-- Filtres --}}
<div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
  <form method="GET" action="{{ route('admin.commissions.index') }}" class="flex items-center gap-3 flex-wrap">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="{{ __('admin.rechercher_ecole') }}"
           class="flex-1 min-w-45 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]" />
    <select name="etablissement_id" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]">
      <option value="">{{ __('admin.tous_etablissements') }}</option>
      @foreach($etablissements as $e)
      <option value="{{ $e->id }}" {{ request('etablissement_id')==$e->id ? 'selected' : '' }}>{{ $e->nom }}</option>
      @endforeach
    </select>
    <select name="statut" class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#E8A020]">
      <option value="">{{ __('admin.tous_statuts') }}</option>
      <option value="calculee" {{ request('statut')==='calculee' ? 'selected' : '' }}>{{ __('admin.a_prelever') }}</option>
      <option value="en_cours" {{ request('statut')==='en_cours' ? 'selected' : '' }}>{{ __('admin.en_cours') }}</option>
      <option value="prelevee" {{ request('statut')==='prelevee' ? 'selected' : '' }}>{{ __('admin.prelevees') }}</option>
      <option value="a_verifier" {{ request('statut')==='a_verifier' ? 'selected' : '' }}>{{ __('admin.a_verifier') }}</option>
      <option value="echec" {{ request('statut')==='echec' ? 'selected' : '' }}>{{ __('admin.echec') }}</option>
    </select>
    <button type="submit" class="bg-[#E8A020] hover:bg-[#cc8c1a] text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filtrer
    </button>
    @if(request()->hasAny(['search','statut','etablissement_id']))
    <a href="{{ route('admin.commissions.index') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2">{{ __('admin.reinitialiser') }}</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div class="responsive-admin-table-container">
    <table class="responsive-admin-table text-sm">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('messages.etablissement') }}</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.transaction_col') }}</th>
        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.montant_tx') }}</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.taux') }}</th>
        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.commission') }}</th>
        {{-- Colonne NET : c'est ce montant que ReverserEtablissementJob vire
             reellement a l'etablissement (montant tx - commission). Sans elle,
             l'admin rapprochait des commissions sans jamais voir le virement. --}}
        <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.net_reverse') }}</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('messages.statut') }}</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('messages.actions') }}</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @forelse($commissions as $c)
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3">
          <div class="font-semibold text-gray-900">{{ $c->etablissement->nom ?? '—' }}</div>
          <div class="text-xs text-gray-400">{{ $c->created_at->format('d/m/Y') }}</div>
        </td>
        <td class="px-4 py-3">
          <div class="text-gray-700 font-mono text-xs">{{ $c->paiement->reference ?? '—' }}</div>
        </td>
        <td class="px-4 py-3 text-right text-gray-700">
          {{ number_format($c->montant_transaction, 0, ',', ' ') }} FCFA
        </td>
        <td class="px-4 py-3 text-center">
          {{-- Taux FIGE au moment du prelevement : c'est celui-la qui a ete
               preleve, pas le taux configure aujourd'hui. --}}
          <span class="text-xs font-semibold text-[#E8A020]" title="{{ __('admin.taux_preleve_fige') }}">
            {{ number_format($c->taux * 100, 1) }}%
          </span>
        </td>
        <td class="px-4 py-3 text-right font-bold text-[#E8A020]">
          {{ number_format($c->montant_commission, 0, ',', ' ') }} FCFA
        </td>
        <td class="px-4 py-3 text-right font-semibold text-gray-700">
          {{ number_format($c->montant_net_etablissement, 0, ',', ' ') }} FCFA
        </td>
        <td class="px-4 py-3">
          @if($c->statut === 'prelevee')
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-800">{{ __('admin.prelevee') }}</span>
          @elseif($c->statut === 'en_cours')
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-blue-100 text-blue-800">{{ __('admin.en_cours') }}</span>
          @elseif($c->statut === 'a_verifier')
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-orange-100 text-orange-800" title="{{ $c->reversement_erreur }}">{{ __('admin.a_verifier') }}</span>
          @elseif($c->statut === 'echec')
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-800" title="{{ $c->reversement_erreur }}">{{ __('admin.echec') }}</span>
          @else
          <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-yellow-100 text-yellow-800">{{ __('admin.a_prelever') }}</span>
          @endif

          @if($c->reversement_erreur && $c->statut !== 'prelevee')
          <div class="text-[10px] text-gray-500 mt-1 leading-snug">{{ \Illuminate\Support\Str::limit($c->reversement_erreur, 90) }}</div>
          @endif

          @if($c->paiement && $c->paiement->statut === 'rembourse')
          <div class="text-[10px] text-purple-700 bg-purple-50 rounded px-1.5 py-0.5 mt-1 inline-block">{{ __('admin.paiement_rembourse') }}</div>
          @endif
        </td>
        <td class="px-4 py-3">
          <div class="flex items-center justify-center gap-1.5">
            {{-- Marquer prelevee --}}
            @if($c->statut === 'calculee')
            <button onclick="ouvrirPrelever({{ $c->id }})"
                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-green-50 hover:bg-green-100 text-green-600 transition-colors" title="{{ __('admin.marquer_prelevee_btn') }}">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            @endif
            {{-- Relancer le reversement. Volontairement absent en « a verifier » :
                 l'argent est peut-etre deja parti, seul l'artisan peut forcer. --}}
            @if(in_array($c->statut, ['echec', 'calculee'], true))
            <form method="POST" action="{{ route('admin.commissions.rejouer', $c) }}"
                  onsubmit="return confirm('{{ __('admin.confirmer_rejeu') }}');" class="inline">
              @csrf
              @method('PATCH')
              <button type="submit"
                      class="w-7 h-7 flex items-center justify-center rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 transition-colors" title="{{ __('admin.rejouer_reversement') }}">
                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/></svg>
              </button>
            </form>
            @endif
          </div>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-400">{{ __('admin.aucune_commission') }}</td>
      </tr>
      @endforelse
    </tbody>
    </table>
  </div>
  @if($commissions->hasPages())
  <div class="px-4 py-3 border-t border-gray-100">{{ $commissions->links() }}</div>
  @endif
</div>

@endsection

@push('scripts')
<script>
// Un taux par profil d'abonnement (CDC S0 #3). Le formulaire est rendu avec
// ses valeurs : la modale ne fait que l'ouvrir, plus de copie de valeur a
// synchroniser, donc plus d.ecart possible entre l'ecran et la validation.
function ouvrirModifierTauxParPlan() {
    epModal.open('modal-modifier-taux');
}
function ouvrirPrelever(id) {
    document.getElementById('form-prelever').action = '/admin-ep2026/commissions/' + id + '/prelever';
    epModal.open('modal-prelever');
}
// Pourcentage en direct, un par plan.
document.querySelectorAll('[data-taux]').forEach(function (input) {
    var plan = input.getAttribute('data-taux');
    var sortie = document.getElementById('pct-' + plan);
    if (!sortie) return;
    input.addEventListener('input', function () {
        sortie.textContent = (parseFloat(this.value || 0) * 100).toFixed(1) + '%';
    });
});
</script>
@endpush
