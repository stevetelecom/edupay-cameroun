@extends('layouts.public')

@section('title', 'Abonnement requis — EduPay Cameroun')

@section('content')
<div style="min-height:100vh;background:#f1f3f5;display:flex;flex-direction:column;">

  <div class="form-header">
    <div class="logo-t" style="font-size:17px;">Edu<span>Pay</span> Cameroun</div>
    <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:6px 13px;border-radius:20px;font-size:12px;text-decoration:none;">← Accueil</a>
  </div>

  <div class="form-body" style="padding:32px 16px;">
    <div style="width:100%;max-width:760px;">
      <div class="form-card" style="max-width:760px;">

        <div style="text-align:center;margin-bottom:26px;">
          <div style="width:52px;height:52px;background:var(--ep-gold-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          </div>
          <div class="form-title">Abonnement requis</div>

          @if(!$abonnement)
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;">
              L'établissement <strong>{{ $etablissement->nom }}</strong> n'a pas encore d'abonnement actif.
              Choisissez une formule ci-dessous et contactez notre équipe pour activer votre accès.
            </div>
          @elseif($abonnement->statut === 'grace_period')
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;color:#B45309;">
              Votre abonnement <strong>{{ ucfirst($abonnement->plan) }}</strong> a expiré le {{ $abonnement->date_fin->format('d/m/Y') }}.
              Vous êtes en période de grâce jusqu'au {{ $abonnement->grace_period_fin->format('d/m/Y') }} — renouvelez dès maintenant pour éviter toute interruption.
            </div>
          @else
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;color:#B91C1C;">
              Votre abonnement <strong>{{ ucfirst($abonnement->plan) }}</strong> a expiré le {{ $abonnement->grace_period_fin->format('d/m/Y') }}.
              Contactez-nous pour le renouveler et retrouver l'accès complet à votre back-office.
            </div>
          @endif
        </div>

        {{-- ── Formules disponibles ── --}}
        <div style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Nos formules</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:26px;">
          @foreach($plans as $key => $plan)
            <div style="border:1px solid #eee;border-top:3px solid {{ $plan['couleur'] }};border-radius:var(--radius-md);padding:16px;">
              <div style="font-size:14px;font-weight:700;color:#111;margin-bottom:2px;">{{ $plan['nom'] }}</div>
              <div style="font-size:20px;font-weight:700;color:{{ $plan['couleur'] }};margin-bottom:10px;">
                {{ number_format($plan['montant'], 0, ',', ' ') }} <span style="font-size:11px;font-weight:500;color:#999;">FCFA / mois</span>
              </div>
              <div style="font-size:12px;color:#666;line-height:1.9;">
                <div>👥 {{ $plan['max_apprenants'] === -1 ? 'Apprenants illimités' : $plan['max_apprenants'].' apprenants max' }}</div>
                <div>💬 {{ $plan['sms_mensuel'] === -1 ? 'SMS illimités' : $plan['sms_mensuel'].' SMS / mois' }}</div>
                <div>🏫 Multi-sites {{ $plan['multi_sites'] ? 'inclus' : 'non inclus' }}</div>
                <div>📊 Exports COBAC {{ $plan['exports_cobac'] ? 'inclus' : 'non inclus' }}</div>
              </div>
            </div>
          @endforeach
        </div>

        {{-- ── Comment ça marche ── --}}
        <div style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">Comment activer votre abonnement</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">Contactez notre équipe (message ou appel) pour indiquer la formule souhaitée.</div>
          </div>
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">Effectuez le paiement mensuel via Mobile Money selon les instructions fournies.</div>
          </div>
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">Notre équipe active votre abonnement sous 24h après réception du paiement.</div>
          </div>
        </div>

        {{-- ── Actions principales ── --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="{{ route('contact') }}" class="btn-p" style="flex:1;min-width:200px;text-align:center;padding:12px;text-decoration:none;">
            Contacter le support →
          </a>
          <a href="{{ route('etablissement.dashboard') }}" class="btn-o" style="flex:1;min-width:200px;text-align:center;padding:12px;text-decoration:none;">
            Accéder à mon tableau de bord
          </a>
        </div>

      </div>

      {{-- ── Retour connexion / accueil ── --}}
      <div style="display:flex;justify-content:center;gap:20px;margin-top:18px;">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" style="background:none;border:none;cursor:pointer;font-size:12px;color:#888;font-weight:500;">
            ← Retour à la connexion
          </button>
        </form>
        <a href="{{ route('landing') }}" style="font-size:12px;color:#888;font-weight:500;text-decoration:none;">Accueil</a>
      </div>

    </div>
  </div>

</div>
@endsection
