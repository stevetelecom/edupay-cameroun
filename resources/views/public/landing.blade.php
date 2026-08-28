@extends('layouts.public')

@section('title', __('public.landing_title'))

@section('content')

{{-- ══ HERO BAND ══ --}}
@include('layouts._navbar_public')
<div class="hero-band video-bg-container">
  <video class="video-bg" autoplay muted loop playsinline>
    <source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4">
  </video>
  <div class="video-bg-overlay"></div>
  <div class="hero-main">
    <div class="hero-tag">
      <span style="width:7px;height:7px;border-radius:50%;background:#5DCAA5;display:inline-block;"></span>
      {{ __('public.hero_tag') }}
    </div>
    <div class="hero-h1">{{ __('public.hero_h1_line1') }} {{ __('public.hero_h1_connector') }} <em>{{ __('public.hero_h1_line2_em') }}</em><br>{{ __('public.hero_h1_line3') }}</div>
    <div class="hero-sub">{{ __('public.hero_sub') }}</div>
    <div class="hero-btns">
      <a href="{{ route('register.parent.step1') }}" class="hbtn-main">{{ __('public.cta_creer_compte_payeur') }}</a>
      <a href="{{ route('register.ecole.step1') }}" class="hbtn-ghost">{{ __('public.cta_inscrire_etablissement') }}</a>
    </div>
  </div>
  <div class="hero-stats" data-stats-container>
    <div class="hstat">
      <div class="hstat-v stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
      <div class="hstat-l">{{ __('public.stat_etablissements_partenaires') }}</div>
    </div>
    <div class="hstat">
      <div class="hstat-v stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
      <div class="hstat-l">{{ __('public.stat_apprenants_inscrits') }}</div>
    </div>
    <div class="hstat">
      <div class="hstat-v stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
      <div class="hstat-l">{{ __('public.stat_paiements_valides') }}</div>
    </div>
    <div class="hstat">
      <div class="hstat-v stat-counter" data-count="99.5" data-decimals="1" data-suffix="%">0%</div>
      <div class="hstat-l">{{ __('public.stat_uptime') }}</div>
    </div>
  </div>
</div>

<style>
.hstat {
  transition: transform .25s ease, box-shadow .25s ease;
  border-radius: 12px;
  padding: 8px;
  cursor: default;
}
.hstat:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 28px rgba(93,202,165,.18);
}
.etab-card-pub {
  transition: box-shadow .25s ease, transform .25s ease !important;
}
.testi-card {
  transition: box-shadow .25s ease, transform .25s ease;
}
.testi-card:hover {
  box-shadow: 0 10px 28px rgba(13,158,117,.12);
  transform: translateY(-4px);
}
</style>

