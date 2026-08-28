@extends('layouts.etablissement')

@section('title', __('messages.parametres'))

@section('content')

    <div style="font-size:17px;font-weight:700;margin-bottom:4px;">{{ __('etablissement.parametres_etab') }}</div>
    <div style="font-size:12px;color:#888;margin-bottom:18px;">{{ __('etablissement.params_sous_titre') }}</div>

    @php($etab = $etablissement ?? Auth::user()->etablissement)

    <div class="g2">

        {{-- ── Informations établissement ── --}}
        <div class="epcard">
            <div style="font-size:14px;font-weight:700;margin-bottom:14px;">{{ __('etablissement.infos_generales') }}</div>

            <form method="POST" action="{{ route('etablissement.parametres.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="lbl">{{ __('etablissement.logo_etab') }}</div>
                <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                    <div id="logo-preview" style="width:56px;height:56px;border-radius:var(--radius-md);background:var(--ep-teal-lt);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;">
                        @if($etab->logo)
                            <img src="{{ Storage::url($etab->logo) }}" style="width:100%;height:100%;object-fit:cover;" />
                        @else
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#0D9E75" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg>
                        @endif
                    </div>
                    <div style="flex:1;">
                        <label for="logo-input" style="display:inline-block;background:#fff;border:1px solid #ddd;padding:9px 16px;border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer;color:#333;">{{ __('etablissement.changer_logo') }}</label>
                        <input type="file" name="logo" id="logo-input" accept="image/png,image/jpeg,image/svg+xml" style="display:none;" onchange="previewLogo(this)" />
                        <div style="font-size:11px;color:#888;margin-top:6px;">{{ __('etablissement.logo_hint') }}</div>
                    </div>
                </div>

                <div class="lbl">{{ __('etablissement.nom_etab') }}</div>
                <input type="text" name="nom" value="{{ old('nom', $etab->nom ?? '') }}" class="inp" required>
                @error('nom')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

                <div class="inp-row">
                    <div>
                        <div class="lbl">{{ __('etablissement.type_etab') }}</div>
                        <select name="type" class="select">
                            <option value="maternelle"    @selected(old('type', $etab->type ?? '') === 'maternelle')>{{ __('etablissement.type_maternelle') }}</option>
                            <option value="primaire"      @selected(old('type', $etab->type ?? '') === 'primaire')>{{ __('etablissement.type_primaire') }}</option>
                            <option value="secondaire"    @selected(old('type', $etab->type ?? '') === 'secondaire')>{{ __('etablissement.type_secondaire') }}</option>
                            <option value="universitaire" @selected(old('type', $etab->type ?? '') === 'universitaire')>{{ __('etablissement.type_universitaire') }}</option>
                            <option value="formation"     @selected(old('type', $etab->type ?? '') === 'formation')>{{ __('etablissement.type_formation') }}</option>
                        </select>
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.statut_juridique') }}</div>
                        <input type="text" name="statut_juridique" value="{{ old('statut_juridique', $etab->statut_juridique ?? '') }}" class="inp" placeholder="{{ __('etablissement.statut_juridique_ph') }}">
                    </div>
                </div>

                <div class="inp-row">
                    <div>
                        <div class="lbl">{{ __('etablissement.numero_agrement') }}</div>
                        <input type="text" name="numero_agrement" value="{{ old('numero_agrement', $etab->numero_agrement ?? '') }}" class="inp" placeholder="{{ __('etablissement.numero_agrement_ph') }}">
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.nb_eleves') }}</div>
                        <select name="nb_eleves" class="inp" style="padding:10px 12px;">
                            <option value="">{{ __('etablissement.selectionner') }}</option>
                            @foreach([
                                'moins_100'  => __('etablissement.nb_eleves_moins_100'),
                                '100_300'    => __('etablissement.nb_eleves_100_300'),
                                '300_500'    => __('etablissement.nb_eleves_300_500'),
                                '500_1000'   => __('etablissement.nb_eleves_500_1000'),
                                'plus_1000'  => __('etablissement.nb_eleves_plus_1000'),
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ old('nb_eleves', $etab->nb_eleves ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="font-size:13px;font-weight:700;color:#0B2545;margin:16px 0 10px;padding-top:12px;border-top:1px solid #f0f0f0;">{{ __('etablissement.contact_localisation') }}</div>

                <div class="inp-row">
                    <div>
                        <div class="lbl">{{ __('etablissement.telephone') }}</div>
                        <input type="text" name="telephone" value="{{ old('telephone', $etab->telephone ?? '') }}" class="inp">
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.email') }}</div>
                        <input type="email" name="email" value="{{ old('email', $etab->email ?? '') }}" class="inp">
                    </div>
                </div>

                <div class="inp-row">
                    <div>
                        <div class="lbl">{{ __('etablissement.ville') }}</div>
                        <input type="text" name="ville" value="{{ old('ville', $etab->ville ?? '') }}" class="inp">
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.quartier') }}</div>
                        <input type="text" name="quartier" value="{{ old('quartier', $etab->quartier ?? '') }}" class="inp">
                    </div>
                </div>

                <div class="inp-row">
                    <div>
                        <div class="lbl">{{ __('etablissement.region') }}</div>
                        <select name="region" class="select">
                            @foreach(['Centre','Littoral','Ouest','Nord-Ouest','Sud-Ouest','Est','Adamaoua','Nord','Extrême-Nord','Sud'] as $reg)
                            <option value="{{ $reg }}" @selected(old('region', $etab->region ?? '') === $reg)>{{ $reg }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <div class="lbl">{{ __('etablissement.boite_postale') }}</div>
                        <input type="text" name="boite_postale" value="{{ old('boite_postale', $etab->boite_postale ?? '') }}" class="inp" placeholder="{{ __('etablissement.boite_postale_ph') }}">
                    </div>
                </div>

                <div class="lbl">{{ __('etablissement.site_web') }}</div>
                <input type="url" name="site_web" value="{{ old('site_web', $etab->site_web ?? '') }}" class="inp" placeholder="{{ __('etablissement.site_web_ph') }}">
                @error('site_web')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

                <div class="lbl">{{ __('etablissement.description') }}</div>
                <textarea name="description" class="inp" rows="3" style="resize:vertical;" placeholder="{{ __('etablissement.description_ph') }}">{{ old('description', $etab->description ?? '') }}</textarea>

                <div style="font-size:13px;font-weight:700;color:#0B2545;margin:16px 0 10px;padding-top:12px;border-top:1px solid #f0f0f0;">{{ __('etablissement.config_paiement') }}</div>

                <div class="lbl">{{ __('etablissement.momo_principal') }}</div>
                <select name="mobile_money_principal" class="select" required>
                    <option value="mtn"      @selected(($etab->mobile_money_principal ?? '') === 'mtn')>{{ __('etablissement.mt_mtn') }}</option>
                    <option value="orange"   @selected(($etab->mobile_money_principal ?? '') === 'orange')>{{ __('etablissement.mt_orange') }}</option>
                </select>

                <div class="lbl">{{ __('etablissement.numero_momo') }}</div>
                <input type="text" name="numero_momo_reversement"
                       value="{{ old('numero_momo_reversement', $etab->numero_momo_reversement ?? '') }}"
                       class="inp" placeholder="{{ __('etablissement.numero_momo_ph') }}" required>
                <div style="font-size:11px;color:#888;margin-top:-8px;margin-bottom:8px;">
                    {{ __('etablissement.numero_momo_hint') }}
                </div>
                @error('numero_momo_reversement')<div style="color:var(--ep-red);font-size:11px;margin-top:-8px;margin-bottom:8px;">{{ $message }}</div>@enderror

                <div style="font-size:13px;font-weight:700;color:#0B2545;margin:16px 0 10px;padding-top:12px;border-top:1px solid #f0f0f0;">{{ __('etablissement.doc_agrement') }}</div>

                @if($etab->document_agrement)
                <div style="background:#FEF3DC;border:1.5px solid #E8A020;border-radius:10px;padding:14px 16px;margin-bottom:14px;">
                    <div style="font-size:12px;font-weight:700;color:#92400E;margin-bottom:8px;">{{ __('etablissement.doc_agrement_actuel') }}</div>
                    <a href="{{ Storage::url($etab->document_agrement) }}" target="_blank"
                       style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:#92400E;text-decoration:none;border:1px solid #E8A020;padding:5px 12px;border-radius:20px;background:#fff;">
                        {{ __('etablissement.ouvrir_doc') }}
                    </a>
                </div>
                @endif

                <div>
                    <label for="doc-agrement-input"
                           style="display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid #ddd;padding:8px 16px;border-radius:var(--radius-md);font-size:12px;font-weight:600;cursor:pointer;color:#333;">
                        <span class="material-symbols-outlined" style="font-size:16px;">upload_file</span>
                        {{ $etab->document_agrement ? __('etablissement.remplacer_doc') : __('etablissement.televerser_doc') }}
                    </label>
                    <input type="file" name="document_agrement" id="doc-agrement-input" accept=".pdf,.jpg,.jpeg,.png" style="display:none;"
                           onchange="afficherNomFichier(this, 'nom-doc-agrement')">
                    <span id="nom-doc-agrement" style="font-size:11px;color:#0D9E75;margin-left:8px;"></span>
                    <div style="font-size:11px;color:#888;margin-top:4px;">{{ __('etablissement.doc_agrement_hint') }}</div>
                </div>

                <button type="submit" class="btn-p" style="margin-top:8px;width:auto;padding:10px 24px;">{{ __('etablissement.enregistrer_modifs') }}</button>
            </form>
        </div>

        {{-- ── Statut & infos lecture seule ── --}}
        <div>
            <div class="epcard" style="margin-bottom:14px;">
                <div style="font-size:14px;font-weight:700;margin-bottom:12px;">{{ __('etablissement.statut_compte') }}</div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">{{ __('etablissement.code_etab') }}</span>
                    <strong style="font-size:13px;">{{ $etab->code_etablissement ?? '—' }}</strong>
                </div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">{{ __('etablissement.statut') }}</span>
                    <span class="pill {{ match($etab->statut ?? '') {
                        'actif' => 'pg', 'en_attente' => 'pa', 'suspendu' => 'pr', default => 'pa',
                    } }}">
                        {{ match($etab->statut ?? '') {
                            'actif' => __('etablissement.actif'), 'en_attente' => __('etablissement.st_en_attente'), 'suspendu' => __('etablissement.statut_suspendu'), default => '—',
                        } }}
                    </span>
                </div>
                <div class="row">
                    <span style="font-size:13px;color:#666;">{{ __('etablissement.taux_commission') }}</span>
                    <strong style="font-size:13px;">{{ number_format(($etab->taux_commission ?? 0) * 100, 2, ',', '') }}%</strong>
                </div>
            </div>

            {{-- ── Catégories de frais ── --}}
            <div class="epcard">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                    <div style="font-size:14px;font-weight:700;">{{ __('etablissement.categories_frais') }}</div>
                    @if(Route::has('etablissement.categories-frais.create'))
                        <a href="{{ route('etablissement.categories-frais.create') }}" style="font-size:11px;color:var(--ep-teal);text-decoration:none;">{{ __('etablissement.ajouter') }}</a>
                    @endif
                </div>
                @forelse (($categoriesFrais ?? []) as $cat)
                    <div class="row">
                        <div>
                            <div style="font-size:13px;font-weight:600;">{{ $cat->nom }}</div>
                            <div style="font-size:10px;color:#888;">
                                {{ number_format($cat->montant_total, 0, ',', ' ') }} FCFA
                                @if($cat->fractionnable) · {{ __('etablissement.fractionnable_nb', ['count' => $cat->nb_tranches_max]) }} @endif
                            </div>
                        </div>
                        <span class="pill {{ $cat->actif ? 'pg' : 'pr' }}">{{ $cat->actif ? __('etablissement.active') : __('etablissement.inactive') }}</span>
                    </div>
                @empty
                    <div style="text-align:center;color:#999;font-size:13px;padding:14px 0;">{{ __('etablissement.aucune_categorie') }}</div>
                @endforelse
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function afficherNomFichier(input, spanId) {
    const span = document.getElementById(spanId);
    if (input.files && input.files[0]) {
        span.textContent = input.files[0].name;
    }
}

function previewLogo(input) {
    const preview = document.getElementById('logo-preview');
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { preview.innerHTML = '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;" />'; };
    reader.readAsDataURL(input.files[0]);
}
</script>
@endpush
