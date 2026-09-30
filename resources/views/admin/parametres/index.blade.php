@extends('layouts.admin')

@section('title', __('messages.params_sys'))

@push('modals')
{{-- MODAL VIDER CACHE --}}
<div id="modal-vider-cache" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ __('admin.vider_le_cache') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-vider-cache')">x</button>
    </div>
    <div class="ep-modal-body">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
        <div class="ep-ico vert" style="width:42px;height:42px;border-radius:12px;">
          <span class="material-symbols-outlined" style="font-size:22px;">cached</span>
        </div>
        <div>
          <div style="font-size:13.5px;font-weight:600;font-family:'Poppins',sans-serif;color:var(--ep-navy,#111);">{{ __('admin.confirmer_vidage') }}</div>
          <div style="font-size:12px;color:var(--ep-gris,#888);font-family:'Poppins',sans-serif;">{{ __('admin.cache_config_vues_app') }}</div>
        </div>
      </div>
      <p style="font-size:13.5px;color:var(--ep-gris,#555);margin-bottom:16px;font-family:'Poppins',sans-serif;line-height:1.7;">
        {{ __('admin.cache_vider_desc') }}
      </p>
      <form method="POST" action="{{ route('admin.parametres.cache') }}">
        @csrf
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-vider-cache')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:#0D9E75;color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ __('admin.vider_le_cache') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL MAINTENANCE --}}<div id="modal-maintenance" class="ep-modal-overlay">
  <div class="ep-modal ep-modal-sm">
    <div class="ep-modal-head">
      <h3>{{ $parametres['maintenance'] ? __('admin.desactiver_maintenance') : __('admin.activer_maintenance') }}</h3>
      <button class="ep-modal-close" onclick="epModal.close('modal-maintenance')">x</button>
    </div>
    <div class="ep-modal-body">
      @if($parametres['maintenance'])
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;margin-bottom:16px;">
<p style="font-size:12.5px;color:#166534;margin:0;font-family:'Poppins',sans-serif;">{{ __('admin.maintenance_reactive_desc') }}</p>
      </div>
      @else
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:16px;">
<p style="font-size:12.5px;color:#b91c1c;margin:0;font-family:'Poppins',sans-serif;">{{ __('admin.maintenance_desactive_desc') }}</p>
      </div>
      @endif
      <p style="font-size:13.5px;color:var(--ep-gris,#555);margin-bottom:16px;font-family:'Poppins',sans-serif;">{{ __('admin.confirmez_cette_action') }}</p>
      <form id="form-maintenance" method="POST" action="{{ route('admin.parametres.update') }}">
        @csrf @method('POST')
        <input type="hidden" name="taux_aangaraa" value="{{ $parametres['taux_aangaraa'] }}">
        <input type="hidden" name="marge_edupay" value="{{ $parametres['marge_edupay'] }}">
        <input type="hidden" name="timeout_paiement" value="{{ $parametres['timeout_paiement'] }}">
        <input type="hidden" name="max_tranches" value="{{ $parametres['max_tranches'] }}">
        <input type="hidden" name="langue_defaut" value="{{ $parametres['langue_defaut'] }}">
        <input type="hidden" name="sms_actif" value="{{ $parametres['sms_actif'] ? '1' : '' }}">
        <input type="hidden" name="mtn_actif" value="{{ $parametres['mtn_actif'] ? '1' : '' }}">
        <input type="hidden" name="orange_actif" value="{{ $parametres['orange_actif'] ? '1' : '' }}">
        <input type="hidden" name="maintenance" id="maintenance-val" value="{{ $parametres['maintenance'] ? '' : '1' }}">
        <div style="display:flex;justify-content:flex-end;gap:10px;">
          <button type="button" onclick="epModal.close('modal-maintenance')"
                  style="padding:8px 16px;font-size:13px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            {{ __('messages.annuler') }}
          </button>
          <button type="submit"
                  style="padding:8px 20px;font-size:13px;font-weight:600;background:{{ $parametres['maintenance'] ? '#16a34a' : '#dc2626' }};color:#fff;border:none;border-radius:8px;cursor:pointer;">
            {{ $parametres['maintenance'] ? __('admin.confirmer') . ' — ' . __('admin.desactiver') : __('admin.confirmer') . ' — ' . __('admin.activer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endpush

@section('content')

{{-- ── En-tête de page style tableau de bord (pastille dorée + Poppins) ── --}}
<div class="ep-entete" style="justify-content:space-between;margin-bottom:18px;">
  <div style="display:flex;align-items:center;gap:10px;">
    <span class="material-symbols-outlined">tune</span>
    <div>
      <h3>{{ __('messages.params_sys') }}</h3>
      <div class="ep-sous-titre" style="margin-top:2px;">{{ __('admin.configuration_globale') }}</div>
    </div>
  </div>
  <button onclick="epModal.open('modal-vider-cache')" class="btn-o" style="width:auto;padding:9px 18px;font-size:13px;display:inline-flex;align-items:center;gap:7px;">
    <span class="material-symbols-outlined" style="font-size:17px;">cached</span>
    {{ __('admin.vider_le_cache') }}
  </button>
</div>

{{-- ── Infos système — cartes pastille style dashboard ──
     (Version PHP et adresse serveur volontairement masquées :
     informations sensibles inutiles dans une interface d'exploitation.) --}}
<div class="grid grid-cols-3 gap-4 mb-6">
  <div class="epcard" style="display:flex;align-items:center;gap:13px;padding:16px 18px !important;">
    <div class="ep-ico navy" style="width:44px;height:44px;border-radius:12px;">
      <span class="material-symbols-outlined" style="font-size:23px;">layers</span>
    </div>
    <div>
      <div class="ep-sous-titre" style="text-transform:uppercase;letter-spacing:.05em;font-size:10.5px;">{{ __('admin.environnement') }}</div>
      <div style="font-size:15.5px;font-weight:700;font-family:'Poppins',sans-serif;color:var(--ep-navy,#111);">{{ strtoupper($stats['env']) }} · Laravel {{ $stats['version_laravel'] }}</div>
    </div>
  </div>
  <div class="epcard" style="display:flex;align-items:center;gap:13px;padding:16px 18px !important;">
    <div class="ep-ico bleu" style="width:44px;height:44px;border-radius:12px;">
      <span class="material-symbols-outlined" style="font-size:23px;">database</span>
    </div>
    <div>
      <div class="ep-sous-titre" style="text-transform:uppercase;letter-spacing:.05em;font-size:10.5px;">{{ __('admin.php_base_donnees') }}</div>
      <div style="font-size:15.5px;font-weight:700;font-family:'Poppins',sans-serif;color:var(--ep-navy,#111);">DB: {{ strtoupper($stats['db_driver']) }} · Cache: {{ strtoupper($stats['cache_driver']) }}</div>
    </div>
  </div>
  <div class="epcard" style="display:flex;align-items:center;gap:13px;padding:16px 18px !important;">
    <div class="ep-ico or" style="width:44px;height:44px;border-radius:12px;">
      <span class="material-symbols-outlined" style="font-size:23px;">sync_alt</span>
    </div>
    <div style="min-width:0;">
      <div class="ep-sous-titre" style="text-transform:uppercase;letter-spacing:.05em;font-size:10.5px;">Queue / AangaraaPay</div>
      <div style="font-size:15.5px;font-weight:700;font-family:'Poppins',sans-serif;color:var(--ep-navy,#111);">Queue: {{ strtoupper($stats['queue_driver']) }}</div>
      <div style="font-size:11px;color:var(--ep-gris,#888);font-family:monospace;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit($parametres['aangaraa_api_url'], 35) }}</div>
    </div>
  </div>
</div>

{{-- ── Formulaire paramètres ── --}}
<form method="POST" action="{{ route('admin.parametres.update') }}">
  @csrf
  {{-- Conserver l'etat maintenance lors de l'enregistrement general --}}
  <input type="hidden" name="maintenance" value="{{ $parametres['maintenance'] ? '1' : '0' }}">

  <div class="grid grid-cols-2 gap-5">

    {{-- ── Colonne gauche ── --}}
    <div class="space-y-4">

      {{-- Taux des frais de service : cout prestataire + marge EduPay --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:6px;">
          <span class="material-symbols-outlined">percent</span>
          <h3>{{ __('admin.taux_commission_lbl') }}</h3>
        </div>
        <p class="ep-sous-titre" style="margin:0 0 16px;line-height:1.7;">
          Le payeur regle les frais de scolarite plus ces frais de service. Ce qui est reellement
          reverse a l'etablissement est le <strong>net</strong> : les frais de scolarite moins la marge
          EduPay. Le cout AangaraaPay est preleve par le prestataire sur ce reversement.
        </p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
          <div>
            <label for="taux_aangaraa" class="lbl" style="font-size:12.5px;display:block;margin-bottom:6px;">
              Cout AangaraaPay
            </label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="number" name="taux_aangaraa" id="taux_aangaraa"
                     value="{{ $parametres['taux_aangaraa'] }}"
                     step="0.001" min="0" max="0.5" required
                     style="flex:1;padding:10px 12px;font-size:15px;font-weight:700;font-family:'Poppins',sans-serif;border:2px solid #E8A020;border-radius:10px;outline:none;text-align:center;" />
              <div id="taux-aangaraa-display" style="font-size:18px;font-weight:800;color:#E8A020;min-width:52px;text-align:right;">
                {{ number_format($parametres['taux_aangaraa'] * 100, 1, ',', '') }}%
              </div>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;font-family:'Poppins',sans-serif;">Preleve par le prestataire sur chaque reversement. Attention : le cout porte sur le NET vire, soit un peu moins que le montant de la transaction.</p>
          </div>

          <div>
            <label for="marge_edupay" class="lbl" style="font-size:12.5px;display:block;margin-bottom:6px;">
              Marge EduPay
            </label>
            <div style="display:flex;align-items:center;gap:8px;">
              <input type="number" name="marge_edupay" id="marge_edupay"
                     value="{{ $parametres['marge_edupay'] }}"
                     step="0.001" min="0" max="0.1" required
                     style="flex:1;padding:10px 12px;font-size:15px;font-weight:700;font-family:'Poppins',sans-serif;border:2px solid #E8A020;border-radius:10px;outline:none;text-align:center;" />
              <div id="marge-edupay-display" style="font-size:18px;font-weight:800;color:#E8A020;min-width:52px;text-align:right;">
                {{ number_format($parametres['marge_edupay'] * 100, 1, ',', '') }}%
              </div>
            </div>
            <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;font-family:'Poppins',sans-serif;">Benefice conserve par EduPay. Elle est deduite du montant de la transaction : l'etablissement est reverse du net (frais de scolarite - marge).</p>
          </div>
        </div>

        <div style="background:#FEF3DC;border-left:3px solid #E8A020;border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;gap:10px;">
          <div style="font-size:12px;color:#854F0B;font-family:'Poppins',sans-serif;font-weight:600;">{{ __('admin.taux_global_ex') }}</div>
          <div style="font-size:21px;font-weight:800;color:#E8A020;font-family:'Poppins',sans-serif;" id="taux-display">
            {{ number_format($parametres['taux_commission'] * 100, 2, ',', '') }}%
          </div>
        </div>
        <div style="background:#FEF3DC;border-left:3px solid #E8A020;border-radius:8px;padding:8px 14px;margin-top:8px;">
          <div style="font-size:12px;color:#854F0B;font-family:'Poppins',sans-serif;">{{ __('admin.profil_std_cobac') }}</div>
        </div>
      </div>

      {{-- ── Paiement ── --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:16px;">
          <span class="material-symbols-outlined">credit_card</span>
          <h3>{{ __('admin.paiement_mobile_money') }}</h3>
        </div>
        <div style="margin-bottom:14px;">
          <label class="lbl" style="font-size:12.5px;display:block;margin-bottom:6px;">
            {{ __('admin.timeout_paiement') }}
          </label>
          <input type="number" name="timeout_paiement"
                 value="{{ $parametres['timeout_paiement'] }}"
                 min="30" max="600" required class="inp"
                 style="width:100%;font-size:14px;box-sizing:border-box;margin-bottom:0;" />
          <div style="font-size:11.5px;color:#aaa;margin-top:4px;font-family:'Poppins',sans-serif;">{{ __('admin.delai_avant_echec') }}</div>
        </div>
        <div>
          <label class="lbl" style="font-size:12.5px;display:block;margin-bottom:6px;">
            {{ __('admin.nb_max_tranches') }}
          </label>
          <input type="number" name="max_tranches"
                 value="{{ $parametres['max_tranches'] }}"
                 min="1" max="12" required class="inp"
                 style="width:100%;font-size:14px;box-sizing:border-box;margin-bottom:0;" />
          <div style="font-size:11.5px;color:#aaa;margin-top:4px;font-family:'Poppins',sans-serif;">{{ __('admin.max_tranches_par_frais') }}</div>
        </div>
      </div>

      {{-- ── Modes de paiement actifs (S07) ── --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:16px;">
          <span class="material-symbols-outlined">point_of_sale</span>
          <h3>{{ __('admin.modes_paiement_actifs') }}</h3>
        </div>

        {{-- Toggle MTN --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f5f5f5;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="ep-ico or" style="width:40px;height:40px;border-radius:11px;">
              <span class="material-symbols-outlined" style="font-size:21px;">smartphone</span>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--ep-navy,#111);font-family:'Poppins',sans-serif;">MTN Mobile Money</div>
              <div style="font-size:11.5px;color:var(--ep-gris,#888);margin-top:2px;font-family:'Poppins',sans-serif;">{{ __('admin.operateur_mtn') }}</div>
            </div>
          </div>
          <label class="ep-toggle">
            <input type="checkbox" name="mtn_actif" value="1" {{ $parametres['mtn_actif'] ? 'checked' : '' }}>
            <span class="ep-toggle-track"><span class="ep-toggle-thumb"></span></span>
          </label>
        </div>

        {{-- Toggle Orange --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="ep-ico rouge" style="width:40px;height:40px;border-radius:11px;">
              <span class="material-symbols-outlined" style="font-size:21px;">smartphone</span>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--ep-navy,#111);font-family:'Poppins',sans-serif;">Orange Money</div>
              <div style="font-size:11.5px;color:var(--ep-gris,#888);margin-top:2px;font-family:'Poppins',sans-serif;">Operateur AangaraaPay — Orange_Cameroon</div>
            </div>
          </div>
          <label class="ep-toggle">
            <input type="checkbox" name="orange_actif" value="1" {{ $parametres['orange_actif'] ? 'checked' : '' }}>
            <span class="ep-toggle-track"><span class="ep-toggle-thumb"></span></span>
          </label>
        </div>

        @error('mtn_actif')
          <div style="font-size:11px;color:#dc2626;margin-top:8px;">{{ $message }}</div>
        @enderror
      </div>
    </div>

    {{-- ── Colonne droite ── --}}
    <div class="space-y-4">

      {{-- ── Toggles système ── --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:16px;">
          <span class="material-symbols-outlined">settings_suggest</span>
          <h3>{{ __('admin.options_systeme') }}</h3>
        </div>

        {{-- Toggle SMS --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f5f5f5;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="ep-ico vert" style="width:40px;height:40px;border-radius:11px;">
              <span class="material-symbols-outlined" style="font-size:21px;">sms</span>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--ep-navy,#111);font-family:'Poppins',sans-serif;">{{ __('admin.notifications_sms') }}</div>
              <div style="font-size:11.5px;color:var(--ep-gris,#888);margin-top:2px;font-family:'Poppins',sans-serif;">{{ __('admin.africas_talking') }}</div>
            </div>
          </div>
          <label class="ep-toggle">
            <input type="checkbox" name="sms_actif" value="1" {{ $parametres['sms_actif'] ? 'checked' : '' }}>
            <span class="ep-toggle-track"><span class="ep-toggle-thumb"></span></span>
          </label>
        </div>
        {{-- Mode maintenance --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="ep-ico {{ $parametres['maintenance'] ? 'rouge' : 'vert' }}" style="width:40px;height:40px;border-radius:11px;">
              <span class="material-symbols-outlined" style="font-size:21px;">engineering</span>
            </div>
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--ep-navy,#111);font-family:'Poppins',sans-serif;">{{ __('admin.mode_maintenance') }}</div>
              <div style="font-size:11.5px;margin-top:2px;font-family:'Poppins',sans-serif;">
                @if($parametres['maintenance'])
                  <span style="color:#dc2626;font-weight:700;">Actif</span> <span style="color:var(--ep-gris,#888);">— plateforme inaccessible</span>
                @else
                  <span style="color:#16a34a;font-weight:700;">{{ __('admin.inactif') }}</span> <span style="color:var(--ep-gris,#888);">— plateforme accessible</span>
                @endif
              </div>
            </div>
          </div>
          <button type="button" onclick="epModal.open('modal-maintenance')"
                  class="btn-p" style="width:auto;padding:8px 16px;font-size:12.5px;{{ $parametres['maintenance'] ? 'background:linear-gradient(135deg,#16a34a,#15803d) !important;box-shadow:0 4px 14px rgba(22,163,74,.25) !important;' : 'background:linear-gradient(135deg,#E05555,#D94040) !important;box-shadow:0 4px 14px rgba(217,64,64,.25) !important;' }}">
            {{ $parametres['maintenance'] ? __('admin.desactiver') : __('admin.activer') }}
          </button>
        </div>
      </div>

      {{-- ── Langue de la plateforme (S07 / F15 / E13) ── --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:16px;">
          <span class="material-symbols-outlined">language</span>
          <h3>{{ __('admin.langue_plateforme') }}</h3>
        </div>
        <label class="lbl" style="font-size:12.5px;display:block;margin-bottom:6px;">
          {{ __('admin.langue_par_defaut') }}
        </label>
        <select name="langue_defaut" class="select" style="width:100%;font-size:14px;box-sizing:border-box;margin-bottom:0;">
          <option value="fr" {{ $parametres['langue_defaut'] === 'fr' ? 'selected' : '' }}>{{ __('admin.francais') }}</option>
          <option value="en" {{ $parametres['langue_defaut'] === 'en' ? 'selected' : '' }}>English</option>
        </select>
        <div style="font-size:11.5px;color:#aaa;margin-top:6px;font-family:'Poppins',sans-serif;">{{ __('admin.langue_appliquee_note') }}</div>
      </div>

      {{-- ── Bouton sauvegarder ── --}}
      <div class="epcard" style="padding:20px !important;">
        <div class="ep-entete" style="margin-bottom:12px;">
          <span class="material-symbols-outlined">save</span>
          <h3>{{ __('admin.sauvegarder_modifications') }}</h3>
        </div>
        <p style="font-size:12.5px;color:var(--ep-gris,#888);margin-bottom:16px;font-family:'Poppins',sans-serif;line-height:1.7;">
          {{ __('admin.parametres_enregistres_note') }}
        </p>
        <button type="submit" class="btn-p" style="width:100%;padding:12px;font-size:14px;">
          {{ __('admin.enregistrer_parametres') }}
        </button>
      </div>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
// Le total affiché est la somme des deux taux : le modifier de l'un
// recalcule l'autre, pour que le super admin voie toujours le total réel
// prélevé au payeur.
function majTotal() {
    const a = parseFloat(document.getElementById('taux_aangaraa')?.value || 0);
    const m = parseFloat(document.getElementById('marge_edupay')?.value || 0);
    const el = document.getElementById('taux-display');
    if (el) el.textContent = ((a + m) * 100).toFixed(2).replace('.', ',') + '%';
}

for (const [id, aff] of [['taux_aangaraa', 'taux-aangaraa-display'], ['marge_edupay', 'marge-edupay-display']]) {
    const champ = document.getElementById(id);
    if (!champ) continue;
    champ.addEventListener('input', function() {
        const pct = (parseFloat(this.value || 0) * 100).toFixed(1).replace('.', ',');
        const cible = document.getElementById(aff);
        if (cible) cible.textContent = pct + '%';
        majTotal();
    });
}
</script>
@endpush
