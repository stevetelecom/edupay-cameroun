@extends('layouts.etablissement')

@section('title', 'Frais & Échéanciers')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
    <div>
        <div style="font-size:17px;font-weight:700;">
            Catégories de frais &amp; échéanciers — {{ date('Y') - 1 }}-{{ date('Y') }}
        </div>
    </div>
</div>

{{-- ── SECTION 1 : CATÉGORIES DE FRAIS ── --}}
<div class="seclbl" style="margin-top:0;">Catégories de frais</div>
<div class="epcard" style="margin-bottom:20px;">

    @if($categories->isEmpty())
        <div style="text-align:center;padding:28px;color:#aaa;font-size:13px;">
            Aucune catégorie définie pour cet établissement.
        </div>
    @else
        @foreach($categories as $cat)
        <div class="row">
            <div style="display:flex;align-items:center;gap:12px;">
                <div>
                    <div style="font-size:13px;font-weight:600;">{{ $cat->nom }}</div>
                    @if($cat->description)
                        <div style="font-size:11px;color:#888;">{{ $cat->description }}</div>
                    @endif
                </div>
                @if(!$cat->actif)
                    <span class="pill pr" style="font-size:10px;">Inactif</span>
                @endif
            </div>
            <div style="display:flex;align-items:center;gap:14px;">
                <strong style="font-size:13px;">
                    {{ number_format($cat->montant_total, 0, ',', ' ') }} FCFA
                </strong>
                <a href="{{ route('etablissement.frais.edit', $cat) }}"
                   style="font-size:11px;color:var(--ep-teal);text-decoration:none;border:1px solid var(--ep-teal);padding:3px 10px;border-radius:20px;">
                    Modifier
                </a>
                <form method="POST" action="{{ route('etablissement.frais.destroy', $cat) }}"
                      onsubmit="return confirm('Supprimer « {{ addslashes($cat->nom) }} » et ses échéances ?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            style="font-size:11px;color:var(--ep-red);background:none;border:1px solid var(--ep-red);padding:3px 10px;border-radius:20px;cursor:pointer;">
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    @endif

    <div style="margin-top:14px;">
        <a href="{{ route('etablissement.frais.create') }}"
           class="btn-o" style="width:auto;padding:7px 16px;font-size:12px;">
            + Ajouter une catégorie
        </a>
    </div>
</div>

{{-- ── SECTION 2 : ÉCHÉANCIER — PAIEMENT FRACTIONNÉ ── --}}
@php
    $toutesEcheances = $categories->flatMap(fn($c) => $c->echeanciers)->sortBy('numero_tranche');
@endphp

@if($toutesEcheances->isNotEmpty())
<div class="seclbl">Échéancier — Paiement fractionné</div>
<div class="epcard">
    @foreach($toutesEcheances as $ech)
    <div class="row">
        <div style="display:flex;align-items:center;gap:12px;">
            <span class="pill pg" style="font-size:10px;flex-shrink:0;">T{{ $ech->numero_tranche }}</span>
            <div>
                <div style="font-size:13px;font-weight:500;">
                    {{ $ech->libelle ?? 'Tranche ' . $ech->numero_tranche }}
                </div>
                <div style="font-size:11px;color:#888;">
                    {{ $ech->categorieFrais->nom ?? '' }}
                    · {{ number_format($ech->montant, 0, ',', ' ') }} FCFA
                </div>
            </div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:13px;font-weight:600;">
                Avant le {{ \Carbon\Carbon::parse($ech->date_echeance)->translatedFormat('d M Y') }}
            </div>
            @php
                $j = now()->diffInDays(\Carbon\Carbon::parse($ech->date_echeance), false);
            @endphp
            @if($j < 0)
                <span class="pill pr" style="font-size:10px;">Dépassée</span>
            @elseif($j <= 5)
                <span class="pill pa" style="font-size:10px;">Dans {{ $j }}j</span>
            @else
                <span class="pill pg" style="font-size:10px;">À venir</span>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
