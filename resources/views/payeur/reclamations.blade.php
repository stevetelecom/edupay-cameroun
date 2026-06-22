@extends('layouts.payeur')

@section('title', 'Réclamations')

@section('content')

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div style="font-size:17px;font-weight:700;">Mes réclamations</div>
    <button class="btn-p" style="width:auto;padding:9px 16px;font-size:12px;" onclick="document.getElementById('claim-box').style.display='block';this.style.display='none';">
        + Nouvelle réclamation
    </button>
</div>

<div id="claim-box" class="epcard" style="display:none;margin-bottom:16px;border-left:3px solid var(--ep-gold);">
    <div class="seclbl" style="margin-top:0;">Décrire le problème</div>

    @if($errors->any())
        <div style="background:#FEE2E2;border:1px solid #FCA5A5;border-radius:8px;padding:10px 14px;margin-bottom:14px;">
            @foreach($errors->all() as $error)
                <div style="font-size:12px;color:#B91C1C;">{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payeur.reclamations.store') }}">
        @csrf
        <div class="lbl">Transaction concernée</div>
        <select class="inp" name="paiement_id">
            <option value="">Autre / paiement introuvable</option>
            @foreach($paiements as $p)
                <option value="{{ $p->id }}" {{ old('paiement_id') == $p->id ? 'selected' : '' }}>
                    Réf. #{{ $p->reference }} — {{ $p->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                    ({{ number_format($p->montant, 0, ',', ' ') }} FCFA)
                </option>
            @endforeach
        </select>

        <div class="lbl">Objet</div>
        <input class="inp" name="sujet" maxlength="150" required
               placeholder="Ex : Paiement débité deux fois"
               value="{{ old('sujet') }}" />

        <div class="lbl">Description</div>
        <textarea class="inp" name="description" rows="4" required
                  style="resize:vertical;"
                  placeholder="Expliquez le problème rencontré (montant débité deux fois, paiement non reconnu par l'école, etc.)">{{ old('description') }}</textarea>

        <div style="display:flex;gap:8px;margin-top:6px;">
            <button type="submit" class="btn-p" style="width:auto;padding:9px 18px;font-size:12px;">
                Envoyer la réclamation
            </button>
            <button type="button" class="btn-o" style="width:auto;padding:9px 18px;font-size:12px;"
                    onclick="document.getElementById('claim-box').style.display='none';">
                Annuler
            </button>
        </div>
    </form>
</div>

<div class="epcard">
    @forelse($reclamations as $reclamation)
        <div class="row">
            <div>
                <div style="font-size:13px;font-weight:600;">
                    #{{ $reclamation->numero_ticket }} — {{ $reclamation->sujet }}
                </div>
                <div style="font-size:11px;color:#888;">
                    Ouvert le {{ $reclamation->created_at->format('d M Y') }}
                    @if($reclamation->paiement)
                        · {{ $reclamation->paiement->fraisApprenant->categorieFrais->nom ?? 'Paiement' }}
                    @endif
                </div>
            </div>
            <span class="pill {{ match($reclamation->statut) {
                'resolu' => 'pg', 'en_cours' => 'pa', 'rejete' => 'pr', 'ouvert' => 'pb', default => 'pa',
            } }}">
                {{ match($reclamation->statut) {
                    'resolu' => 'Résolu', 'en_cours' => 'En cours', 'rejete' => 'Rejetée', 'ouvert' => 'Ouvert', default => $reclamation->statut,
                } }}
            </span>
        </div>
    @empty
        <div style="text-align:center;color:#999;font-size:13px;padding:20px 0;">
            Vous n'avez envoyé aucune réclamation pour le moment.
        </div>
    @endforelse
</div>

@endsection
