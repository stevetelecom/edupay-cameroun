@extends('layouts.etablissement')

@section('title', 'Nouvelle catégorie de frais')

@section('content')

<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;">
    <a href="{{ route('etablissement.frais.index') }}" style="color:#888;text-decoration:none;">
        ← Frais &amp; Échéanciers
    </a>
    <span style="color:#ddd;">/</span>
    <span style="font-weight:600;">Nouvelle catégorie</span>
</div>

@if($errors->any())
<div style="background:var(--ep-red-lt);border-left:3px solid var(--ep-red);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
    <strong>Erreurs de validation :</strong>
    <ul style="margin:6px 0 0 16px;">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('etablissement.frais.store') }}">
@csrf

<div class="g2" style="align-items:start;gap:16px;">

{{-- ── Colonne gauche : catégorie ── --}}
<div>
<div class="epcard" style="margin-bottom:14px;">
    <div class="seclbl" style="margin-top:0;">Catégorie de frais</div>

    <div class="lbl">Nom *</div>
    <select class="select" id="sel-nom" onchange="syncNom(this)">
        <option value="">-- Choisir --</option>
        @foreach(['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen'] as $opt)
            <option value="{{ $opt }}" {{ old('nom')==$opt?'selected':'' }}>{{ $opt }}</option>
        @endforeach
        <option value="__autre__" {{ old('nom') && !in_array(old('nom'),['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen'])?'selected':'' }}>
            Autre…
        </option>
    </select>
    <input class="inp" name="nom" id="inp-nom" placeholder="Nom personnalisé"
           value="{{ old('nom') }}"
           style="{{ (!old('nom') || in_array(old('nom'),['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen']))?'display:none;':'' }}" />
    @error('nom')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

    <div class="lbl">Année scolaire *</div>
    <select class="select" name="annee_scolaire">
        <option value="2025-2026" {{ old('annee_scolaire','2025-2026')=='2025-2026'?'selected':'' }}>2025-2026</option>
        <option value="2026-2027" {{ old('annee_scolaire')=='2026-2027'?'selected':'' }}>2026-2027</option>
        <option value="2024-2025" {{ old('annee_scolaire')=='2024-2025'?'selected':'' }}>2024-2025</option>
    </select>

    <div class="lbl">Montant total (FCFA) *</div>
    <input class="inp" type="number" name="montant_total" id="montant-total"
           placeholder="ex : 95000" min="0" step="500"
           value="{{ old('montant_total') }}" onchange="recalc()" />
    @error('montant_total')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

    <div class="lbl">Nombre de tranches *</div>
    <div style="display:flex;gap:8px;margin-bottom:12px;" id="tranches-btns">
        @foreach([1,2,3] as $n)
        <label id="tbl-{{ $n }}"
               style="flex:1;padding:10px;border:2px solid {{ old('nb_tranches_max',1)==$n?'var(--ep-teal)':'#ddd' }};
                      border-radius:var(--radius-md);cursor:pointer;text-align:center;
                      background:{{ old('nb_tranches_max',1)==$n?'var(--ep-teal-lt)':'#fff' }};"
               onclick="setN({{ $n }})">
            <input type="radio" name="nb_tranches_max" value="{{ $n }}"
                   {{ old('nb_tranches_max',1)==$n?'checked':'' }} style="display:none;">
            <div style="font-size:14px;font-weight:700;">{{ $n }}</div>
            <div style="font-size:10px;color:#888;">tranche{{ $n>1?'s':'' }}</div>
        </label>
        @endforeach
    </div>

    <div class="lbl" style="margin-bottom:8px;">Options</div>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;cursor:pointer;">
        <input type="checkbox" name="fractionnable" value="1"
               {{ old('fractionnable',1)?'checked':'' }} />
        Paiement fractionné autorisé
    </label>

    <div class="lbl">Description (optionnel)</div>
    <textarea class="inp" name="description" rows="2"
              placeholder="ex : Scolarité par trimestre, paiement en 3 tranches max">{{ old('description') }}</textarea>
