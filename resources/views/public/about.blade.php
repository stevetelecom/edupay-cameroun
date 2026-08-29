@extends('layouts.public')

@section('title', __('public.about_title'))

@section('content')

@include('layouts._navbar_public')
<div class="hero-band">
  <div style="padding:36px 28px 28px;text-align:center;background:#0B2545">
    <div class="hero-tag" style="justify-content:center;">{{ __('public.about_hero_tag') }}</div>
    <div style="font-size:28px;font-weight:700;color:#fff;margin:10px 0;line-height:1.3;">{{ __('public.about_hero_h1_prefix') }}<em style="font-style:normal;color:#5DCAA5;">{{ __('public.about_hero_h1_em') }}</em></div>
    <div style="font-size:14px;color:rgba(255,255,255,.55);max-width:500px;margin:0 auto;line-height:1.7;">{{ __('public.about_hero_sub') }}</div>
  </div>
</div>

<div class="ep-body2">

  <div class="seclbl" style="margin-top:4px;">{{ __('public.notre_mission') }}</div>
  <div class="mission-card" style="margin-bottom:16px;">
    <div style="font-size:15px;font-weight:700;margin-bottom:8px;">{{ __('public.mission_titre') }}</div>
    <div style="font-size:13px;color:#555;line-height:1.75;">{{ __('public.mission_texte') }}</div>
  </div>

  <div class="seclbl">{{ __('public.nos_valeurs') }}</div>
  <div class="g2" style="margin-bottom:20px;">
    <div class="value-card" style="border-left-color:var(--ep-teal);"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.valeur_accessibilite_titre') }}</div><div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.valeur_accessibilite_desc') }}</div></div>
    <div class="value-card" style="border-left-color:var(--ep-gold);"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.valeur_securite_titre') }}</div><div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.valeur_securite_desc') }}</div></div>
    <div class="value-card" style="border-left-color:#185FA5;"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.valeur_ancrage_titre') }}</div><div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.valeur_ancrage_desc') }}</div></div>
    <div class="value-card" style="border-left-color:#7C3AED;"><div style="font-size:13px;font-weight:700;margin-bottom:5px;">{{ __('public.valeur_impact_titre') }}</div><div style="font-size:12px;color:#666;line-height:1.6;">{{ __('public.valeur_impact_desc') }}</div></div>
  </div>

  <div class="seclbl">{{ __('public.contexte_cameroun') }}</div>
  <div class="g4" style="margin-bottom:20px;" data-stats-container>
    <div class="kpi"><div class="kval">30 000+</div><div class="klbl">{{ __('public.ctx_etabs_30k') }}</div></div>
    <div class="kpi"><div class="kval stat-counter" data-count="6000000">0</div><div class="klbl">{{ __('public.ctx_apprenants_6m') }}</div></div>
    <div class="kpi"><div class="kval stat-counter" data-count="12000000">0</div><div class="klbl">{{ __('public.ctx_momo_12m') }}</div></div>
    <div class="kpi"><div class="kval stat-counter" data-count="45" data-suffix="%">0%</div><div class="klbl">{{ __('public.ctx_smartphone_45') }}</div></div>
  </div>

  <div class="seclbl">{{ __('public.edupay_chiffres') }}</div>
  <div class="g4" style="margin-bottom:20px;" data-stats-container>
    <div class="kpi" style="background:var(--ep-teal-lt);border:1px solid rgba(13,158,117,.15);">
      <div class="kval stat-counter" style="color:var(--ep-teal);" data-count="{{ $stats['nb_etablissements'] }}">0</div>
      <div class="klbl">{{ __('public.etabs_actifs') }}</div>
    </div>
    <div class="kpi" style="background:var(--ep-blue-lt);border:1px solid rgba(24,95,165,.15);">
      <div class="kval stat-counter" style="color:#185FA5;" data-count="{{ $stats['nb_apprenants'] }}">0</div>
      <div class="klbl">{{ __('public.apprenants_inscrits') }}</div>
    </div>
    <div class="kpi" style="background:var(--ep-gold-lt);border:1px solid rgba(232,160,32,.15);">
      <div class="kval stat-counter" style="color:#854F0B;" data-count="{{ $stats['nb_paiements'] }}">0</div>
      <div class="klbl">{{ __('public.paiements_valides') }}</div>
    </div>
    <div class="kpi" style="background:#F3F4F6;border:1px solid #E5E7EB;">
      <div class="kval stat-counter" style="color:#374151;" data-count="{{ $stats['montant_total'] }}" data-suffix=" FCFA">0 FCFA</div>
      <div class="klbl">{{ __('public.fcfa_collectes') }}</div>
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
</div>

</div>

<div class="ep-footer">
  <div class="footer-grid">
    <div><div class="footer-logo" style="display:flex;align-items:center;gap:10px;"><span style="width:44px;height:44px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.15);"><img src="{{ asset('images/logo.jpeg') }}" alt="EduPay Cameroun" style="width:100%;height:100%;object-fit:cover;" /></span>EduPay Cameroun</div><div class="footer-desc">{{ __('public.footer_school_brief') }}</div><div><span class="footer-badge">TLS 1.3</span><span class="footer-badge">PCI-DSS</span><span class="footer-badge">COBAC</span></div></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_produit') }}</div><a class="footer-link" href="{{ route('landing') }}">{{ __('public.footer_accueil') }}</a><a class="footer-link" href="{{ route('temoignages') }}">{{ __('public.footer_temoignages') }}</a><a class="footer-link" href="{{ route('tarifs') }}">{{ __('public.footer_tarifs') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_etablissements') }}</div><a class="footer-link" href="{{ route('register.ecole.step1') }}">{{ __('public.footer_inscription') }}</a><a class="footer-link" href="{{ route('support') }}">{{ __('public.footer_support') }}</a></div>
    <div><div class="footer-col-title">{{ __('public.footer_col_legal') }}</div><a class="footer-link" href="{{ route('confidentialite') }}">{{ __('public.footer_confidentialite') }}</a><a class="footer-link" href="{{ route('cgu') }}">CGU</a></div>
  </div>
  <div class="footer-bottom"><div class="footer-legal">{{ __('public.footer_legal_brief') }}</div><div class="footer-socials">
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
</div></div>
</div>

@endsection
