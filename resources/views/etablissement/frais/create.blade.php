@extends('layouts.etablissement')

@section('title', __('etablissement.nouvelle_categorie_frais'))

@section('content')

<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;">
    <a href="{{ route('etablissement.frais.index') }}" style="color:#888;text-decoration:none;">
        ← {{ __('etablissement.frais_echeanciers_titre') }}
    </a>
    <span style="color:#ddd;">/</span>
    <span style="font-weight:600;">{{ __('etablissement.nouvelle_categorie_bread') }}</span>
</div>

@if($errors->any())
<div style="background:var(--ep-red-lt);border-left:3px solid var(--ep-red);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
    <strong>{{ __('etablissement.erreurs_validation') }}</strong>
    <ul style="margin:6px 0 0 16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('etablissement.frais.store') }}" id="frais-form">
@csrf

<div class="g2" style="align-items:start;gap:16px;">

{{-- ── Colonne gauche ── --}}
<div>
<div class="epcard" style="margin-bottom:14px;">
    <div class="seclbl" style="margin-top:0;">{{ __('etablissement.categorie_frais') }}</div>

    <div class="lbl">{{ __('etablissement.lbl_nom') }}</div>
    <select class="select" id="sel-nom" onchange="syncNom(this)">
        <option value="">{{ __('etablissement.choisir') }}</option>
        @foreach([
            'fs_scolarite' => 'Scolarité',
            'fs_inscription' => 'Inscription',
            'fs_cantine' => 'Cantine',
            'fs_internat' => 'Internat',
            'fs_transport' => 'Transport',
            'fs_examens' => "Frais d'examen",
        ] as $key => $opt)
            <option value="{{ $opt }}" {{ old('nom')==$opt?'selected':'' }}>{{ __('etablissement.'.$key) }}</option>
        @endforeach
        <option value="__autre__" {{ old('nom') && !in_array(old('nom'),['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen'])?'selected':'' }}>
            {{ __('etablissement.autre') }}
        </option>
    </select>
    <input class="inp" name="nom" id="inp-nom"
           placeholder="{{ __('etablissement.nom_personnalise_ph') }}"
           value="{{ old('nom') }}"
           style="display:none;margin-top:8px;" />
    @error('nom')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

    <div class="lbl">{{ __('etablissement.annee_scolaire') }}</div>
    <select class="select" name="annee_scolaire">
        @foreach(['2024-2025','2025-2026','2026-2027'] as $y)
        <option value="{{ $y }}" {{ old('annee_scolaire','2025-2026')==$y?'selected':'' }}>{{ $y }}</option>
        @endforeach
    </select>

    <div class="lbl">{{ __('etablissement.montant_total_fcfa') }}</div>
    <input class="inp" type="number" name="montant_total" id="montant-total"
           placeholder="{{ __('etablissement.ph_montant_total') }}" min="0" step="500"
           value="{{ old('montant_total') }}"
           oninput="recalc()" />
    @error('montant_total')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

    <div class="lbl">{{ __('etablissement.nb_tranches_label') }}</div>

    {{-- Boutons tranches — PAS de label wrappant un radio, on gère tout en JS --}}
    <div style="display:flex;gap:8px;margin-bottom:12px;">
        @foreach([1,2,3] as $n)
        <div id="tbl-{{ $n }}"
             data-n="{{ $n }}"
             style="flex:1;padding:10px;border:2px solid {{ old('nb_tranches_max',1)==$n?'var(--ep-teal)':'#ddd' }};
                    border-radius:var(--radius-md);cursor:pointer;text-align:center;
                    background:{{ old('nb_tranches_max',1)==$n?'var(--ep-teal-lt)':'#fff' }};
                    user-select:none;"
             onclick="setN({{ $n }})">
            <div style="font-size:14px;font-weight:700;pointer-events:none;">{{ $n }}</div>
            <div style="font-size:10px;color:#888;pointer-events:none;">{{ $n>1 ? __('etablissement.tranches_unit') : __('etablissement.tranche_unit') }}</div>
        </div>
        @endforeach
    </div>
    {{-- Radio caché pour la soumission du formulaire --}}
    <div style="display:none;">
        @foreach([1,2,3] as $n)
        <input type="radio" name="nb_tranches_max" id="radio-{{ $n }}" value="{{ $n }}"
               {{ old('nb_tranches_max',1)==$n?'checked':'' }}>
        @endforeach
    </div>

    <div class="lbl" style="margin-bottom:8px;">{{ __('etablissement.options') }}</div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;cursor:pointer;">
        <input type="checkbox" name="fractionnable" value="1"
               {{ old('fractionnable',1)?'checked':'' }} />
        {{ __('etablissement.paiement_fractionne_autorise') }}
    </label>

    <div class="lbl">{{ __('etablissement.description_optionnel') }}</div>
    <textarea class="inp" name="description" rows="2"
              placeholder="{{ __('etablissement.ph_description_frais') }}">{{ old('description') }}</textarea>
</div>

<button type="submit" class="btn-p" style="width:100%;">{{ __('etablissement.enregistrer_arrow') }}</button>
<a href="{{ route('etablissement.frais.index') }}" class="btn-o"
   style="width:100%;display:block;text-align:center;margin-top:8px;">{{ __('etablissement.annuler') }}</a>