</div>

<button type="submit" class="btn-p" style="width:100%;">Enregistrer →</button>
<a href="{{ route('etablissement.frais.index') }}" class="btn-o"
   style="width:100%;display:block;text-align:center;margin-top:8px;">Annuler</a>
</div>

{{-- ── Colonne droite : échéancier ── --}}
<div>
<div class="epcard">
    <div class="seclbl" style="margin-top:0;">Échéancier de paiement</div>
    <div id="ech-container"></div>
</div>
<div style="margin-top:12px;background:var(--ep-gold-lt);border-radius:var(--radius-md);
            padding:12px 14px;font-size:11px;color:#854F0B;line-height:1.7;
            border-left:3px solid var(--ep-gold);">
    <strong>CDC E02 / E03 :</strong> Max 3 tranches · La somme des tranches doit égaler le montant total · Rappel SMS automatique J‑5 avant chaque échéance.
</div>
</div>

</div>{{-- /g2 --}}
</form>

@push('scripts')
<script>
let nbT = {{ old('nb_tranches_max', 1) }};
let total = {{ old('montant_total', 0) }};

function setN(n) {
    nbT = n;
    [1,2,3].forEach(i => {
        const l = document.getElementById('tbl-' + i);
        l.style.border = i===n ? '2px solid var(--ep-teal)' : '2px solid #ddd';
        l.style.background = i===n ? 'var(--ep-teal-lt)' : '#fff';
        l.querySelector('input').checked = (i===n);
    });
    renderEch();
}

function recalc() {
    total = parseFloat(document.getElementById('montant-total').value) || 0;
    renderEch();
}

function renderEch() {
    const c = document.getElementById('ech-container');
    c.innerHTML = '';
    if (!nbT) return;
    const base = Math.floor(total / nbT);
    for (let i = 1; i <= nbT; i++) {
        const montant = (i === nbT) ? (total - base * (nbT - 1)) : base;
        c.insertAdjacentHTML('beforeend', `
        <div style="border:1px solid #eee;border-radius:var(--radius-md);padding:14px;margin-bottom:10px;">
            <div style="font-size:11px;font-weight:700;color:#0F6E56;text-transform:uppercase;
                        letter-spacing:.05em;margin-bottom:10px;">
                Tranche ${i} / ${nbT}
            </div>
            <div class="lbl">Libellé</div>
            <input class="inp" name="echeances[${i-1}][libelle]"
                   placeholder="Tranche ${i} — ${i===1?'Inscription + 50%':i===2?'30%':'Solde 20%'}"
                   value="Tranche ${i} — ${nbT===3?(i===1?'Inscription + 50%':i===2?'30%':'Solde 20%'):(i===1?'50%':'Solde 50%')}" />
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <div class="lbl">Montant (FCFA) *</div>
                    <input class="inp" type="number" name="echeances[${i-1}][montant]"
                           value="${montant}" min="0" step="500" style="margin-bottom:0;" />
                </div>
                <div>
                    <div class="lbl">Date limite *</div>
                    <input class="inp" type="date" name="echeances[${i-1}][date_echeance]"
                           style="margin-bottom:0;" />
                </div>
            </div>
        </div>`);
    }
}

function syncNom(sel) {
    const inp = document.getElementById('inp-nom');
    if (sel.value === '__autre__') {
        inp.style.display = 'block';
        inp.name = 'nom';
        sel.name = '';
    } else {
        inp.style.display = 'none';
        inp.name = '';
        sel.name = 'nom';
    }
}

// Init au chargement
document.getElementById('sel-nom').name = {{ old('nom') && !in_array(old('nom'),['Scolarité','Inscription','Cantine','Internat','Transport','Frais d\'examen']) ? "''" : "'nom'" }};
renderEch();
</script>
@endpush

@endsection
