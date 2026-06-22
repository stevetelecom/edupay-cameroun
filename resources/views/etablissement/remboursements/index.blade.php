@extends('layouts.etablissement')

@section('title', 'Remboursements')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div style="font-size:17px;font-weight:700;">Demandes de remboursement</div>
    <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;" onclick="document.getElementById('refund-box').style.display='block';this.style.display='none';">
        + Nouvelle demande
    </button>
</div>

<div id="refund-box" class="epcard" style="display:none;margin-bottom:16px;border-left:3px solid var(--ep-gold);">
    <div class="seclbl" style="margin-top:0;">Créer une demande de remboursement</div>

    @if($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('etablissement.remboursements.store') }}">
        @csrf
        <div class="lbl">Paiement concerné</div>
        <select class="select" name="paiement_id" id="select-paiement" required onchange="document.getElementById('montant-max').textContent = this.options[this.selectedIndex].dataset.montant || '';">
            <option value="">-- Choisir un paiement --</option>
            @foreach($paiementsRemboursables as $p)
                <option value="{{ $p->id }}" data-montant="{{ number_format($p->montant, 0, ',', ' ') }}">
                    {{ $p->apprenant->prenom }} {{ $p->apprenant->nom }} — {{ $p->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                    ({{ number_format($p->montant, 0, ',', ' ') }} FCFA, réf. {{ $p->reference }})
                </option>
            @endforeach
        </select>

        <div class="lbl">Montant à rembourser (FCFA) <span style="color:#888;font-weight:400;">— max : <span id="montant-max"></span></span></div>
        <input class="inp" type="number" name="montant" min="1" required />

        <div class="lbl">Motif</div>
        <input class="inp" name="motif" maxlength="255" required placeholder="Ex : Erreur de saisie, double paiement, frais annulé..." />

        <div style="display:flex;gap:8px;margin-top:6px;">
            <button type="submit" class="btn-p" style="width:auto;padding:9px 18px;font-size:12px;">
                Créer la demande
            </button>
            <button type="button" class="btn-o" style="width:auto;padding:9px 18px;font-size:12px;"
                    onclick="document.getElementById('refund-box').style.display='none';">
                Annuler
            </button>
        </div>
    </form>
</div>

<div class="epcard">
    @forelse($remboursements as $r)
        <div class="row">
            <div>
                <div style="font-size:13px;font-weight:600;">
                    {{ $r->paiement->apprenant->prenom }} {{ $r->paiement->apprenant->nom }}
                    — {{ $r->paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                </div>
                <div style="font-size:11px;color:#888;">
                    {{ number_format($r->montant, 0, ',', ' ') }} FCFA · Motif : {{ $r->motif }}
                    · {{ $r->created_at->format('d M Y') }}
                    @if($r->statut !== 'en_attente' && $r->traiteur)
                        · Traité par {{ $r->traiteur->prenom }} {{ $r->traiteur->nom }}
                    @endif
                </div>
                @if($r->statut === 'refuse' && $r->motif_refus)
                    <div style="font-size:11px;color:#9B2C2C;margin-top:2px;">Motif du refus : {{ $r->motif_refus }}</div>
                @endif
            </div>

            @if($r->statut === 'en_attente')
                <div style="display:flex;gap:6px;flex-shrink:0;">
                    <form method="POST" action="{{ route('etablissement.remboursements.approuver', $r) }}">
                        @csrf
                        <button type="submit" style="background:var(--ep-teal);color:#fff;border:none;padding:6px 12px;border-radius:6px;font-size:11px;cursor:pointer;">
                            Approuver
                        </button>
                    </form>
                    <button type="button" class="btn-o" style="width:auto;padding:6px 10px;font-size:11px;"
                            onclick="document.getElementById('refus-box-{{ $r->id }}').style.display='block';">
                        Refuser
                    </button>
                </div>
            @else
                <span class="pill {{ $r->statut === 'approuve' ? 'pg' : 'pr' }}">
                    {{ $r->statut === 'approuve' ? 'Remboursé' : 'Refusé' }}
                </span>
            @endif
        </div>

        @if($r->statut === 'en_attente')
            <div id="refus-box-{{ $r->id }}" style="display:none;padding:10px 0;border-bottom:1px solid #f0f0f0;">
                <form method="POST" action="{{ route('etablissement.remboursements.refuser', $r) }}">
                    @csrf
                    <input class="inp" name="motif_refus" placeholder="Motif du refus (optionnel)" style="margin-bottom:8px;" />
                    <button type="submit" class="btn-r" style="width:auto;padding:7px 14px;font-size:11px;">Confirmer le refus</button>
                </form>
            </div>
        @endif
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            Aucune demande de remboursement pour le moment.
        </div>
    @endforelse
</div>

@endsection