</div>

{{-- ── Colonne droite : échéancier ── --}}
<div>
<div class="epcard">
    <div class="seclbl" style="margin-top:0;">{{ __('etablissement.echeancier_paiement') }}</div>
    <div id="ech-container">
        <div style="color:#aaa;font-size:12px;text-align:center;padding:20px 0;">
            {{ __('etablissement.echeancier_hint') }}
        </div>
    </div>
</div>
<div style="margin-top:12px;background:var(--ep-gold-lt);border-radius:var(--radius-md);
            padding:12px 14px;font-size:11px;color:#854F0B;line-height:1.7;
            border-left:3px solid var(--ep-gold);">
    {{ __('etablissement.cdc_note') }}
</div>
</div>

</div>
</form>

@push('scripts')
<script>
// ── État global ──
var nbT   = {{ old('nb_tranches_max', 1) }};
var total = parseFloat("{{ old('montant_total', 0) }}") || 0;

// ── Traductions injectées ──
@php
$fraisI18n = [
    'paiement_integral' => __('etablissement.paiement_integral'),
    'tranche1_50'        => __('etablissement.tranche1_50'),
    'solde_50'           => __('etablissement.solde_50'),
    'tranche1_40'        => __('etablissement.tranche1_40'),
    'tranche2_30'        => __('etablissement.tranche2_30'),
    'solde_30'           => __('etablissement.solde_30'),
    'libelle'            => __('etablissement.libelle'),
    'montant_fcfa'       => __('etablissement.montant_fcfa'),
    'date_limite'        => __('etablissement.date_limite'),
];
@endphp
var i18n = @json($fraisI18n);

// ── Sélection du nombre de tranches ──
function setN(n) {
    nbT = n;
    [1, 2, 3].forEach(function(i) {
        var box   = document.getElementById('tbl-' + i);
        var radio = document.getElementById('radio-' + i);
        var isActive = (i === n);
        box.style.border     = isActive ? '2px solid var(--ep-teal)' : '2px solid #ddd';
        box.style.background = isActive ? 'var(--ep-teal-lt)' : '#fff';
        radio.checked = isActive;
    });
    renderEch();
}

// ── Recalcul quand le montant change ──
function recalc() {
    total = parseFloat(document.getElementById('montant-total').value) || 0;
    renderEch();
}

// ── Génère les champs échéancier ──
function renderEch() {
    var c = document.getElementById('ech-container');
    c.innerHTML = '';

    if (!nbT || nbT < 1) return;

    var base = Math.floor(total / nbT);

    for (var i = 1; i <= nbT; i++) {
        var montant  = (i === nbT) ? (total - base * (nbT - 1)) : base;
        var libDefaut = nbT === 1
            ? i18n.paiement_integral
            : (nbT === 2
                ? (i === 1 ? i18n.tranche1_50 : i18n.solde_50)
                : (i === 1 ? i18n.tranche1_40 : (i === 2 ? i18n.tranche2_30 : i18n.solde_30)));

        var html = '<div style="border:1px solid #eee;border-radius:var(--radius-md);padding:14px;margin-bottom:10px;">'
            + '<div style="font-size:11px;font-weight:700;color:#0F6E56;text-transform:uppercase;letter-spacing:.05em;margin-bottom:10px;">'
            + 'Tranche ' + i + ' / ' + nbT
            + '</div>'
            + '<div class="lbl">' + i18n.libelle + '</div>'
            + '<input class="inp" name="echeances[' + (i-1) + '][libelle]" placeholder="' + libDefaut + '" value="' + libDefaut + '" />'
            + '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">'
            + '<div>'
            + '<div class="lbl">' + i18n.montant_fcfa + '</div>'
            + '<input class="inp" type="number" name="echeances[' + (i-1) + '][montant]" value="' + montant + '" min="0" step="500" style="margin-bottom:0;" required />'
            + '</div>'
            + '<div>'
            + '<div class="lbl">' + i18n.date_limite + '</div>'
            + '<input class="inp" type="date" name="echeances[' + (i-1) + '][date_echeance]" style="margin-bottom:0;" required />'
            + '</div>'
            + '</div>'
            + '</div>';

        c.insertAdjacentHTML('beforeend', html);
    }
}

// ── Gestion du champ Nom (select + input custom) ──
function syncNom(sel) {
    var inp = document.getElementById('inp-nom');
    if (sel.value === '__autre__') {
        inp.style.display = 'block';
        inp.required = true;
        inp.name = 'nom';
        sel.name = '';
    } else {
        inp.style.display = 'none';
        inp.required = false;
        inp.name = '';
        sel.name = 'nom';
    }
}

// ── Init au chargement ──
document.addEventListener('DOMContentLoaded', function() {
    // Initialise le nom
    var sel = document.getElementById('sel-nom');
    var inp = document.getElementById('inp-nom');
    var oldNom = "{{ old('nom') }}";
    var presets = ['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen'];
    if (oldNom && presets.indexOf(oldNom) === -1 && oldNom !== '') {
        inp.style.display = 'block';
        inp.name = 'nom';
        inp.required = true;
        sel.name = '';
    } else {
        sel.name = 'nom';
    }

    // Initialise les tranches et l'échéancier
    setN(nbT);
});
</script>
@endpush

@endsection
