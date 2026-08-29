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
        <div style="width:40px;height:40px;background:#E0F5EE;border-radius:50%;display:flex;align-items:center;justify-content:center;shrink:0;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        </div>
        <div>
          <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.confirmer_vidage') }}</div>
          <div style="font-size:12px;color:#888;">{{ __('admin.cache_config_vues_app') }}</div>
        </div>
      </div>
      <p style="font-size:13px;color:#555;margin-bottom:16px;">
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
<p style="font-size:12px;color:#166534;margin:0;">{{ __('admin.maintenance_reactive_desc') }}</p>
      </div>
      @else
      <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:16px;">
<p style="font-size:12px;color:#b91c1c;margin:0;">{{ __('admin.maintenance_desactive_desc') }}</p>
      </div>
      @endif
      <p style="font-size:13px;color:#555;margin-bottom:16px;">{{ __('admin.confirmez_cette_action') }}</p>
      <form id="form-maintenance" method="POST" action="{{ route('admin.parametres.update') }}">
        @csrf @method('POST')
        <input type="hidden" name="taux_commission" value="{{ $parametres['taux_commission'] }}">
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

<div class="flex items-center justify-between mb-5">
  <div>
    <h1 class="text-xl font-bold text-gray-900">{{ __('messages.params_sys') }}</h1>
    <p class="text-sm text-gray-500 mt-0.5">{{ __('admin.configuration_globale') }}</p>
  </div>
  <button onclick="epModal.open('modal-vider-cache')"
          style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;border:1px solid #ddd;border-radius:8px;font-size:13px;font-weight:500;color:#444;cursor:pointer;">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
    {{ __('admin.vider_le_cache') }}
  </button>
</div>

{{-- Infos systeme --}}
<div class="grid grid-cols-3 gap-4 mb-6">
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div style="font-size:11px;color:#999;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;">{{ __('admin.environnement') }}</div>
    <div style="font-size:14px;font-weight:700;color:#111;">{{ strtoupper($stats['env']) }}</div>
    <div style="font-size:11px;color:#888;margin-top:2px;">Laravel {{ $stats['version_laravel'] }}</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div style="font-size:11px;color:#999;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;">{{ __('admin.php_base_donnees') }}</div>
    <div style="font-size:14px;font-weight:700;color:#111;">PHP {{ $stats['version_php'] }}</div>
    <div style="font-size:11px;color:#888;margin-top:2px;">DB: {{ strtoupper($stats['db_driver']) }} · Cache: {{ strtoupper($stats['cache_driver']) }}</div>
  </div>
  <div class="bg-white border border-gray-200 rounded-xl p-4">
    <div style="font-size:11px;color:#999;margin-bottom:6px;text-transform:uppercase;letter-spacing:.05em;">Queue / AangaraaPay</div>
    <div style="font-size:14px;font-weight:700;color:#111;">Queue: {{ strtoupper($stats['queue_driver']) }}</div>
    <div style="font-size:11px;color:#888;margin-top:2px;font-family:monospace;">{{ Str::limit($parametres['aangaraa_api_url'], 35) }}</div>
  </div>
</div>

