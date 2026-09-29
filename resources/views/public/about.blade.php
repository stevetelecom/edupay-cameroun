@extends('layouts.public')

@section('title', __('public.about_title'))

@push('styles')
<link rel="stylesheet" href="{{ asset('css/edupay-landing.css') }}">
<style>
/* Corps des pages secondaires : suit le thème clair/sombre */
.lp .ep-body2, .lp-page .ep-body2 { background: transparent; }
</style>
@endpush

@section('content')

@include('layouts._navbar_public')
<div class="lp lp-page">
{{-- ══ HERO SECONDAIRE v3 : orbes + badge + stats glassmorphism ══ --}}
<div class="lp-hero-sec">
  <div class="lp-orb lp-orb-a" aria-hidden="true"></div>
  <div class="lp-orb lp-orb-b" aria-hidden="true"></div>
  <div class="lp-hero-sec-inner">
    <div class="lp-tag" style="justify-content:center;">
      <span class="lp-pulse" aria-hidden="true"></span>
      <span class="material-symbols-rounded" aria-hidden="true">diversity_3</span>
      {{ __('public.about_hero_tag') }}
    </div>
    <h1 class="lp-h1" style="font-size:34px;">
      {{ __('public.about_hero_h1_prefix') }}
      <em>{{ __('public.about_hero_h1_em') }}</em>
    </h1>
    <p class="lp-sub" style="margin-left:auto;margin-right:auto;">{{ __('public.about_hero_sub') }}</p>
  </div>
</div>

