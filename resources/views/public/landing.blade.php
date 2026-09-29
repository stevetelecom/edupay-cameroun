@extends('layouts.public')

@section('title', __('public.landing_title'))

{{-- Styles spécifiques à la landing (v3) : chargés via @stack('styles')
     déclaré dans layouts/public.blade.php --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('css/edupay-landing.css') }}">
<style>
/* Corps de la landing : suit le thème clair/sombre (le layout
   fixe .ep-body2 en clair ; ici on laisse la variable décider) */
.lp .ep-body2 { background: transparent; }
</style>
@endpush

@section('content')

@include('layouts._navbar_public')

{{-- ════════════════════════════════════════════════════════════
     HERO — grille 2 colonnes : copie + scène 3D flottante.
     Vidéo de fond + orbes de gravitation + carte bancaire animée.
     Patterns fintech 2026 : preuve chiffrée, confiance sous CTA.
     Le wrapper .lp expose les variables locales du thème landing.
     ════════════════════════════════════════════════════════════ --}}
<div class="lp">
<div class="lp-hero">
  {{-- Vidéo de fond (masquée sur mobile / reduced-motion via CSS) --}}
  <video class="lp-video" autoplay muted loop playsinline preload="metadata"
         aria-hidden="true" tabindex="-1">
    <source src="{{ asset('videos/hero-payment.mp4') }}" type="video/mp4">
  </video>
  <div class="lp-video-overlay"></div>

  {{-- Orbes de gravitation (décor pur, jamais cliquables) --}}
  <div class="lp-orb lp-orb-a" aria-hidden="true"></div>
  <div class="lp-orb lp-orb-b" aria-hidden="true"></div>
  <div class="lp-orb lp-orb-c" aria-hidden="true"></div>
  <div class="lp-grille" aria-hidden="true"></div>

  <div class="lp-hero-inner">

    {{-- ── Colonne copie ── --}}
    <div class="lp-hero-copy">
      <div class="lp-tag">
        <span class="lp-pulse" aria-hidden="true"></span>
        <span class="material-symbols-rounded" aria-hidden="true">bolt</span>
        {{ __('public.hero_tag') }}
      </div>

      <h1 class="lp-h1">
        {{ __('public.hero_h1_line1') }} {{ __('public.hero_h1_connector') }}
        <em class="lp-souligne">{{ __('public.hero_h1_line2_em') }}
          <svg viewBox="0 0 220 10" preserveAspectRatio="none" aria-hidden="true">
            <path d="M2 7 Q 30 2, 60 6 T 118 6 T 176 5 T 218 6"/>
          </svg>
        </em><br>
        {{ __('public.hero_h1_line3') }}
      </h1>

      <p class="lp-sub">{{ __('public.hero_sub') }}</p>

      <div class="lp-ctas">
        <a href="{{ route('register.parent.step1') }}" class="lp-btn lp-btn-main">
          {{ __('public.cta_creer_compte_payeur') }}
          <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
        </a>
        <a href="{{ route('register.ecole.step1') }}" class="lp-btn lp-btn-ghost">
          {{ __('public.cta_inscrire_etablissement') }}
        </a>
      </div>

      {{-- Micro-signaux de confiance, juste sous les CTA --}}
      <div class="lp-confiance">
        <span><span class="material-symbols-rounded" aria-hidden="true">lock</span>{{ __('public.hero_confiance_1') }}</span>
        <span><span class="material-symbols-rounded" aria-hidden="true">description</span>{{ __('public.hero_confiance_2') }}</span>
        <span><span class="material-symbols-rounded" aria-hidden="true">support_agent</span>{{ __('public.hero_confiance_3') }}</span>
      </div>
    </div>

    {{-- ── Colonne scène 3D : carte + badges flottants (tilt souris) ── --}}
    <div class="lp-scene" aria-hidden="true">
      <div class="lp-tilt">

        {{-- Carte de paiement EduPay (dégradé logo) --}}
        <div class="lp-carte-visa">
          <div class="lp-carte-haut">
            <div class="lp-carte-puce">
              <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
            </div>
            <span class="material-symbols-rounded" style="font-size:22px;color:rgba(255,255,255,.85);">contactless</span>
          </div>

          <div class="lp-carte-num">5321&nbsp;&nbsp;••••&nbsp;&nbsp;••••&nbsp;&nbsp;2026</div>

          <div class="lp-carte-bas">
            <div>
              {{ __('public.hero_carte_beneficiaire') }}
              <b>{{ __('public.hero_carte_ecole') }}</b>
            </div>
            <div class="lp-carte-marque">
              EDUPAY
              <small>CAMEROUN</small>
            </div>
          </div>
        </div>

        {{-- Badge MTN MoMo / Orange Money flottant --}}
        <div class="lp-badge-momo">
          <i class="i-mtn"><span class="material-symbols-rounded">smartphone</span></i>
          {{ __('public.hero_badge_momo') }}
          <i class="i-orange"><span class="material-symbols-rounded">account_balance_wallet</span></i>
          {{ __('public.hero_badge_om') }}
        </div>

        {{-- Toast de paiement confirmé --}}
        <div class="lp-toast-paiement">
          <div class="lp-toast-ico">
            <span class="material-symbols-rounded">check_circle</span>
          </div>
          <div>
            <b>{{ __('public.hero_carte_montant') }}</b>
            <small>{{ __('public.hero_carte_statut') }} · {{ __('public.hero_carte_ref') }}</small>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- ── Compteurs animés : barre glassmorphism ── --}}
  <div class="lp-stats">
    <div class="lp-stats-inner" data-stats-container>
      <div class="lp-stat">
        <span class="material-symbols-rounded" aria-hidden="true">school</span>
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.stat_lbl_etablissements') }}</div>
      </div>
      <div class="lp-stat">
        <span class="material-symbols-rounded" aria-hidden="true">groups</span>
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.stat_lbl_apprenants') }}</div>
      </div>
      <div class="lp-stat">
        <span class="material-symbols-rounded" aria-hidden="true">task_alt</span>
        <div class="lp-stat-v stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
        <div class="lp-stat-l">{{ __('public.stat_lbl_paiements') }}</div>
      </div>
      <div class="lp-stat">
        <span class="material-symbols-rounded" aria-hidden="true">cloud_done</span>
        <div class="lp-stat-v stat-counter" data-count="99.5" data-decimals="1" data-suffix="%">0%</div>
        <div class="lp-stat-l">{{ __('public.stat_lbl_uptime') }}</div>
      </div>
    </div>
    {{-- Preuve de marché (chiffres du CDC : 30 000 écoles, 6M apprenants) --}}
    <p style="text-align:center;font-size:11px;color:rgba(255,255,255,.45);margin:12px 0 0;">
      {{ __('public.stats_marche_note', [
          'etabs' => __('public.stat_etablissements_partenaires'),
          'appr'  => __('public.stat_apprenants_inscrits'),
          'momo'  => __('public.stat_paiements_valides'),
      ]) }}
    </p>
  </div>{{-- /.lp-stats --}}
