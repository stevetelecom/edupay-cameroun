@extends('layouts.payeur')

@section('title', __('payeur.modifier_titre', ['prenom' => $apprenant->prenom, 'nom' => $apprenant->nom]))

@push('modals')

{{-- ══ MODAL : Confirmer le détachement ══ --}}
<div id="modal-detach-apprenant" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm ep-modal-danger">
    <div class="ep-modal-head">
      <h3>{{ __('payeur.retirer_confirm_titre', ['prenom' => $apprenant->prenom]) }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-detach-apprenant')">×</button>
    </div>
    <div class="ep-modal-body">
      <p style="font-size:13px;color:#555;line-height:1.6;">
        {!! __('payeur.retirer_confirm_body', ['prenom' => $apprenant->prenom, 'nom' => $apprenant->nom]) !!}<br><br>
        {{ __('payeur.retirer_confirm_irreversible') }}
      </p>
    </div>
    <div class="ep-modal-foot">
      <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
              onclick="epModal.close('modal-detach-apprenant')">{{ __('payeur.annuler') }}</button>
      <form method="POST" action="{{ route('payeur.apprenant.detach', $apprenant) }}" style="display:inline;">
        @csrf @method('DELETE')
        <button type="submit" class="btn-r" style="width:auto;padding:8px 18px;">
          {{ __('payeur.retirer') }} {{ $apprenant->prenom }}
        </button>
      </form>
    </div>
  </div>
</div>

@endpush

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">← {{ __('payeur.retour') }}</a>
    </div>

    <div style="max-width:560px;margin:0 auto;">

        <div style="font-size:17px;font-weight:700;margin-bottom:4px;">
            {{ __('payeur.modifier_titre', ['prenom' => $apprenant->prenom, 'nom' => $apprenant->nom]) }}
        </div>
        <div style="font-size:12px;color:#888;margin-bottom:20px;">
            {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
        </div>

        @if($errors->any())
            <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:12px 16px;margin-bottom:18px;">
                <div style="font-size:13px;font-weight:600;color:#991B1B;margin-bottom:6px;">{{ __('payeur.erreurs') }}</div>
                <ul style="margin:0;padding-left:18px;">
                    @foreach($errors->all() as $e)
                        <li style="font-size:12px;color:#B91C1C;">{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="epcard">

            <div style="margin-bottom:16px;">
                <div class="lbl">{{ __('messages.etablissement') }} *</div>
                <div style="position:relative;margin-bottom:8px;">
                    <input type="text" id="etab-search"
                           value="{{ $apprenant->etablissement->nom ?? '' }}"
                           placeholder="{{ __('payeur.rechercher_etablissement') }}"
                           style="width:100%;padding:11px 12px 11px 36px;border:1px solid #ddd;border-radius:8px;font-size:13px;outline:none;"
                           oninput="filtrerEtabs(this.value)"
                           onfocus="document.getElementById('etab-liste').style.display='block'" />
                    <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;"
                         width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </div>
                <div id="etab-liste"
                     style="display:none;border:1px solid #e0e0e0;border-radius:8px;background:#fff;max-height:200px;overflow-y:auto;box-shadow:0 4px 16px rgba(0,0,0,.1);">
                    @foreach($etablissements as $etab)
                        <div class="etab-item"
                             data-id="{{ $etab->id }}"
                             data-nom="{{ $etab->nom }}"
                             data-ville="{{ $etab->ville }}"
                             onclick="selectionnerEtab(this)"
                             style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;justify-content:space-between;{{ $apprenant->etablissement_id == $etab->id ? 'background:#f0fdf4;' : '' }}">
                            <div>
                                <div style="font-size:13px;font-weight:600;">{{ $etab->nom }}</div>
                                <div style="font-size:11px;color:#888;">{{ $etab->ville }} · {{ $etab->type }}</div>
                            </div>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"
                                 class="etab-check" style="opacity:{{ $apprenant->etablissement_id == $etab->id ? '1' : '0' }};">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('payeur.apprenant.update', $apprenant) }}">
                @csrf @method('PUT')
                <input type="hidden" name="etablissement_id" id="h-etab-id"
                       value="{{ old('etablissement_id', $apprenant->etablissement_id) }}">
                <input type="hidden" name="etablissement_nom" id="h-etab-nom"
                       value="{{ old('etablissement_nom', $apprenant->etablissement->nom ?? '') }}">

                <div class="g2">
                    <div>
                        <div class="lbl">{{ __('payeur.prenom_lbl') }} *</div>
                        <input type="text" name="prenom" class="inp"
                               value="{{ old('prenom', $apprenant->prenom) }}" required>
                    </div>
                    <div>
                        <div class="lbl">{{ __('payeur.nom_lbl') }} *</div>
                        <input type="text" name="nom" class="inp"
                               value="{{ old('nom', $apprenant->nom) }}" required>
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.classe') }} *</div>
                        <input type="text" name="classe" class="inp"
                               value="{{ old('classe', $apprenant->classe) }}" required>
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.matricule') }}</div>
                        <input type="text" name="matricule" class="inp"
                               value="{{ old('matricule', $apprenant->matricule) }}">
                    </div>
                </div>

                @if($apprenant->paiements()->exists())
                    <div style="background:var(--ep-gold-lt);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#854F0B;">
                        {{ __('payeur.des_paiements_existent') }}
                    </div>
                @endif

                <div style="display:flex;gap:10px;margin-top:8px;">
                    <a href="{{ route('payeur.dashboard') }}"
                       class="btn-o" style="width:auto;padding:10px 20px;">{{ __('payeur.annuler') }}</a>
                    <button type="submit" class="btn-p" style="flex:1;">
                        {{ __('payeur.enregistrer_modifications') }}
                    </button>
                </div>
            </form>

        </div>

        @unless($apprenant->paiements()->exists())
            <div style="margin-top:16px;text-align:center;">
                <button onclick="epModal.open('modal-detach-apprenant')"
                        style="background:none;border:none;color:var(--ep-red);font-size:12px;cursor:pointer;text-decoration:underline;">
                    {{ __('payeur.retirer_de_mon_compte', ['prenom' => $apprenant->prenom]) }}
                </button>
            </div>
        @endunless

    </div>

@endsection

@push('scripts')
<script>
function filtrerEtabs(q) {
    var items = document.querySelectorAll('.etab-item');
    var ql = q.toLowerCase().trim();
    items.forEach(function(item) {
        var nom   = item.dataset.nom.toLowerCase();
        var ville = item.dataset.ville.toLowerCase();
        item.style.display = (!ql || nom.includes(ql) || ville.includes(ql)) ? 'flex' : 'none';
    });
    document.getElementById('etab-liste').style.display = 'block';
}

function selectionnerEtab(el) {
    document.querySelectorAll('.etab-item').forEach(function(i) {
        i.style.background = '';
        i.querySelector('.etab-check').style.opacity = '0';
    });
    el.style.background = '#f0fdf4';
    el.querySelector('.etab-check').style.opacity = '1';
    document.getElementById('h-etab-id').value  = el.dataset.id;
    document.getElementById('h-etab-nom').value = el.dataset.nom;
    document.getElementById('etab-search').value = el.dataset.nom;
    document.getElementById('etab-liste').style.display = 'none';
}

document.addEventListener('click', function(e) {
    var liste  = document.getElementById('etab-liste');
    var search = document.getElementById('etab-search');
    if (liste && search && !liste.contains(e.target) && e.target !== search) {
        liste.style.display = 'none';
    }
});
</script>
@endpush
