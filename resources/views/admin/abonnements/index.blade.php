@extends('layouts.admin')
@section('title', 'Gestion des abonnements')

@push('modals')
{{-- ══ MODAL : Nouvel abonnement ══ --}}
<div id="modal-new-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">+ Activer un abonnement</h3>
      <button onclick="fermerModal('modal-new-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form method="POST" action="{{ route('admin.abonnements.store') }}">
      @csrf
      <div class="p-6 space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Établissement *</label>
          <select name="etablissement_id" required
                  class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75] bg-white">
            <option value="">-- Choisir un établissement --</option>
            @foreach(\App\Models\Etablissement::where('statut','actif')->orderBy('nom')->get() as $etab)
              <option value="{{ $etab->id }}">{{ $etab->nom }} — {{ $etab->ville }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Plan *</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   style="border-color: {{ $plan['couleur'] }}20;"
                   id="plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-lg font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">FCFA/mois</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Date de début *</label>
          <input type="date" name="date_debut" required value="{{ now()->format('Y-m-d') }}"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement reçu</label>
          <input type="text" name="reference_paiement" placeholder="Ex: MTN-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2" placeholder="Remarques éventuelles..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-new-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          Activer l'abonnement
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Renouveler ══ --}}
<div id="modal-renew-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">🔄 Renouveler l'abonnement</h3>
      <button onclick="fermerModal('modal-renew-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form id="form-renew" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="bg-blue-50 rounded-lg p-3 text-sm text-blue-700">
          Renouvellement pour : <strong id="renew-nom"></strong><br/>
          Plan actuel : <strong id="renew-plan"></strong>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement reçu</label>
          <input type="text" name="reference_paiement" placeholder="Ex: OM-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-renew-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#185FA5] hover:bg-[#144d8a] text-white font-semibold rounded-lg">
          Confirmer le renouvellement
        </button>
      </div>
    </form>
  </div>
</div>
{{-- ══ MODAL : Modifier le plan ══ --}}
<div id="modal-edit-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b">
      <h3 class="font-bold text-gray-900">Modifier le plan</h3>
      <button onclick="fermerModal('modal-edit-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <form id="form-edit-abo" method="POST" action="">
      @csrf @method('PATCH')
      <div class="p-6 space-y-4">
        <div class="bg-gray-50 rounded-lg p-3 text-sm text-gray-700">
          Établissement : <strong id="edit-abo-nom"></strong>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-2">Nouveau plan *</label>
          <div class="grid grid-cols-3 gap-3">
            @foreach(\App\Models\Abonnement::PLANS as $key => $plan)
            <label class="border-2 rounded-lg p-3 cursor-pointer text-center transition-all hover:border-[#0D9E75]"
                   id="edit-plan-card-{{ $key }}">
              <input type="radio" name="plan" value="{{ $key }}"
                     class="hidden" onclick="selEditPlan('{{ $key }}')">
              <div class="font-bold text-sm" style="color:{{ $plan['couleur'] }}">{{ $plan['nom'] }}</div>
              <div class="text-sm font-black text-gray-800">{{ number_format($plan['montant'],0,',',' ') }}</div>
              <div class="text-xs text-gray-500">FCFA/mois</div>
            </label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Référence paiement</label>
          <input type="text" name="reference_paiement" placeholder="Ex: MTN-XXXXXXX"
                 class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Notes</label>
          <textarea name="notes" rows="2"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"></textarea>
        </div>
      </div>
      <div class="flex justify-end gap-3 px-6 py-4 border-t">
        <button type="button" onclick="fermerModal('modal-edit-abo')"
                class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
        <button type="submit"
                class="px-5 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
          Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ MODAL : Supprimer abonnement ══ --}}