{{-- ══ BODY ══ --}}
<div class="ep-body2">


  {{-- ══ SECTION : Établissements partenaires ══ --}}
  <div style="margin-bottom:32px;">
    <div class="seclbl reveal-on-scroll">{{ __('public.nos_etablissements_partenaires') }}</div>
    <div style="font-size:13px;color:#888;margin-bottom:20px;text-align:center;">
      {{ __('public.etablissements_nous_f_confiance', ['count' => $stats['nb_etablissements']]) }}
    </div>

    {{-- Filtre rapide --}}
    <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;">
      <div style="position:relative;flex:1;min-width:250px;">
        <span class="material-symbols-outlined" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:18px;color:#999;pointer-events:none;">search</span>
        <input type="text" id="etab-filter"
               placeholder="{{ __('public.rechercher_placeholder') }}"
               onkeyup="filtrerEtabsPublic()"
               onkeypress="if(event.key==='Enter'){filtrerEtabsPublic();}"
               style="width:100%;padding:11px 14px 11px 40px;border:1px solid #ddd;
                      border-radius:8px;font-size:13px;outline:none;
                      transition:all 0.15s;" 
               onfocus="this.style.borderColor='var(--ep-teal)';this.style.boxShadow='0 0 0 3px rgba(13,158,117,0.1)'"
               onblur="this.style.borderColor='#ddd';this.style.boxShadow='none'" />
      </div>
      
      <select id="type-filter" onchange="filtrerEtabsPublic()"
              style="padding:11px 14px;border:1px solid #ddd;border-radius:8px;
                     font-size:13px;background:#fff;outline:none;cursor:pointer;
                     transition:all 0.15s;min-width:160px;"
              onfocus="this.style.borderColor='var(--ep-teal)';this.style.boxShadow='0 0 0 3px rgba(13,158,117,0.1)'"
              onblur="this.style.borderColor='#ddd';this.style.boxShadow='none'">
        <option value="">{{ __('public.type_tous') }}</option>
        <option value="maternelle">{{ __('public.type_maternelle') }}</option>
        <option value="primaire">{{ __('public.type_primaire') }}</option>
        <option value="college">{{ __('public.type_college') }}</option>
        <option value="lycee_general">{{ __('public.type_lycee_general') }}</option>
        <option value="lycee_technique">{{ __('public.type_lycee_technique') }}</option>
        <option value="institut">{{ __('public.type_institut') }}</option>
      </select>
      
      <button type="button" id="filter-btn" onclick="filtrerEtabsPublic()" 
              style="padding:11px 20px;border:none;border-radius:8px;
                     background:var(--ep-teal);font-size:13px;font-weight:600;
                     cursor:pointer;color:#fff;transition:all 0.15s;
                     box-shadow:0 2px 4px rgba(13,158,117,0.2);display:inline-flex;
                     align-items:center;gap:6px;"
              onmouseover="this.style.background='#0B8A62';this.style.transform='translateY(-1px)';this.style.boxShadow='0 4px 8px rgba(13,158,117,0.3)'"
              onmouseout="this.style.background='var(--ep-teal)';this.style.transform='translateY(0)';this.style.boxShadow='0 2px 4px rgba(13,158,117,0.2)'">
        <span class="material-symbols-outlined" style="font-size:18px;">search</span>
        {{ __('public.btn_rechercher') }}
      </button>
      
      <button type="button" id="reset-filter-btn" onclick="resetFiltreEtabs()" 
              style="padding:11px 18px;border:1px solid #ddd;border-radius:8px;
                     background:#fff;font-size:13px;font-weight:500;cursor:pointer;
                     color:#666;display:none;transition:all 0.15s;
                     inline-flex;align-items:center;gap:6px;"
              onmouseover="this.style.background='#f8f8f8';this.style.borderColor='#999'"
              onmouseout="this.style.background='#fff';this.style.borderColor='#ddd'">
        <span class="material-symbols-outlined" style="font-size:16px;color:#666;">close</span>
        {{ __('public.btn_reinitialiser') }}
      </button>
    </div>
    
    {{-- Compteur de résultats --}}
    <div id="results-counter" style="font-size:12px;color:#666;margin-bottom:12px;display:none;">
      <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle;color:var(--ep-teal);">filter_alt</span>
      <span id="results-count">0</span> {{ __('public.resultats_etabs') }}
    </div>

    {{-- Grille établissements --}}
    <div id="etabs-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px;" data-reveal-stagger="60">
      @forelse($etablissements as $etab)
      <a href="{{ route('etablissement.show', $etab->code_etablissement) }}"
           class="etab-card-pub reveal-on-scroll"
           data-nom="{{ e(strtolower($etab->nom)) }}"
           data-ville="{{ e(strtolower($etab->ville ?? '')) }}"
           data-type="{{ e(strtolower($etab->type ?? '')) }}"
           style="background:#fff;border:1px solid #eee;border-radius:12px;
                  padding:16px;text-align:center;transition:box-shadow .2s,transform .2s;
                  cursor:pointer;text-decoration:none;color:inherit;display:block;"
           onmouseover="this.style.boxShadow='0 8px 20px rgba(13,158,117,.15)';this.style.transform='translateY(-3px)'"
           onmouseout="this.style.boxShadow='none';this.style.transform='translateY(0)'">
        {{-- Logo ou avatar --}}
        @if($etab->logo)
          <img src="{{ asset('storage/'.$etab->logo) }}"
               alt="{{ $etab->nom }}"
               style="width:56px;height:56px;border-radius:10px;object-fit:cover;
                      margin:0 auto 10px;display:block;border:1px solid #eee;" />
        @else
          <div style="width:56px;height:56px;border-radius:10px;
                      background:var(--ep-teal-lt);display:flex;align-items:center;
                      justify-content:center;margin:0 auto 10px;
                      font-size:22px;font-weight:700;color:var(--ep-teal);">
            {{ strtoupper(substr($etab->nom, 0, 1)) }}
          </div>
        @endif

        <div style="font-size:13px;font-weight:700;color:#1a1a2e;margin-bottom:4px;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
             title="{{ $etab->nom }}">
          {{ $etab->nom }}
        </div>
        <div style="font-size:11px;color:#888;margin-bottom:8px;display:flex;align-items:center;justify-content:center;gap:4px;">
          <span class="material-symbols-outlined" style="font-size:14px;color:#888;">location_on</span>
          {{ $etab->ville ?? '—' }}
        </div>
        <span style="font-size:10px;padding:3px 8px;border-radius:20px;
                     background:var(--ep-teal-lt);color:#085041;font-weight:500;">
          {{ ucfirst(str_replace('_', ' ', $etab->type ?? 'Établissement')) }}
        </span>
      </a>
      @empty
      <div style="grid-column:1/-1;text-align:center;color:#aaa;padding:40px 0;font-size:13px;">
        {{ __('public.aucun_etab_partenaire') }}
        <div style="margin-top:12px;">
          <a href="{{ route('register.ecole.step1') }}" class="hbtn-main"
             style="font-size:13px;padding:10px 20px;">
            {{ __('public.cta_inscrire_maintenant') }}
          </a>
        </div>
      </div>
      @endforelse
    </div>

    {{-- Voir plus si beaucoup d'établissements --}}
    @if($etablissements->count() > 12)
    <div style="text-align:center;margin-top:16px;">
      <button onclick="toggleTousEtabs(this)"
              style="background:transparent;color:var(--ep-teal);border:2px solid var(--ep-teal);
                     padding:10px 24px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;">
        {{ __('public.voir_tous_etabs', ['count' => $etablissements->count()]) }}
      </button>
    </div>
    @endif
  </div>

  <div class="seclbl reveal-on-scroll" style="margin-top:4px;">{{ __('public.pourquoi_edupay') }}</div>
  <div class="feat-grid" style="margin-bottom:24px;" data-reveal-stagger="70">

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:var(--ep-teal);"></div>
      <div class="feat-icon" style="background:var(--ep-teal-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_mobile_money_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_mobile_money_desc') }}</div>
    </div>

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:var(--ep-gold);"></div>
      <div class="feat-icon" style="background:var(--ep-gold-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_recu_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_recu_desc') }}</div>
    </div>

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:#185FA5;"></div>
      <div class="feat-icon" style="background:var(--ep-blue-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_dashboard_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_dashboard_desc') }}</div>
    </div>

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:#7C3AED;"></div>
      <div class="feat-icon" style="background:var(--ep-purple-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_securite_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_securite_desc') }}</div>
    </div>

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:var(--ep-red);"></div>
      <div class="feat-icon" style="background:var(--ep-red-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#D94040" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_fractionne_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_fractionne_desc') }}</div>
    </div>

    <div class="feat-card reveal-on-scroll">
      <div class="feat-line" style="background:var(--ep-teal);"></div>
      <div class="feat-icon" style="background:var(--ep-teal-lt);">
        <svg viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
      <div class="feat-title">{{ __('public.feat_multi_titre') }}</div>
      <div class="feat-desc">{{ __('public.feat_multi_desc') }}</div>
    </div>

  </div>

  <div class="seclbl reveal-on-scroll">{{ __('public.concu_systeme_edu') }}</div>
  <div class="g4" style="margin-bottom:24px;" data-reveal-stagger="70">
    <div class="epcard reveal-on-scroll" style="text-align:center;border-top:3px solid var(--ep-teal);">
      <div style="width:36px;height:36px;background:var(--ep-teal-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">{!! __('public.card_mat_prim_titre') !!}</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">{{ __('public.card_mat_prim_desc') }}</div>
    </div>
    <div class="epcard reveal-on-scroll" style="text-align:center;border-top:3px solid var(--ep-gold);">
      <div style="width:36px;height:36px;background:var(--ep-gold-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">{!! __('public.card_coll_lyc_titre') !!}</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">{{ __('public.card_coll_lyc_desc') }}</div>
    </div>
    <div class="epcard reveal-on-scroll" style="text-align:center;border-top:3px solid #185FA5;">
      <div style="width:36px;height:36px;background:var(--ep-blue-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><rect x="2" y="7" width="20" height="15"/><polyline points="16 2 12 7 8 2"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">{!! __('public.card_univ_inst_titre') !!}</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">{{ __('public.card_univ_inst_desc') }}</div>
    </div>
    <div class="epcard reveal-on-scroll" style="text-align:center;border-top:3px solid #7C3AED;">
      <div style="width:36px;height:36px;background:var(--ep-purple-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7C3AED" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
      <div style="font-weight:600;font-size:13px;">{!! __('public.card_par_etud_titre') !!}</div>
      <div style="font-size:11px;color:#888;margin-top:4px;">{{ __('public.card_par_etud_desc') }}</div>
    </div>
  </div>

  <div style="background:var(--ep-navy);border-radius:var(--radius-lg);padding:28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:4px;">
    <div>
      <div style="font-size:18px;font-weight:600;color:#fff;margin-bottom:5px;">{{ __('public.etab_pas_encore') }}</div>
      <div style="font-size:13px;color:rgba(255,255,255,.55);">{{ __('public.inscription_gratuite_desc') }}</div>
    </div>
    <a href="{{ route('register.ecole.step1') }}" style="background:var(--ep-teal);color:#fff;border:none;padding:13px 26px;border-radius:var(--radius-md);font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;">{{ __('public.cta_inscrire_etablissement') }} →</a>
  </div>

</div>

{{-- ══ FOOTER ══ --}}
<div class="ep-footer">
  <div class="footer-grid">
    <div>
      <div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div>
      <div class="footer-desc">{{ __('public.footer_desc') }}</div>
      <div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_produit') }}</div>
      <a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_fonctionnalites') }}</a>
      <a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a>
      <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
      <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscrire_ecole') }}</a>
      <a class="footer-link" href="{{ route('login', ['role' => 'etablissement']) }}">{{ __('public.footer_backoffice') }}</a>
      <a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide_utilisation') }}</a>
      <a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support_dedie') }}</a>
    </div>
    <div>
      <div class="footer-col-title">{{ __('public.footer_col_informations') }}</div>
      <a class="footer-link" href="{{ route('about') }}">{{ __('public.footer_a_propos') }}</a>
      <a class="footer-link" href="{{ route('contact') }}">{{ __('public.footer_contact') }}</a>
      <a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a>
      <a class="footer-link" href="{{ route('cgu') }}">{{ __('public.footer_conditions') }}</a>
    </div>
  </div>
  <div class="footer-bottom">
    <div>
      <div class="footer-legal">{{ __('public.footer_legal') }}</div>
      <div class="certif">
        <span class="cert-badge">{{ __('public.footer_mtn_partner') }}</span>
        <span class="cert-badge">{{ __('public.footer_orange_integre') }}</span>
        <span class="cert-badge">{{ __('public.footer_cinetpay_certifie') }}</span>
        <span class="cert-badge">{{ __('public.footer_cobac_conforme') }}</span>
      </div>
    </div>
    <div class="footer-socials">
      <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="LinkedIn" title="LinkedIn" style="background:#0A66C2;border-color:#0A66C2;color:#fff;">
    <i class="fa-brands fa-linkedin-in"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="X (Twitter)" title="X" style="background:#000;border-color:#000;color:#fff;">
    <i class="fa-brands fa-x-twitter"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="WhatsApp" title="WhatsApp" style="background:#25D366;border-color:#25D366;color:#fff;">
    <i class="fa-brands fa-whatsapp"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Facebook" title="Facebook" style="background:#1877F2;border-color:#1877F2;color:#fff;">
    <i class="fa-brands fa-facebook-f"></i>
  </a>
  <a href="#" target="_blank" rel="noopener" class="social-btn" aria-label="Instagram" title="Instagram" style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);border:none;color:#fff;">
    <i class="fa-brands fa-instagram"></i>
  </a>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
.etab-card-pub:hover {
    box-shadow: 0 4px 20px rgba(13,158,117,.12);
    border-color: var(--ep-teal-mid) !important;
    transform: translateY(-2px);
}
</style>
@endpush

@push('scripts')
<script>
// ── Traductions dynamiques ──
var EP_LANG = {
    aucun_trouve: @json(__('public.aucun_etab_trouve')),
    essayez_autre: @json(__('public.essayez_autre_critere')),
    reduire_liste: @json(__('public.reduire_liste')),
    voir_tous: @json(__('public.voir_tous_etabs'))
};

// ── Filtre établissements publics ──
var allCards = null;
var etabsLimites = false;
var totalCards = 0;

function filtrerEtabsPublic() {
    if (!allCards) {
        allCards = document.querySelectorAll('.etab-card-pub');
        totalCards = allCards.length;
    }
    
    var searchInput = document.getElementById('etab-filter');
    var typeSelect = document.getElementById('type-filter');
    var resetBtn = document.getElementById('reset-filter-btn');
    var resultsCounter = document.getElementById('results-counter');
    var resultsCount = document.getElementById('results-count');
    
    var q    = (searchInput.value || '').toLowerCase().trim();
    var type = (typeSelect.value || '').toLowerCase().trim();
    
    // Afficher/masquer le bouton reset et le compteur
    if (q || type) {
        resetBtn.style.display = '';
        resultsCounter.style.display = '';
    } else {
        resetBtn.style.display = 'none';
        resultsCounter.style.display = 'none';
    }
    
    var visibleCount = 0;
    
    allCards.forEach(function(card, idx) {
        var nom   = (card.getAttribute('data-nom') || '').toLowerCase();
        var ville = (card.getAttribute('data-ville') || '').toLowerCase();
        var t     = (card.getAttribute('data-type') || '').toLowerCase();
        
        // Recherche dans nom et ville
        var matchQ = !q || nom.indexOf(q) !== -1 || ville.indexOf(q) !== -1;
        
        // Comparaison exacte du type
        var matchType = !type || t === type;
        
        // Affichage basé sur le filtre ET la limite si activée
        var shouldShow = matchQ && matchType;
        
        if (shouldShow) {
            // Si limité et que c'est au-delà de 12, ne pas afficher
            if (!etabsLimites && totalCards > 12 && idx >= 12) {
                card.style.display = 'none';
            } else {
                card.style.display = '';
                card.style.animation = 'fadeIn 0.3s ease-in';
                visibleCount++;
            }
        } else {
            card.style.display = 'none';
        }
    });
    
    // Mettre à jour le compteur
    if (resultsCount) {
        resultsCount.textContent = visibleCount;
    }
    
    // Afficher un message si aucun résultat
    var grid = document.getElementById('etabs-grid');
    var noResultMsg = document.getElementById('no-result-message');
    
    if (visibleCount === 0 && (q || type)) {
        if (!noResultMsg) {
            noResultMsg = document.createElement('div');
            noResultMsg.id = 'no-result-message';
            noResultMsg.style.cssText = 'grid-column:1/-1;text-align:center;color:#aaa;padding:40px 0;font-size:14px;';
            noResultMsg.innerHTML = '<div style="margin-bottom:16px;"><span class="material-symbols-outlined" style="font-size:64px;color:#ddd;">search_off</span></div>' +
                                   '<div style="font-weight:600;color:#666;margin-bottom:8px;">' + EP_LANG.aucun_trouve + '</div>' +
                                   '<div style="font-size:12px;">' + EP_LANG.essayez_autre + '</div>';
            grid.appendChild(noResultMsg);
        }
        noResultMsg.style.display = '';
    } else if (noResultMsg) {
        noResultMsg.style.display = 'none';
    }
    
    console.log('🔍 Filtre appliqué: ' + visibleCount + '/' + totalCards + ' établissement(s)');
}

// ── Réinitialiser les filtres ──
function resetFiltreEtabs() {
    var searchInput = document.getElementById('etab-filter');
    var typeSelect = document.getElementById('type-filter');
    var resetBtn = document.getElementById('reset-filter-btn');
    var resultsCounter = document.getElementById('results-counter');
    
    searchInput.value = '';
    typeSelect.value = '';
    resetBtn.style.display = 'none';
    resultsCounter.style.display = 'none';
    
    filtrerEtabsPublic();
    
    console.log('🔄 Filtres réinitialisés');
}

// ── Afficher / masquer tous les établissements ──
function toggleTousEtabs(btn) {
    if (!allCards) allCards = document.querySelectorAll('.etab-card-pub');
    
    etabsLimites = !etabsLimites;
    
    // Réappliquer le filtre avec la nouvelle limite
    filtrerEtabsPublic();
    
    btn.textContent = etabsLimites
        ? EP_LANG.reduire_liste
        : EP_LANG.voir_tous.replace(':count', totalCards);
        
    console.log('👁️ Affichage: ' + (etabsLimites ? 'tous' : '12 premiers'));
}

// ── Limiter à 12 au chargement si > 12 ──
document.addEventListener('DOMContentLoaded', function() {
    allCards = document.querySelectorAll('.etab-card-pub');
    totalCards = allCards.length;
    
    if (totalCards > 12) {
        allCards.forEach(function(card, idx) {
            if (idx >= 12) {
                card.style.display = 'none';
            }
        });
    }
    
    console.log('✅ Page chargée: ' + totalCards + ' établissement(s) disponible(s)');
    
    // Permettre la recherche avec Enter
    document.getElementById('etab-filter').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            filtrerEtabsPublic();
        }
    });
});

// Animation fadeIn
var style = document.createElement('style');
style.innerHTML = '@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }';
document.head.appendChild(style);
</script>
@endpush