{{-- Formulaire parametres --}}
<form method="POST" action="{{ route('admin.parametres.update') }}">
  @csrf
  {{-- Conserver l'etat maintenance lors de l'enregistrement general --}}
  <input type="hidden" name="maintenance" value="{{ $parametres['maintenance'] ? '1' : '0' }}">

  <div class="grid grid-cols-2 gap-5">

    {{-- Colonne gauche --}}
    <div class="space-y-4">

      {{-- Taux commission --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#E8A020" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
          {{ __('admin.taux_commission_lbl') }}
        </h2>        <div style="margin-bottom:12px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
            {{ __('admin.taux_global_ex') }}
          </label>
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="number" name="taux_commission" id="taux_commission"
                   value="{{ $parametres['taux_commission'] }}"
                   step="0.001" min="0" max="0.1" required
                   style="flex:1;padding:10px 12px;font-size:15px;font-weight:700;border:2px solid #E8A020;border-radius:8px;outline:none;text-align:center;" />
            <div style="text-align:center;min-width:60px;">
              <div style="font-size:20px;font-weight:800;color:#E8A020;" id="taux-display">
                {{ number_format($parametres['taux_commission'] * 100, 1, ',', '') }}%
              </div>
              <div style="font-size:10px;color:#aaa;">{{ __('messages.par_transaction') }}</div>
            </div>
          </div>
        </div>
        <div style="background:#FEF3DC;border-left:3px solid #E8A020;border-radius:6px;padding:8px 12px;">
          <div style="font-size:11px;color:#854F0B;">{{ __('admin.profil_std_cobac') }}</div>
        </div>
      </div>

      {{-- Paiement --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          {{ __('admin.paiement_mobile_money') }}
        </h2>
        <div style="margin-bottom:14px;">
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
            {{ __('admin.timeout_paiement') }}
          </label>
          <input type="number" name="timeout_paiement"
                 value="{{ $parametres['timeout_paiement'] }}"
                 min="30" max="600" required
                 style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #ddd;border-radius:8px;outline:none;box-sizing:border-box;" />
          <div style="font-size:11px;color:#aaa;margin-top:4px;">{{ __('admin.delai_avant_echec') }}</div>
        </div>
        <div>
          <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
            {{ __('admin.nb_max_tranches') }}
          </label>
          <input type="number" name="max_tranches"
                 value="{{ $parametres['max_tranches'] }}"
                 min="1" max="12" required
                 style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #ddd;border-radius:8px;outline:none;box-sizing:border-box;" />
          <div style="font-size:11px;color:#aaa;margin-top:4px;">{{ __('admin.max_tranches_par_frais') }}</div>
        </div>
      </div>

      {{-- Modes de paiement actifs (S07) --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D94040" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
          {{ __('admin.modes_paiement_actifs') }}
        </h2>

        {{-- Toggle MTN --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f5f5f5;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#FFCC00;display:inline-block;"></span>
            <div>
              <div style="font-size:13px;font-weight:600;color:#111;">MTN Mobile Money</div>
              <div style="font-size:11px;color:#888;margin-top:2px;">{{ __('admin.operateur_mtn') }}</div>
            </div>
          </div>
          <label class="ep-toggle">
            <input type="checkbox" name="mtn_actif" value="1" {{ $parametres['mtn_actif'] ? 'checked' : '' }}>
            <span class="ep-toggle-track"><span class="ep-toggle-thumb"></span></span>
          </label>
        </div>

        {{-- Toggle Orange --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;">
          <div style="display:flex;align-items:center;gap:10px;">
            <span style="width:8px;height:8px;border-radius:50%;background:#FF6600;display:inline-block;"></span>
            <div>
              <div style="font-size:13px;font-weight:600;color:#111;">Orange Money</div>
              <div style="font-size:11px;color:#888;margin-top:2px;">Operateur AangaraaPay — Orange_Cameroon</div>
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

    {{-- Colonne droite --}}
    <div class="space-y-4">

      {{-- Toggles --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
          {{ __('admin.options_systeme') }}
        </h2>

        {{-- Toggle SMS --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f5f5f5;">
          <div>
            <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.notifications_sms') }}</div>
            <div style="font-size:11px;color:#888;margin-top:2px;">{{ __('admin.africas_talking') }}</div>
          </div>
          <label class="ep-toggle">
            <input type="checkbox" name="sms_actif" value="1" {{ $parametres['sms_actif'] ? 'checked' : '' }}>
            <span class="ep-toggle-track"><span class="ep-toggle-thumb"></span></span>
          </label>
        </div>
        {{-- Mode maintenance --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;">
          <div>
            <div style="font-size:13px;font-weight:600;color:#111;">{{ __('admin.mode_maintenance') }}</div>
            <div style="font-size:11px;margin-top:2px;">
              @if($parametres['maintenance'])
                <span style="color:#dc2626;font-weight:600;">Actif</span> — plateforme inaccessible aux utilisateurs
              @else
                <span style="color:#16a34a;font-weight:600;">{{ __('admin.inactif') }}</span> — plateforme accessible normalement
              @endif
            </div>
          </div>
          <button type="button" onclick="epModal.open('modal-maintenance')"
                  style="padding:6px 16px;font-size:12px;font-weight:600;border:none;border-radius:8px;cursor:pointer;
                         background:{{ $parametres['maintenance'] ? '#16a34a' : '#dc2626' }};color:#fff;">
            {{ $parametres['maintenance'] ? __('admin.desactiver') : __('admin.activer') }}
          </button>
        </div>
        </div>
      </div>

      {{-- Langue de la plateforme (S07 / F15 / E13) --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#185FA5" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
          {{ __('admin.langue_plateforme') }}
        </h2>
        <label style="font-size:12px;font-weight:500;color:#555;display:block;margin-bottom:6px;">
          {{ __('admin.langue_par_defaut') }}
        </label>
        <select name="langue_defaut"
                style="width:100%;padding:10px 12px;font-size:14px;border:1px solid #ddd;border-radius:8px;outline:none;box-sizing:border-box;background:#fff;">
          <option value="fr" {{ $parametres['langue_defaut'] === 'fr' ? 'selected' : '' }}>{{ __('admin.francais') }}</option>
          <option value="en" {{ $parametres['langue_defaut'] === 'en' ? 'selected' : '' }}>English</option>
        </select>
<div style="font-size:11px;color:#aaa;margin-top:6px;">{{ __('admin.langue_appliquee_note') }}</div>
      </div>

      {{-- Bouton sauvegarder --}}
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 style="font-size:14px;font-weight:700;color:#111;margin-bottom:12px;">{{ __('admin.sauvegarder_modifications') }}</h2>
        <p style="font-size:12px;color:#888;margin-bottom:16px;">
          {{ __('admin.parametres_enregistres_note') }}
        </p>
        <button type="submit"
                style="width:100%;padding:12px;font-size:14px;font-weight:700;background:#0B2545;color:#fff;border:none;border-radius:10px;cursor:pointer;transition:background .15s;"
                onmouseover="this.style.background='#0D9E75'" onmouseout="this.style.background='#0B2545'">
          {{ __('admin.enregistrer_parametres') }}
        </button>
      </div>
    </div>
  </div>
</form>

@endsection

@push('scripts')
<script>
document.getElementById('taux_commission').addEventListener('input', function() {
    const pct = (parseFloat(this.value || 0) * 100).toFixed(1).replace('.', ',');
    document.getElementById('taux-display').textContent = pct + '%';
});
</script>
@endpush