<div id="modal-delete-abo" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center"
     onclick="if(event.target===this)fermerModal(this.id)">
  <div class="bg-white rounded-xl w-full max-w-sm mx-4 shadow-xl">
    <div class="flex items-center justify-between px-6 py-4 border-b border-red-100">
      <h3 class="font-bold text-red-600">Supprimer l'abonnement</h3>
      <button onclick="fermerModal('modal-delete-abo')" class="text-gray-400 hover:text-gray-600 text-2xl">×</button>
    </div>
    <div class="p-6">
      <p class="text-sm text-gray-600 leading-relaxed">
        Vous allez supprimer l'abonnement de <strong id="delete-abo-nom" class="text-red-600"></strong>.<br/><br/>
        L'établissement n'aura plus de plan actif. Cette action est irréversible.
      </p>
    </div>
    <div class="flex justify-end gap-3 px-6 py-4 border-t">
      <button onclick="fermerModal('modal-delete-abo')"
              class="px-4 py-2 text-sm border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">Annuler</button>
      <form id="form-delete-abo" method="POST" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit"
                class="px-5 py-2 text-sm bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
          Supprimer
        </button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg mb-4">✓ {{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg mb-4">✗ {{ session('error') }}</div>
@endif

{{-- En-tête --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
  <div>
    <h1 class="text-xl font-bold text-gray-900">Gestion des abonnements</h1>
    <p class="text-sm text-gray-500 mt-0.5">Suivi et activation des plans établissements</p>
  </div>
  <button onclick="ouvrirModal('modal-new-abo')"
          class="px-4 py-2 text-sm bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold rounded-lg">
    + Activer un abonnement
  </button>
</div>

{{-- KPIs --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#0D9E75]">{{ $stats['actifs'] }}</div>
    <div class="text-xs text-gray-500 mt-1">Abonnements actifs</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#E8A020]">{{ $stats['grace_period'] }}</div>
    <div class="text-xs text-gray-500 mt-1">En grace period</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-red-500">{{ $stats['expires'] }}</div>
    <div class="text-xs text-gray-500 mt-1">Expirés</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div class="text-2xl font-bold text-[#0B2545]">{{ number_format($stats['revenus_mois'],0,',',' ') }}</div>
    <div class="text-xs text-gray-500 mt-1">FCFA encaissés (mois)</div>
  </div>
</div>

{{-- Filtres --}}
<div class="flex flex-wrap gap-3 mb-4">
  <form method="GET" class="flex flex-wrap gap-2">
    <select name="statut" onchange="this.form.submit()"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">Tous les statuts</option>
      <option value="actif" {{ request('statut')==='actif' ? 'selected' : '' }}>Actif</option>
      <option value="grace_period" {{ request('statut')==='grace_period' ? 'selected' : '' }}>Grace period</option>
      <option value="expire" {{ request('statut')==='expire' ? 'selected' : '' }}>Expiré</option>
    </select>
    <select name="plan" onchange="this.form.submit()"
            class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white">
      <option value="">Tous les plans</option>
      <option value="basique" {{ request('plan')==='basique' ? 'selected' : '' }}>Basique</option>
      <option value="standard" {{ request('plan')==='standard' ? 'selected' : '' }}>Standard</option>
      <option value="premium" {{ request('plan')==='premium' ? 'selected' : '' }}>Premium</option>
    </select>
  </form>
</div>

{{-- Tableau responsive --}}
<div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[700px]">
      <thead>
        <tr class="border-b border-gray-100 bg-gray-50">
          <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Établissement</th>
          <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Plan</th>
          <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Période</th>
          <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Statut</th>
          <th class="text-left text-xs font-semibold text-gray-500 uppercase px-4 py-3">Montant</th>
          <th class="text-right text-xs font-semibold text-gray-500 uppercase px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        @forelse($abonnements as $abo)
        @php
          $couleurs = [
            'actif'        => 'bg-green-50 text-green-700 border-green-200',
            'grace_period' => 'bg-amber-50 text-amber-700 border-amber-200',
            'expire'       => 'bg-red-50 text-red-700 border-red-200',
            'suspendu'     => 'bg-gray-50 text-gray-600 border-gray-200',
          ];
          $planCouleurs = [
            'basique'  => 'bg-teal-50 text-teal-700',
            'standard' => 'bg-blue-50 text-blue-700',
            'premium'  => 'bg-amber-50 text-amber-700',
          ];
        @endphp
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-3">
            <div class="font-semibold text-gray-900 text-sm">{{ $abo->etablissement->nom ?? '—' }}</div>
            <div class="text-xs text-gray-500">{{ $abo->etablissement->ville ?? '' }}</div>
          </td>
          <td class="px-4 py-3">
            <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $planCouleurs[$abo->plan] ?? '' }}">
              {{ ucfirst($abo->plan) }}
            </span>
          </td>
          <td class="px-4 py-3">
            <div class="text-xs text-gray-700">{{ $abo->date_debut->format('d/m/Y') }} → {{ $abo->date_fin->format('d/m/Y') }}</div>
            @if($abo->enGracePeriod())
              <div class="text-xs text-amber-600">Grace jusqu'au {{ $abo->grace_period_fin->format('d/m/Y') }}</div>
            @else
              <div class="text-xs text-gray-400">{{ $abo->joursRestants() }} jours restants</div>
            @endif
          </td>
          <td class="px-4 py-3">
            <span class="text-xs font-medium px-2 py-1 rounded-full border {{ $couleurs[$abo->statut] ?? '' }}">
              {{ ucfirst(str_replace('_', ' ', $abo->statut)) }}
            </span>
          </td>
          <td class="px-4 py-3 font-semibold text-gray-800">
            {{ number_format($abo->montant_mensuel, 0, ',', ' ') }} FCFA
          </td>
          <td class="px-4 py-3 text-right">
            <div class="flex items-center justify-end gap-2 flex-wrap">
              @if(in_array($abo->statut, ['actif', 'grace_period', 'expire']))
              <button onclick="renouveler({{ $abo->id }}, '{{ addslashes($abo->etablissement->nom ?? '') }}', '{{ ucfirst($abo->plan) }}')"
                      class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Renouveler
              </button>
              @endif
              <button onclick="modifierAbo({{ $abo->id }}, '{{ addslashes($abo->etablissement->nom ?? '') }}', '{{ $abo->plan }}')"
                      class="text-xs text-amber-600 hover:text-amber-800 font-medium">
                Modifier
              </button>
              <button onclick="supprimerAbo({{ $abo->id }}, '{{ addslashes($abo->etablissement->nom ?? '') }}')"
                      class="text-xs text-red-500 hover:text-red-700 font-medium">
                Supprimer
              </button>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
            Aucun abonnement enregistré.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="px-4 py-3 border-t border-gray-100">
    {{ $abonnements->links() }}
  </div>
</div>

@endsection

@push('scripts')
<script>
function ouvrirModal(id) {
    var el = document.getElementById(id);
    el.classList.remove('hidden');
    el.style.display = 'flex';
}
function fermerModal(id) {
    var el = document.getElementById(id);
    el.classList.add('hidden');
    el.style.display = 'none';
}
function selPlan(plan) {
    ['basique','standard','premium'].forEach(p => {
        const card = document.getElementById('plan-card-' + p);
        if (card) card.style.opacity = p === plan ? '1' : '0.5';
    });
}
function modifierAbo(id, nom, planActuel) {
    document.getElementById('edit-abo-nom').textContent = nom;
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
    document.getElementById('renew-nom').textContent  = nom;
    document.getElementById('renew-plan').textContent = plan;
    document.getElementById('form-renew').action =
        "{{ url(config('app.admin_url_prefix', 'admin-ep2026') . '/abonnements') }}/" + id + '/renouveler';
    ouvrirModal('modal-renew-abo');
}
</script>
@endpush
