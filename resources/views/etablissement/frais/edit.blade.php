@extends('layouts.etablissement')

@section('title', 'Modifier — ' . $frais->nom)

@section('content')

<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;font-size:13px;">
    <a href="{{ route('etablissement.frais.index') }}" style="color:#888;text-decoration:none;">
        ← Frais &amp; Échéanciers
    </a>
    <span style="color:#ddd;">/</span>
    <span style="font-weight:600;">Modifier — {{ $frais->nom }}</span>
</div>

@if($errors->any())
<div style="background:var(--ep-red-lt);border-left:3px solid var(--ep-red);border-radius:var(--radius-md);padding:12px 16px;margin-bottom:16px;font-size:12px;color:#9B2C2C;">
    <ul style="margin:0 0 0 16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('etablissement.frais.update', $frais) }}">
@csrf @method('PUT')

<div class="g2" style="align-items:start;gap:16px;">
<div>
<div class="epcard" style="margin-bottom:14px;">
    <div class="seclbl" style="margin-top:0;">Catégorie</div>

    <div class="lbl">Nom *</div>
    <input class="inp" name="nom" value="{{ old('nom', $frais->nom) }}" required />

    <div class="lbl">Année scolaire *</div>
    <select class="select" name="annee_scolaire">
        @foreach(['2024-2025','2025-2026','2026-2027'] as $y)
        <option value="{{ $y }}" {{ old('annee_scolaire',$frais->annee_scolaire)==$y?'selected':'' }}>{{ $y }}</option>
        @endforeach
    </select>

    <div class="lbl">Montant total (FCFA) *</div>
    <input class="inp" type="number" name="montant_total"
           value="{{ old('montant_total', $frais->montant_total) }}" min="0" step="500" required />

    <div class="lbl">Nombre de tranches max *</div>
    <div style="display:flex;gap:8px;margin-bottom:12px;">
        @foreach([1,2,3] as $n)
        <label style="flex:1;padding:10px;border:2px solid {{ old('nb_tranches_max',$frais->nb_tranches_max)==$n?'var(--ep-teal)':'#ddd' }};
                      border-radius:var(--radius-md);cursor:pointer;text-align:center;
                      background:{{ old('nb_tranches_max',$frais->nb_tranches_max)==$n?'var(--ep-teal-lt)':'#fff' }};">
            <input type="radio" name="nb_tranches_max" value="{{ $n }}"
                   {{ old('nb_tranches_max',$frais->nb_tranches_max)==$n?'checked':'' }} style="display:none;">
            <div style="font-size:14px;font-weight:700;">{{ $n }}</div>
            <div style="font-size:10px;color:#888;">tranche{{ $n>1?'s':'' }}</div>
        </label>
        @endforeach
    </div>

    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;cursor:pointer;">
        <input type="checkbox" name="fractionnable" value="1"
               {{ old('fractionnable',$frais->fractionnable)?'checked':'' }} />
        Paiement fractionné autorisé
    </label>

    <label style="display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:12px;cursor:pointer;">
        <input type="checkbox" name="actif" value="1"
               {{ old('actif',$frais->actif)?'checked':'' }} />
        Catégorie active
    </label>

    <div class="lbl">Description</div>
    <textarea class="inp" name="description" rows="2">{{ old('description',$frais->description) }}</textarea>
</div>

<button type="submit" class="btn-p" style="width:100%;">Enregistrer les modifications →</button>
<a href="{{ route('etablissement.frais.index') }}" class="btn-o"
   style="width:100%;display:block;text-align:center;margin-top:8px;">Annuler</a>
</div>

{{-- Échéances en lecture --}}
<div>
<div class="epcard">
    <div class="seclbl" style="margin-top:0;">Échéances configurées</div>
    @forelse($echeances as $ech)
    <div class="row">
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="pill pg" style="font-size:10px;">T{{ $ech->numero_tranche }}</span>
            <div>
                <div style="font-size:13px;font-weight:500;">{{ $ech->libelle ?? 'Tranche '.$ech->numero_tranche }}</div>
                <div style="font-size:11px;color:#888;">{{ number_format($ech->montant,0,',',' ') }} FCFA</div>
            </div>
        </div>
        <div style="font-size:12px;color:#555;">
            {{ \Carbon\Carbon::parse($ech->date_echeance)->translatedFormat('d M Y') }}
        </div>
    </div>
    @empty
    <div style="color:#aaa;font-size:12px;text-align:center;padding:12px;">
        Aucune échéance. Supprimez et recréez la catégorie pour en ajouter.
    </div>
    @endforelse
</div>
</div>
</div>
</form>

@endsection