<div class="ep-body2">

  {{-- ══ MISSION v3 ══ --}}
  <div class="lp-section" style="padding-top:44px;">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">flag</span>
      {{ __('public.mission_lbl') }}
    </div>
    <h2 class="lp-sectitre reveal-on-scroll">{{ __('public.mission_titre') }}</h2>
    <div class="lp-feat" style="max-width:760px;margin:0 auto 8px;--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;">
      <p class="lp-feat-desc" style="font-size:13.5px;margin:0;">{{ __('public.mission_texte') }}</p>
      <div style="display:flex;align-items:center;gap:9px;margin-top:14px;padding-top:14px;border-top:1px solid var(--lp-bord);">
        <span class="material-symbols-rounded" style="font-size:19px;color:var(--lp-teal);" aria-hidden="true">verified</span>
        <b style="font-size:12.5px;color:var(--lp-encre);">{{ __('public.mission_conclusion') }}</b>
      </div>
    </div>
  </div>

  {{-- ══ VALEURS v3 : 4 cartes avec pastilles Material animées ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">diamond</span>
      {{ __('public.nos_valeurs') }}
    </div>
    <h2 class="lp-sectitre reveal-on-scroll">{{ __('public.nos_valeurs') }}</h2>
    <p class="lp-secsub reveal-on-scroll">{{ __('public.valeurs_sub') }}</p>

    <div class="lp-feats" data-reveal-stagger="70">
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#0A8562;">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">public</span></div>
        <b>{{ __('public.valeur_accessibilite_titre') }}</b>
        <small>{{ __('public.valeur_accessibilite_desc') }}</small>
      </div>
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">shield_lock</span></div>
        <b>{{ __('public.valeur_securite_titre') }}</b>
        <small>{{ __('public.valeur_securite_desc') }}</small>
      </div>
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">location_on</span></div>
        <b>{{ __('public.valeur_ancrage_titre') }}</b>
        <small>{{ __('public.valeur_ancrage_desc') }}</small>
      </div>
      <div class="lp-valeur reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#4C1D95;">
        <div class="lp-valeur-ico"><span class="material-symbols-rounded">trending_up</span></div>
        <b>{{ __('public.valeur_impact_titre') }}</b>
        <small>{{ __('public.valeur_impact_desc') }}</small>
      </div>
    </div>
  </div>

  {{-- ══ CONTEXTE CAMEROUN v3 : KPI avec pastilles Material ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">travel_explore</span>
      {{ __('public.contexte_cameroun') }}
    </div>
    <div class="lp-feats" style="grid-template-columns:repeat(4,1fr);gap:14px;" data-reveal-stagger="60">
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#085041;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">school</span></div>
        <div>
          <div class="lp-kpi-v">30 000+</div>
          <div class="lp-kpi-l">{{ __('public.ctx_etabs_30k') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">groups</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="6000000">0</div>
          <div class="lp-kpi-l">{{ __('public.ctx_apprenants_6m') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">smartphone</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="12000000">0</div>
          <div class="lp-kpi-l">{{ __('public.ctx_momo_12m') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#4C1D95;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">phone_android</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="45" data-suffix="%">0%</div>
          <div class="lp-kpi-l">{{ __('public.ctx_smartphone_45') }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ EDUPAY EN CHIFFRES v3 ══ --}}
  <div class="lp-section">
    <div class="lp-seclbl reveal-on-scroll">
      <span class="material-symbols-rounded" aria-hidden="true">query_stats</span>
      {{ __('public.edupay_chiffres') }}
    </div>
    <div class="lp-feats" style="grid-template-columns:repeat(4,1fr);gap:14px;" data-reveal-stagger="60" data-stats-container>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#0D9E75;--lp-couleur2:#085041;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">apartment</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="{{ $stats['nb_etablissements'] }}">0</div>
          <div class="lp-kpi-l">{{ __('public.etabs_actifs') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#1F6FB2;--lp-couleur2:#123C66;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">group</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="{{ $stats['nb_apprenants'] }}">0</div>
          <div class="lp-kpi-l">{{ __('public.apprenants_inscrits') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#E8A020;--lp-couleur2:#C9860E;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">task_alt</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="{{ $stats['nb_paiements'] }}">0</div>
          <div class="lp-kpi-l">{{ __('public.paiements_valides') }}</div>
        </div>
      </div>
      <div class="lp-kpi reveal-on-scroll" style="--lp-couleur:#7C3AED;--lp-couleur2:#4C1D95;">
        <div class="lp-kpi-ico"><span class="material-symbols-rounded">payments</span></div>
        <div>
          <div class="lp-kpi-v stat-counter" data-count="{{ $stats['montant_total'] }}" data-suffix=" FCFA">0 FCFA</div>
          <div class="lp-kpi-l">{{ __('public.fcfa_collectes') }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="seclbl reveal-on-scroll">{{ __('public.equipe_projet') }}</div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:20px;" data-reveal-stagger="60">

    {{-- 1. MEKONTSO OLIVIER STEVE — Chef de groupe --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('olivier')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-teal-lt);color:#085041;overflow:hidden;">
        <img src="{{ asset('images/team/olivier.jpg') }}" alt="MEKONTSO OLIVIER STEVE"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">MO</span>
      </div>
      <div style="font-size:12px;font-weight:700;">MEKONTSO OLIVIER STEVE</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_chef_groupe') }} · GSI</div>
      <span class="pill pg" style="margin-top:6px;font-size:10px;">{{ __('public.pill_lead') }}</span>
    </div>

    {{-- 2. MELOUNI MARCELLE ANAIS — Design --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('marcelle')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-purple-lt);color:#5B21B6;overflow:hidden;">
        <img src="{{ asset('images/team/marcelle.jpg') }}" alt="MELOUNI MARCELLE ANAIS"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">MA</span>
      </div>
      <div style="font-size:12px;font-weight:700;">MELOUNI MARCELLE ANAIS</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_design') }} · GSA</div>
      <span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;margin-top:6px;font-size:10px;">{{ __('public.pill_ui_maquettes') }}</span>
    </div>

    {{-- 3. WANDJI NGUELE ESTELLE — Back-end --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('estelle')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-blue-lt);color:#1A4F8A;overflow:hidden;">
        <img src="{{ asset('images/team/estelle.jpg') }}" alt="WANDJI NGUELE ESTELLE"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">WE</span>
      </div>
      <div style="font-size:12px;font-weight:700;">WANDJI NGUELE ESTELLE</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_backend') }} · GSI</div>
      <span class="pill pb" style="margin-top:6px;font-size:10px;">API</span>
    </div>

    {{-- 4. EBODE BIKORO — Front-end --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('bikoro')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-gold-lt);color:#8B5E10;overflow:hidden;">
        <img src="{{ asset('images/team/bikoro.jpg') }}" alt="EBODE BIKORO"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">EB</span>
      </div>
      <div style="font-size:12px;font-weight:700;">EBODE BIKORO</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_frontend') }} · GSI</div>
      <span class="pill" style="background:var(--ep-gold-lt);color:#8B5E10;margin-top:6px;font-size:10px;">UI</span>
    </div>

    {{-- 5. MAKUETA NGAMBA — Back-office --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('makueta')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-teal-lt);color:#085041;overflow:hidden;">
        <img src="{{ asset('images/team/makueta.jpg') }}" alt="MAKUETA NGAMBA"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">MN</span>
      </div>
      <div style="font-size:12px;font-weight:700;">MAKUETA NGAMBA</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_dev_ecole') }} · GSI</div>
      <span class="pill pa" style="margin-top:6px;font-size:10px;">{{ __('public.pill_backoffice') }}</span>
    </div>

    {{-- 6. MAFFO DJOUMESSI — QA/DevOps --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('maffo')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-red-lt);color:#9B2C2C;overflow:hidden;">
        <img src="{{ asset('images/team/maffo.jpg') }}" alt="MAFFO DJOUMESSI"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">MD</span>
      </div>
      <div style="font-size:12px;font-weight:700;">MAFFO DJOUMESSI</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">QA / DevOps · GSI</div>
      <span class="pill pr" style="margin-top:6px;font-size:10px;">{{ __('public.pill_tests') }}</span>
    </div>

    {{-- 7. Maguy Leticia — Design/Logo --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('maguy')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-purple-lt);color:#5B21B6;overflow:hidden;">
        <img src="{{ asset('images/team/maguy.jpg') }}" alt="Maguy Leticia"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">ML</span>
      </div>
      <div style="font-size:12px;font-weight:700;">Maguy Leticia</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">{{ __('public.role_design') }} · GSA</div>
      <span class="pill" style="background:var(--ep-purple-lt);color:#5B21B6;margin-top:6px;font-size:10px;">{{ __('public.pill_logo') }}</span>
    </div>

    {{-- 8. N'KO BISSO JEROME — QA/Support --}}
    <div class="team-card reveal-on-scroll" onclick="openTeamModal('jerome')" style="cursor:pointer;">
      <div class="team-av" style="background:var(--ep-red-lt);color:#9B2C2C;overflow:hidden;">
        <img src="{{ asset('images/team/jerome.jpg') }}" alt="N'KO BISSO JEROME"
             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
             onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
        <span style="display:none;width:100%;height:100%;align-items:center;justify-content:center;">NJ</span>
      </div>
      <div style="font-size:12px;font-weight:700;">N'KO BISSO JEROME</div>
      <div style="font-size:10px;color:#888;margin-top:3px;">QA / Support · GSI</div>
      <span class="pill pr" style="margin-top:6px;font-size:10px;">{{ __('public.pill_support') }}</span>
    </div>

  </div>

  {{-- ══ MODALS PROFILS ÉQUIPE ══ --}}
  @php
    $teamData = [
      'olivier' => [
        'name' => 'MEKONTSO OLIVIER STEVE',
        'role' => 'Chef de groupe du projet · Génie Informatique (GSI)',
        'color' => '#0D9E75',
        'bio' => "Chef de projet et développeur principal d'EduPay Cameroun. En charge de l'architecture technique globale (Laravel, base de données, intégrations Mobile Money), de la coordination de l'équipe et du suivi du cahier des charges CDC-EDUPAY-CM-2026-001.",
        'skills' => ['Laravel', 'Architecture logicielle', 'Gestion de projet', 'MySQL', 'Intégrations API'],
        'link' => 'https://porfolio.mekonsto.gsi2026.com',
        'linkLabel' => 'Voir le portfolio',
      ],
      'marcelle' => [
        'name' => 'Ze MELOUNI MARCELLE ANAIS',
        'role' => 'Génie des Systèmes Audiovisuels (GSA)',
        'color' => '#5B21B6',
        'bio' => "En charge du design UI/UX et de la conception des maquettes visuelles d'EduPay Cameroun. Apporte une expertise en communication visuelle et identité de marque au projet.",
        'skills' => ['Design UI/UX', 'Maquettage', 'Identité visuelle'],
      ],
      'estelle' => [
        'name' => 'WANDJI NGUELE ESTELLE',
        'role' => 'Génie Informatique (GSI)',
        'color' => '#1A4F8A',
        'bio' => "Développeuse back-end sur EduPay Cameroun, en charge de la logique métier côté serveur et des API utilisées par les modules Paiement et Établissement.",
        'skills' => ['Laravel', 'API REST', 'Base de données'],
      ],
      'bikoro' => [
        'name' => 'EBODE BIKORO',
        'role' => 'Génie Informatique (GSI)',
        'color' => '#8B5E10',
        'bio' => "Développeur front-end, en charge de l'intégration des interfaces utilisateur et de l'expérience visuelle des différents espaces de la plateforme (parent, établissement).",
        'skills' => ['HTML/CSS', 'JavaScript', 'Blade/Laravel'],
      ],
      'makueta' => [
        'name' => 'MAKUETA NGAMBA',
        'role' => 'Génie Informatique (GSI)',
        'color' => '#085041',
        'bio' => "En charge du module Back-office École — gestion des apprenants, des frais et de l'annuaire côté établissement scolaire.",
        'skills' => ['Laravel', 'Gestion de données', 'Back-office'],
      ],
      'maffo' => [
        'name' => 'MAFFO DJOUMESSI',
        'role' => 'Génie Informatique (GSI)',
        'color' => '#9B2C2C',
        'bio' => "En charge de l'assurance qualité (QA) et des aspects DevOps du projet — tests fonctionnels, suivi des anomalies et fiabilité de la plateforme.",
        'skills' => ['Tests QA', 'DevOps', 'CI/CD'],
      ],
      'maguy' => [
        'name' => 'Eyamo Maguy Leticia',
        'role' => 'Génie des Systèmes Audiovisuels (GSA)',
        'color' => '#5B21B6',
        'bio' => "En charge de la conception du logo EduPay Cameroun et des éléments graphiques de la charte visuelle du projet.",
        'skills' => ['Design graphique', 'Branding', 'Logo & identité'],
      ],
      'jerome' => [
        'name' => "N'KO BISSO JEROME",
        'role' => 'Génie Informatique (GSI)',
        'color' => '#9B2C2C',
        'bio' => "En charge du support et de l'assurance qualité, veille au bon fonctionnement des parcours utilisateurs et à la remontée des anomalies rencontrées lors des tests.",
        'skills' => ['QA', 'Support utilisateur', 'Tests fonctionnels'],
      ],
    ];
  @endphp

  @php
    $skillMap = [
      'Gestion de projet' => 'skill_gestion_projet', 'MySQL' => 'skill_mysql',
      'Intégrations API' => 'skill_api', 'Design UI/UX' => 'skill_design_uiux',
      'Maquettage' => 'skill_maquettage', 'Identité visuelle' => 'skill_identite',
      'API REST' => 'skill_api_rest', 'Base de données' => 'skill_bdd',
      'HTML/CSS' => 'skill_html_css', 'JavaScript' => 'skill_js',
      'Blade/Laravel' => 'skill_blade_laravel', 'Gestion de données' => 'skill_gestion_donnees',
      'Back-office' => 'skill_backoffice', 'Tests QA' => 'skill_tests_qa',
      'DevOps' => 'skill_devops', 'CI/CD' => 'skill_cicd',
      'Design graphique' => 'skill_design_graphique', 'Branding' => 'skill_branding',
      'Logo & identité' => 'skill_logo_identite', 'QA' => 'skill_qa',
      'Support utilisateur' => 'skill_support_utilisateur', 'Tests fonctionnels' => 'skill_tests_fonctionnels',
    ];
  @endphp

  @foreach($teamData as $key => $member)
  <div id="modal-team-{{ $key }}" class="ep-modal-overlay" onclick="if(event.target===this) closeTeamModal('{{ $key }}')"
       style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(11,37,69,.75);
              align-items:center;justify-content:center;padding:16px;">
    <div style="background:#fff;border-radius:16px;max-width:420px;width:100%;overflow:hidden;
                box-shadow:0 20px 60px rgba(0,0,0,.3);animation:teamModalIn .25s ease;">
      <div style="background:{{ $member['color'] }};padding:28px 24px;text-align:center;position:relative;">
        <button onclick="closeTeamModal('{{ $key }}')"
                style="position:absolute;top:12px;right:12px;background:rgba(255,255,255,.2);border:none;
                       width:28px;height:28px;border-radius:50%;color:#fff;font-size:16px;cursor:pointer;">×</button>
        <div style="width:88px;height:88px;border-radius:50%;background:#fff;margin:0 auto 12px;
                    overflow:hidden;border:3px solid rgba(255,255,255,.4);">
          <img src="{{ asset('images/team/'.$key.'.jpg') }}" alt="{{ $member['name'] }}"
               style="width:100%;height:100%;object-fit:cover;"
               onerror="this.style.display='none';">
        </div>
        <div style="font-size:17px;font-weight:700;color:#fff;">{{ $member['name'] }}</div>
        <div style="font-size:12px;color:rgba(255,255,255,.85);margin-top:4px;">{{ __("public.team_role_$key") }}</div>
      </div>
      <div style="padding:22px 24px;">
        <div style="font-size:13px;color:#555;line-height:1.6;margin-bottom:16px;">{{ __("public.team_bio_$key") }}</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:{{ isset($member['link']) ? '16px' : '4px' }};">
          @foreach($member['skills'] as $skill)
            <span style="font-size:11px;padding:4px 10px;border-radius:20px;background:#f3f4f6;color:#555;">{{ isset($skillMap[$skill]) ? __("public.$skillMap[$skill]") : $skill }}</span>          @endforeach
        </div>
        @if(isset($member['link']))
          <a href="{{ $member['link'] }}" target="_blank" rel="noopener"
             style="display:inline-flex;align-items:center;gap:6px;color:{{ $member['color'] }};
                    font-size:13px;font-weight:600;text-decoration:none;">
            {{ __('public.voir_portfolio') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
              <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
            </svg>
          </a>
        @endif
      </div>
    </div>
  </div>
  @endforeach

  <style>
    @keyframes teamModalIn {
      from { opacity: 0; transform: scale(.92) translateY(10px); }
      to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .team-card {
      transition: transform .25s ease, box-shadow .25s ease;
    }
    .team-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 28px rgba(13,158,117,.16);
    }
  </style>

  <script>
    function openTeamModal(key) {
      var modal = document.getElementById('modal-team-' + key);
      if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }
    }
    function closeTeamModal(key) {
      var modal = document.getElementById('modal-team-' + key);
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    }
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.ep-modal-overlay').forEach(function(m) {
          m.style.display = 'none';
        });
        document.body.style.overflow = '';
      }
    });
  </script>
</div>{{-- /.ep-body2 --}}
</div>{{-- /.lp : fin du wrapper de variables --}}

{{-- ══ FOOTER PRO v3 (identique à la landing) ══ --}}
<footer class="ep-footer lp-footer">
  <div class="lp-footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-logo" style="display:flex;align-items:center;gap:10px;">
          <span style="width:46px;height:46px;border-radius:13px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.2);">
            <img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" />
          </span>
          <span>Edu<span style="color:#5DCAA5;">Pay</span></span>
        </div>
        <div class="footer-desc">{{ __('public.footer_school_brief') }}</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;">
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">lock</span>TLS 1.3</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">verified_user</span>PCI-DSS</span>
          <span class="footer-badge"><span class="material-symbols-rounded" style="font-size:13px;color:#5DCAA5;">account_balance</span>COBAC</span>
        </div>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_produit') }}</div>
        <a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a>
        <a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a>
        <a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a>
        <a class="footer-link" href="{{ route('guide') }}">{{ __('public.footer_guide') }}</a>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div>
        <a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a>
        <a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support') }}</a>
      </div>
      <div>
        <div class="footer-col-title">{{ __('public.footer_col_contact') }}</div>
        <a class="footer-link" href="mailto:{{ config('mail.contact_address', 'contact@edupay.cm') }}" style="text-transform:none;letter-spacing:0;">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">mail</span>
          {{ config('mail.contact_address', 'contact@edupay.cm') }}
        </a>
        <a class="footer-link" href="{{ route('contact') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">forum</span>
          {{ __('public.footer_contact') }}
        </a>
        <a class="footer-link" href="{{ route('confidentialite') }}">
          <span class="material-symbols-rounded" style="font-size:15px;color:#5DCAA5;margin-right:2px;">policy</span>
          {{ __('public.footer_confidentialite') }}
        </a>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-legal">{{ __('public.footer_legal_brief') }}</div>
      @include('partials.footer-socials')
    </div>
  </div>
</footer>

@endsection
