@extends('layouts.payeur')

@section('title', 'Frais — ' . $apprenant->prenom . ' ' . $apprenant->nom)

@push('modals')
<div id="modal-modifier-apprenant" class="ep-modal-overlay">
  <div class="ep-modal">
    <div class="ep-modal-head">
      <h3>Modifier mon dossier</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-modifier-apprenant')">×</button>
    </div>
    <form method="POST" action="{{ route('payeur.apprenant.update', $apprenant) }}">
      @csrf @method('PUT')
      <div class="ep-modal-body">
        <div class="g2">
          <div>
            <div class="lbl">Prénom</div>
            <input class="inp" name="prenom" value="{{ $apprenant->prenom }}" required />
          </div>
          <div>
            <div class="lbl">Nom</div>
            <input class="inp" name="nom" value="{{ $apprenant->nom }}" required />
          </div>
        </div>
        <div class="lbl">Classe / Niveau</div>
        <input class="inp" name="classe" value="{{ $apprenant->classe }}" required />
        <div class="lbl">Matricule</div>
        <input class="inp" name="matricule" value="{{ $apprenant->matricule }}" placeholder="EP-XXXX" />
      </div>
      <div class="ep-modal-foot">
        <button type="button" class="btn-o" style="width:auto;padding:8px 16px;"
                onclick="epModal.close('modal-modifier-apprenant')">Annuler</button>
        <button type="submit" class="btn-p" style="width:auto;padding:8px 20px;">
          Enregistrer →
        </button>
      </div>
    </form>
  </div>
</div>
@endpush