</div>{{-- /.lp-hero --}}

{{-- ── Barre de confiance : canaux de paiement intégrés ── --}}
<div class="lp-partenaires">{{-- (reste dans .lp, fermé plus bas) --}}
  <div class="lp-partenaires-inner">
    <span class="lp-partenaires-label">{{ __('public.partenaires_titre') }}</span>
    <span class="lp-partenaire"><i class="p-mtn"><span class="material-symbols-rounded">smartphone</span></i>MTN MoMo</span>
    <span class="lp-partenaire"><i class="p-orange"><span class="material-symbols-rounded">account_balance_wallet</span></i>Orange Money</span>
    <span class="lp-partenaire"><i class="p-aang"><span class="material-symbols-rounded">hub</span></i>AangaraaPay</span>
    <span class="lp-partenaire"><i class="p-coleac"><span class="material-symbols-rounded">credit_card</span></i>Visa · Mastercard</span>
    <span class="lp-partenaire"><i class="p-secure"><span class="material-symbols-rounded">verified_user</span></i>TLS 1.3</span>
  </div>
</div>

{{-- ══ CORPS DE PAGE ══ --}}
<div class="ep-body2">

  {{-- ══ SECTION : annuaire des établissements partenaires ══ --}}
  <div id="etablissements" class="lp-section" style="scroll-margin-top:20px;">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">location_city</span>
      {{ __('public.nos_etablissements_partenaires') }}
    </div>
    <h2 class="lp-sectitre reveal-on-scroll">
      {{ __('public.etablissements_nous_f_confiance', ['count' => $stats['nb_etablissements']]) }}
    </h2>
    <p class="lp-secsub reveal-on-scroll">{{ __('public.etabs_annuaire_sub') }}</p>

    {{-- Filtre rapide — filtrage SERVEUR + pagination (les écoles au-delà
         de la 12e carte doivent rester trouvables) --}}
    <form method="GET" action="{{ route('landing') }}#etablissements" id="etab-filtre-form"
          class="lp-filtres">
      <div class="lp-filtre-champ">
        <span class="material-symbols-rounded" aria-hidden="true">search</span>
        <input type="text" id="etab-filter" name="q" value="{{ $q }}"
               class="lp-input" placeholder="{{ __('public.rechercher_placeholder') }}"
               autocomplete="off" aria-label="{{ __('public.rechercher_placeholder') }}" />
      </div>

      <select id="type-filter" name="type" class="lp-select"
              aria-label="{{ __('public.type_tous') }}">
        <option value="">{{ __('public.type_tous') }}</option>
        @foreach($types as $type_valeur => $type_libelle)
        <option value="{{ $type_valeur }}" @selected($type === $type_valeur)>{{ __($type_libelle) }}</option>
        @endforeach
      </select>

      <button type="submit" id="filter-btn" class="lp-btn-filtre">
        <span class="material-symbols-rounded" aria-hidden="true">search</span>
        {{ __('public.btn_rechercher') }}
      </button>

      @if($q !== '' || $type !== '')
      <a href="{{ route('landing') }}" id="reset-filter-btn" class="lp-btn-reset">
        <span class="material-symbols-rounded" aria-hidden="true">close</span>
        {{ __('public.btn_reinitialiser') }}
      </a>
      @endif
    </form>

    {{-- Compteur de résultats (affiché seulement quand un filtre est actif) --}}
    <div id="results-counter" class="lp-compteur" style="{{ ($q === '' && $type === '') ? 'display:none;' : '' }}">
      <span class="material-symbols-rounded" aria-hidden="true">filter_alt</span>
      <span id="results-count">{{ $etablissements->total() }}</span> {{ __('public.resultats_etabs') }}
    </div>

    {{-- Grille des cartes établissements (style admin.html : pastille,
         bande colorée au survol, badge « Payer en ligne ») --}}
    <div id="etabs-grid" class="lp-etabs" data-reveal-stagger="60">
      @forelse($etablissements as $etab)
      {{-- Dégradé de l'avatar initiale selon le type d'établissement
           (uniquement utilisé quand l'école n'a pas fourni de logo) --}}
      @php
        $avGrades = [
            'maternelle'      => ['#F5B93F', '#C9860E'],
            'primaire'        => ['#0D9E75', '#085041'],
            'college'         => ['#1F6FB2', '#123C66'],
            'lycee_general'   => ['#7C3AED', '#4C1D95'],
            'lycee_technique' => ['#D94040', '#7E1F1A'],
            'institut_prive'  => ['#0A8562', '#064C39'],
            'universite'      => ['#16406E', '#0B2545'],
            'groupe_scolaire' => ['#E8A020', '#8B5E10'],
        ];
        $av = $avGrades[$etab->type] ?? ['#0D9E75', '#0A8562'];
      @endphp
      <a href="{{ route('etablissement.show', $etab->code_etablissement) }}"
         class="lp-etab reveal-on-scroll"
         style="--lp-av1: {{ $av[0] }}; --lp-av2: {{ $av[1] }};"
         data-nom="{{ e(strtolower($etab->nom)) }}"
         data-ville="{{ e(strtolower($etab->ville ?? '')) }}"
         data-type="{{ e(strtolower($etab->type ?? '')) }}">
        @if(!empty($etab->logo))
          {{-- Logo officiel de l'établissement (uploadé à l'inscription
               ou depuis les paramètres). Un logo est TOUJOURS préféré à
               l'initiale : c'est l'identité visuelle réelle de l'école. --}}
          <img class="lp-etab-logo" src="{{ asset('storage/'.$etab->logo) }}"
               alt="Logo {{ $etab->nom }}" loading="lazy"
               onerror="this.style.display='none';this.nextElementSibling.style.display='flex';" />
          {{-- Repli si le fichier logo est introuvable (404) : initiale --}}
          <div class="lp-etab-avatar" style="display:none;" aria-hidden="true">{{ mb_strtoupper(mb_substr($etab->nom, 0, 1)) }}</div>
        @else
          {{-- Pas encore de logo fourni : première lettre du nom --}}
          <div class="lp-etab-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($etab->nom, 0, 1)) }}</div>
        @endif

        <div class="lp-etab-nom" title="{{ $etab->nom }}">{{ $etab->nom }}</div>
        <div class="lp-etab-ville">
          <span class="material-symbols-rounded" aria-hidden="true">location_on</span>
          {{ $etab->ville ?? '—' }}
        </div>
        <span class="lp-etab-type">
          <span class="point" aria-hidden="true"></span>
          {{ ucfirst(str_replace('_', ' ', $etab->type ?? 'Établissement')) }}
        </span>

        <span class="lp-etab-payer">
          <span class="material-symbols-rounded" aria-hidden="true">bolt</span>
          {{ __('public.etab_payer_en_ligne') }}
        </span>
      </a>
      @empty
      <div class="lp-vide">
        <span class="material-symbols-rounded" aria-hidden="true">search_off</span>
        @if($q !== '' || $type !== '')
          {{ __('public.aucun_etab_trouve') }}
          <div style="margin-top:14px;">
            <a href="{{ route('landing') }}" class="lp-btn lp-btn-main" style="font-size:13px;padding:10px 20px;">
              {{ __('public.btn_reinitialiser') }}
            </a>
          </div>
        @else
          {{ __('public.aucun_etab_partenaire') }}
          <div style="margin-top:14px;">
            <a href="{{ route('register.ecole.step1') }}" class="lp-btn lp-btn-main" style="font-size:13px;padding:10px 20px;">
              {{ __('public.cta_inscrire_maintenant') }}
            </a>
          </div>
        @endif
      </div>
      @endforelse
    </div>

    {{-- Pagination serveur --}}
    @if($etablissements->hasPages())
    <nav class="lp-pagination" aria-label="{{ __('public.resultats_etabs') }}">
      @if($etablissements->onFirstPage())
        <span class="lp-page-btn disabled"><span class="material-symbols-rounded">chevron_left</span></span>
      @else
        <a href="{{ $etablissements->previousPageUrl() }}" rel="prev" class="lp-page-btn">
          <span class="material-symbols-rounded">chevron_left</span>
        </a>
      @endif

      <span class="lp-page-infos">
        {{ __('public.page_sur', ['page' => $etablissements->currentPage(), 'total' => $etablissements->lastPage()]) }}
      </span>

      @if($etablissements->hasMorePages())
        <a href="{{ $etablissements->nextPageUrl() }}" rel="next" class="lp-page-btn">
          <span class="material-symbols-rounded">chevron_right</span>
        </a>
      @else
        <span class="lp-page-btn disabled"><span class="material-symbols-rounded">chevron_right</span></span>
      @endif
    </nav>
    @endif
  </div>

  {{-- ══ SECTION : pourquoi EduPay — 6 cartes Material Symbols animées ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">auto_awesome</span>
      {{ __('public.pourquoi_edupay') }}
    </div>
    <h2 class="lp-sectitre reveal-on-scroll">{!! __('public.why_titre_section') !!}</h2>

    <div class="lp-feats" data-reveal-stagger="70">
      {{-- 1 · Mobile Money natif --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;--lp-couleur-halo:rgba(13,158,117,.14);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">smartphone</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_mobile_money_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_mobile_money_desc') }}</div>
      </div>

      {{-- 2 · Reçu PDF instantané --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;--lp-couleur-halo:rgba(232,160,32,.15);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">receipt_long</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_recu_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_recu_desc') }}</div>
      </div>

      {{-- 3 · Dashboard temps réel --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#185FA5;--lp-couleur-halo:rgba(31,111,178,.14);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">bar_chart</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_dashboard_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_dashboard_desc') }}</div>
      </div>

      {{-- 4 · Sécurité --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#6D28D9;--lp-couleur-halo:rgba(139,92,246,.14);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">security</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_securite_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_securite_desc') }}</div>
      </div>

      {{-- 5 · Paiement fractionné --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#D94040;--lp-couleur2:#B03A2E;--lp-couleur-halo:rgba(217,64,64,.13);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">date_range</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_fractionne_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_fractionne_desc') }}</div>
      </div>

      {{-- 6 · Multi-établissements --}}
      <div class="lp-feat reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#085041;--lp-couleur-halo:rgba(13,158,117,.14);">
        <div class="lp-feat-ico"><span class="material-symbols-rounded">device_hub</span></div>
        <div class="lp-feat-titre">{{ __('public.feat_multi_titre') }}</div>
        <div class="lp-feat-desc">{{ __('public.feat_multi_desc') }}</div>
      </div>
    </div>
  </div>

  {{-- ══ SECTION : conçu pour tout le système éducatif ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">school</span>
      {{ __('public.concu_systeme_edu') }}
    </div>

    <div class="lp-types" data-reveal-stagger="70">
      <div class="lp-type reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur-halo:var(--ep-teal-lt);">
        <div class="lp-type-ico"><span class="material-symbols-rounded">child_care</span></div>
        <b>{!! __('public.card_mat_prim_titre') !!}</b>
        <small>{{ __('public.card_mat_prim_desc') }}</small>
      </div>
      <div class="lp-type reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur-halo:var(--ep-gold-lt);">
        <div class="lp-type-ico"><span class="material-symbols-rounded">menu_book</span></div>
        <b>{!! __('public.card_coll_lyc_titre') !!}</b>
        <small>{{ __('public.card_coll_lyc_desc') }}</small>
      </div>
      <div class="lp-type reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur-halo:var(--ep-blue-lt);">
        <div class="lp-type-ico"><span class="material-symbols-rounded">account_balance</span></div>
        <b>{!! __('public.card_univ_inst_titre') !!}</b>
        <small>{{ __('public.card_univ_inst_desc') }}</small>
      </div>
      <div class="lp-type reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur-halo:var(--ep-purple-lt);">
        <div class="lp-type-ico"><span class="material-symbols-rounded">groups</span></div>
        <b>{!! __('public.card_par_etud_titre') !!}</b>
        <small>{{ __('public.card_par_etud_desc') }}</small>
      </div>
    </div>
  </div>

  {{-- ══ CTA FINAL : appel à l'inscription établissement ══ --}}
  <div class="lp-section" style="padding-bottom:40px;">
    <div class="lp-cta-final reveal-on-scroll">
      <div>
        <h3>{{ __('public.etab_pas_encore') }}</h3>
        <p>{{ __('public.inscription_gratuite_desc') }}</p>
      </div>
      <a href="{{ route('register.ecole.step1') }}" class="lp-btn lp-btn-main">
        {{ __('public.cta_inscrire_etablissement') }}
        <span class="material-symbols-rounded" aria-hidden="true">arrow_forward</span>
      </a>
    </div>

    {{-- Bandeau régulateur : confiance réglementaire près du CTA
         (pattern N26 — pas relégué au footer) --}}
    <div class="lp-regulateur reveal-on-scroll">
      <span><span class="material-symbols-rounded" aria-hidden="true">gavel</span>{{ __('public.regulateur_loi') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">account_balance</span>{{ __('public.regulateur_cobac') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">verified_user</span>{{ __('public.regulateur_beac') }}</span>
      <span><span class="material-symbols-rounded" aria-hidden="true">enhanced_encryption</span>{{ __('public.regulateur_donnees') }}</span>
    </div>
  </div>

</div>{{-- /.ep-body2 --}}
</div>{{-- /.lp : fin du wrapper de variables --}}

{{-- ══ FOOTER PRO v3 : 4 colonnes, colonnes contact + newsletter
     visuel des moyens de paiement, barre légale complète ══ --}}
<footer class="ep-footer lp-footer">
  <div class="lp-footer-inner">

    {{-- Rangée 1 : marque + 3 colonnes de liens + contact --}}
    <div class="footer-grid">
      <div>
        <div class="footer-logo" style="display:flex;align-items:center;gap:10px;">
          <span style="width:46px;height:46px;border-radius:13px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.2);">
            <img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" />
          </span>
          <span>Edu<span style="color:#5DCAA5;">Pay</span></span>
        </div>
        <div class="footer-desc">{{ __('public.footer_desc') }}</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;">
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">lock</span>TLS 1.3</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">verified_user</span>PCI-DSS</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">account_balance</span>COBAC</span>
        </div>
      </div>

      <div>
        <div class="footer-col-title">{{ __('public.footer_col_produit') }}</div>
        <a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_fonctionnalites') }}</a>
        <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
        <a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a>
        <a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide_utilisation') }}</a>
      </div>

      <div>
        <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
        <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscrire_ecole') }}</a>
        <a class="footer-link" href="{{ route('login', ['role' => 'etablissement']) }}">{{ __('public.footer_backoffice') }}</a>
        <a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support_dedie') }}</a>
      </div>

      <div>
        <div class="footer-col-title">{{ __('public.footer_col_contact') }}</div>
        <a class="footer-link" href="mailto:{{ config('mail.contact_address', 'contact@edupay.cm') }}" style="text-transform:none;letter-spacing:0;">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">mail</span>
          {{ config('mail.contact_address', 'contact@edupay.cm') }}
        </a>
        <a class="footer-link" href="tel:+237600000000" style="text-transform:none;letter-spacing:0;">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">call</span>
          (+237) 6 00 00 00 00
        </a>
        <a class="footer-link" href="{{ route('contact') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">forum</span>
          {{ __('public.footer_contact') }}
        </a>
        <a class="footer-link" href="{{ route('about') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">info</span>
          {{ __('public.footer_a_propos') }}
        </a>
      </div>
    </div>

    {{-- Rangée 2 : moyens de paiement acceptés (icônes Material sur
         pastilles aux couleurs officielles des opérateurs) --}}
    <div class="lp-footer-paiements">
      <span class="lp-footer-paiements-label">{{ __('public.footer_moyens_paiement') }}</span>
      <span class="lp-paiement-chip" style="background:#FFCC00;color:#3A2E00;">MTN MoMo</span>
      <span class="lp-paiement-chip" style="background:#FF6600;color:#fff;">Orange Money</span>
      <span class="lp-paiement-chip" style="background:#1F6FB2;color:#fff;">Visa</span>
      <span class="lp-paiement-chip" style="background:#1A1F71;color:#fff;">Mastercard</span>
      <span class="lp-paiement-chip" style="background:#0B2545;color:#9FE1CB;">AangaraaPay</span>
    </div>

    {{-- Rangée 3 : barre légale --}}
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
      @include('partials.footer-socials')
    </div>

  </div>
</footer>

@endsection

@push('scripts')
<script src="{{ asset('js/ep-landing.js') }}" defer></script>
@endpush
