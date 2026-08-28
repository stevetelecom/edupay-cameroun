@extends('layouts.public')

@section('title', __('etablissement.abonnement_requis') . ' — EduPay Cameroun')

@section('content')
<div class="video-bg-container" style="min-height:100vh;display:flex;flex-direction:column;"><video class="video-bg" autoplay muted loop playsinline><source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4"></video><div class="video-bg-overlay"></div>

  <div class="form-header">
    <div style="display:flex;align-items:center;gap:9px;"><span style="width:52px;height:52px;border-radius:14px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 3px 12px rgba(0,0,0,.2);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span></div>
    <div style="display:flex;align-items:center;gap:10px;">
      <a href="{{ route('landing') }}" style="background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);padding:6px 13px;border-radius:20px;font-size:12px;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
        <span class="material-symbols-outlined" style="font-size:14px;">arrow_back</span> {{ __('etablissement.accueil') }}
      </a>
      <form method="POST" action="{{ route('locale.switch') }}" style="display:inline-flex;align-items:center;">
        @csrf
        <select name="locale" onchange="this.form.submit()" style="background:rgba(255,255,255,.08);color:#fff;border:1px solid rgba(255,255,255,.25);border-radius:20px;padding:6px 10px;font-size:12px;font-weight:500;cursor:pointer;outline:none;">
          <option value="fr" {{ app()->getLocale()==='fr' ? 'selected' : '' }}>🇫🇷 FR</option>
          <option value="en" {{ app()->getLocale()==='en' ? 'selected' : '' }}>🇬🇧 EN</option>
        </select>
      </form>
    </div>
  </div>

  <div class="form-body" style="padding:32px 16px;">
    <div style="width:100%;max-width:760px;">
      <div class="form-card" style="max-width:760px;">

        <div style="text-align:center;margin-bottom:26px;">
          <div style="width:52px;height:52px;background:var(--ep-gold-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <span class="material-symbols-outlined" style="font-size:26px;color:#E8A020;">credit_card</span>
          </div>
          <div class="form-title">{{ __('etablissement.abonnement_requis') }}</div>

          @if(!$abonnement)
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;">
              {!! __('etablissement.abon_pas_actif', ['nom' => $etablissement->nom]) !!}
            </div>
          @elseif($abonnement->statut === 'grace_period')
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;color:#B45309;">
              {!! __('etablissement.abon_grace', ['plan' => ucfirst($abonnement->plan), 'date1' => $abonnement->date_fin->format('d/m/Y'), 'date2' => $abonnement->grace_period_fin->format('d/m/Y')]) !!}
            </div>
          @else
            <div class="form-sub" style="max-width:480px;margin:6px auto 0;color:#B91C1C;">
              {!! __('etablissement.abon_expire', ['plan' => ucfirst($abonnement->plan), 'date' => $abonnement->grace_period_fin->format('d/m/Y')]) !!}
            </div>
          @endif
        </div>

        {{-- ── Formules disponibles ── --}}
        <div style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">{{ __('etablissement.nos_formules') }}</div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:26px;">
          @foreach($plans as $key => $plan)
            <div style="border:1px solid #eee;border-top:3px solid {{ $plan['couleur'] }};border-radius:var(--radius-md);padding:16px;">
              <div style="font-size:14px;font-weight:700;color:#111;margin-bottom:2px;">{{ $plan['nom'] }}</div>
              <div style="font-size:20px;font-weight:700;color:{{ $plan['couleur'] }};margin-bottom:10px;">
                {{ number_format($plan['montant'], 0, ',', ' ') }} <span style="font-size:11px;font-weight:500;color:#999;">{{ __('etablissement.fcfa_par_mois') }}</span>
              </div>
              <div style="font-size:12px;color:#666;line-height:2;">
                <div style="display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:15px;color:#9CA3AF;">group</span>
                  {{ $plan['max_apprenants'] === -1 ? __('etablissement.apprenants_illimites') : __('etablissement.apprenants_max', ['count' => $plan['max_apprenants']]) }}
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:15px;color:#9CA3AF;">sms</span>
                  {{ $plan['sms_mensuel'] === -1 ? __('etablissement.sms_illimites') : __('etablissement.sms_mois', ['count' => $plan['sms_mensuel']]) }}
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:15px;color:#9CA3AF;">apartment</span>
                  {{ __('etablissement.multi_sites_fmt', ['statut' => $plan['multi_sites'] ? __('etablissement.inclus') : __('etablissement.non_inclus')]) }}
                </div>
                <div style="display:flex;align-items:center;gap:6px;">
                  <span class="material-symbols-outlined" style="font-size:15px;color:#9CA3AF;">bar_chart</span>
                  {{ __('etablissement.exports_cobac_fmt', ['statut' => $plan['exports_cobac'] ? __('etablissement.inclus') : __('etablissement.non_inclus')]) }}
                </div>
              </div>
            </div>
          @endforeach
        </div>

        {{-- ── Comment ça marche ── --}}
        <div style="font-size:11px;font-weight:600;color:#999;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">{{ __('etablissement.comment_activer') }}</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:28px;">
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">1</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('etablissement.etape1') }}</div>
          </div>
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">2</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('etablissement.etape2') }}</div>
          </div>
          <div style="display:flex;gap:12px;align-items:flex-start;">
            <div style="width:22px;height:22px;border-radius:50%;background:var(--ep-teal);color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">3</div>
            <div style="font-size:13px;color:#555;line-height:1.6;">{{ __('etablissement.etape3') }}</div>
          </div>
        </div>

        {{-- ── Actions principales ── --}}
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a href="{{ route('contact') }}" class="btn-p" style="flex:1;min-width:200px;text-align:center;padding:12px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:16px;">support_agent</span>
            {{ __('etablissement.contacter_support') }}
          </a>
          <a href="{{ route('etablissement.dashboard') }}" class="btn-o" style="flex:1;min-width:200px;text-align:center;padding:12px;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;gap:6px;">
            <span class="material-symbols-outlined" style="font-size:16px;">dashboard</span>
            {{ __('etablissement.acceder_tdb') }}
          </a>
        </div>

      </div>

      {{-- ── Retour connexion / accueil ── --}}
      <div style="display:flex;justify-content:center;gap:12px;margin-top:18px;flex-wrap:wrap;">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <input type="hidden" name="redirect" value="login" />
          <button type="submit" style="background:#fff;border:1px solid #ddd;box-shadow:0 2px 8px rgba(0,0,0,.08);cursor:pointer;font-size:12px;color:#444;font-weight:600;display:inline-flex;align-items:center;gap:5px;padding:9px 16px;border-radius:20px;">
            <span class="material-symbols-outlined" style="font-size:14px;">login</span>
            {{ __('etablissement.retour_connexion') }}
          </button>
        </form>
        <a href="{{ route('landing') }}" style="background:#fff;border:1px solid #ddd;box-shadow:0 2px 8px rgba(0,0,0,.08);font-size:12px;color:#444;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;padding:9px 16px;border-radius:20px;">
          <span class="material-symbols-outlined" style="font-size:14px;">home</span>
          {{ __('etablissement.accueil') }}
        </a>
      </div>

    </div>
  </div>

</div>
@endsection