@section('content')

    <div style="display:flex;align-items:center;gap:10px;margin-bottom:20px;">
        <a href="{{ route('payeur.dashboard') }}" style="color:#888;text-decoration:none;font-size:13px;">← Retour</a>
    </div>

    {{-- En-tête apprenant --}}
    <div class="epcard" style="background:var(--ep-teal-lt);border-color:rgba(13,158,117,.2);margin-bottom:18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-size:15px;font-weight:700;color:#085041;">
                    {{ $apprenant->prenom }} {{ $apprenant->nom }}
                </div>
                <div style="font-size:12px;color:#1B9E75;">
                    {{ $apprenant->etablissement->nom ?? '—' }} · {{ $apprenant->classe }}
                    @if($apprenant->matricule) · Mat. {{ $apprenant->matricule }} @endif
                </div>
            </div>
            <button type="button" onclick="epModal.open('modal-modifier-apprenant')"
                    class="btn-o" style="width:auto;font-size:12px;padding:8px 14px;">
                ✎ Modifier
            </button>
        </div>
    </div>

    {{-- Liste des frais --}}
    <div class="seclbl">Frais scolaires — détail par catégorie</div>

    @forelse ($apprenant->frais as $frais)
        @php
            $reste      = $frais->montant_total - $frais->montant_paye;
            $pourcentage = $frais->montant_total > 0
                ? round(($frais->montant_paye / $frais->montant_total) * 100)
                : 0;
            $dernierPaiement = $frais->paiements->first();
        @endphp

        <div class="epcard" style="margin-bottom:14px;border-left:3px solid {{ match($frais->statut) {
            'regle'   => 'var(--ep-teal)',
            'partiel' => 'var(--ep-gold)',
            'impaye'  => 'var(--ep-red)',
            default   => '#ddd',
        } }};">

            {{-- En-tête frais --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div style="font-size:14px;font-weight:700;">
                        {{ $frais->categorieFrais->nom ?? 'Frais scolaires' }}
                    </div>
                    <div style="font-size:11px;color:#888;">{{ $frais->annee_scolaire }}</div>
                </div>
                <span class="pill {{ match($frais->statut) {
                    'regle' => 'pg', 'partiel' => 'pa', 'impaye' => 'pr', default => 'pa',
                } }}">
                    {{ match($frais->statut) {
                        'regle' => 'Réglé', 'partiel' => 'Partiel', 'impaye' => 'Impayé', default => $frais->statut,
                    } }}
                </span>
            </div>

            {{-- Montants --}}
            <div style="display:flex;gap:20px;margin-bottom:10px;">
                <div>
                    <div style="font-size:10px;color:#aaa;margin-bottom:2px;">Total</div>
                    <div style="font-size:16px;font-weight:700;">
                        {{ number_format($frais->montant_total, 0, ',', ' ') }} FCFA
                    </div>
                </div>
                <div>
                    <div style="font-size:10px;color:#aaa;margin-bottom:2px;">Payé</div>
                    <div style="font-size:16px;font-weight:700;color:var(--ep-teal);">
                        {{ number_format($frais->montant_paye, 0, ',', ' ') }} FCFA
                    </div>
                </div>
                @if($reste > 0)
                    <div>
                        <div style="font-size:10px;color:#aaa;margin-bottom:2px;">Reste</div>
                        <div style="font-size:16px;font-weight:700;color:var(--ep-red);">
                            {{ number_format($reste, 0, ',', ' ') }} FCFA
                        </div>
                    </div>
                @endif
            </div>

            {{-- Barre de progression --}}
            <div class="prog" style="margin-bottom:4px;">
                <div class="pfill" style="width:{{ $pourcentage }}%;background:{{ $frais->statut === 'impaye' ? 'var(--ep-red)' : 'var(--ep-teal)' }};"></div>
            </div>
            <div style="font-size:10px;color:#888;margin-bottom:12px;">{{ $pourcentage }}% réglé</div>

            {{-- Échéancier --}}
            @if($frais->categorieFrais->echeanciers->count())
                <div class="seclbl" style="margin-bottom:6px;">Échéancier</div>
                @foreach($frais->categorieFrais->echeanciers as $ech)
                    <div style="display:flex;justify-content:space-between;align-items:center;
                                padding:7px 0;border-bottom:1px solid #f5f5f5;font-size:12px;">
                        <div>
                            <span style="font-weight:600;">T{{ $ech->numero_tranche }}</span>
                            @if($ech->libelle) — {{ $ech->libelle }} @endif
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:600;">{{ number_format($ech->montant, 0, ',', ' ') }} FCFA</div>
                            <div style="font-size:10px;color:#aaa;">
                                Échéance : {{ $ech->date_echeance->format('d/m/Y') }}
                            </div>
                        </div>
                    </div>
                @endforeach
                <div style="margin-bottom:10px;"></div>
            @endif

            {{-- Dernier paiement --}}
            @if($dernierPaiement)
                <div style="font-size:11px;color:#888;margin-bottom:10px;">
                    Dernier paiement validé :
                    {{ number_format($dernierPaiement->montant, 0, ',', ' ') }} FCFA
                    le {{ \Carbon\Carbon::parse($dernierPaiement->date_validation)->format('d/m/Y') }}
                    via {{ match($dernierPaiement->mode_paiement) {
                        'mtn_momo' => 'MTN MoMo',
                        'orange_money' => 'Orange Money',
                        default => $dernierPaiement->mode_paiement,
                    } }}
                </div>
            @endif

            {{-- Bouton payer --}}
            @if($reste > 0)
                <a href="{{ route('payeur.paiement.show', $frais) }}"
                   class="btn-p" style="display:block;text-align:center;padding:10px;">
                    Payer {{ number_format($reste, 0, ',', ' ') }} FCFA →
                </a>
            @else
                <div style="text-align:center;font-size:12px;color:var(--ep-teal);font-weight:600;padding:8px 0;">
                    ✓ Entièrement réglé
                </div>
            @endif

        </div>
    @empty
        <div class="epcard" style="text-align:center;color:#999;padding:30px 0;">
            Aucun frais enregistré pour cet apprenant.
        </div>
    @endforelse

@endsection
