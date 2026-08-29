<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $pageTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 flex flex-col items-center justify-center font-sans antialiased py-10">

    <div class="mb-6 text-center">
        <div class="text-2xl font-bold text-[#0B2545] tracking-tight">
            Edu<span class="text-[#0D9E75]">Pay</span> Cameroun
        </div>
        <div class="text-xs text-gray-400 mt-1">{{ __('admin.creation_super_admin') }}</div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-md">

        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
                <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-lg font-bold text-center text-gray-900 mb-1">{{ __('admin.creer_super_admin') }}</h1>
        <p class="text-sm text-gray-500 text-center mb-6">
            {{ __('admin.page_accessible_une_fois') }}
        </p>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.register.post') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}" />

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.prenom') }} *</label>
                    <input type="text" name="prenom" value="{{ old('prenom') }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                           placeholder="Olivier" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.nom') }} *</label>
                    <input type="text" name="nom" value="{{ old('nom') }}" required
                           class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                           placeholder="MEKONTSO" />
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.adresse_email') }} *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                       placeholder="admin@edupay.cm" />
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('admin.telephone_2fa_etoile') }}</label>
                <input type="text" class="tel-cm-input" data-allow-fixe="false" name="telephone" value="{{ old('telephone') }}" placeholder="6XXXXXXXX" required
                       class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]"
                       placeholder="6XXXXXXXX" />
            </div>

            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('admin.mdp_min10_etoile') }}</label>
                <input type="password" name="password" required autocomplete="new-password"
                       class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
            </div>

            <div class="mb-6">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.confirmer_mdp_etoile') }}</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:border-[#0D9E75]" />
            </div>

            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-4 py-3 rounded-lg">
                {{ __('admin.formulaire_une_fois') }}
            </div>

            <button type="submit"
                    class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
                {{ __('admin.creer_compte_super_admin') }}
            </button>
        </form>
    </div>

    <div class="mt-6 text-xs text-gray-400 text-center">
        {{ __('admin.copyright_tls') }}
    </div>

</body>
</html>

@include('partials.telephone-cm-script')
<script>document.addEventListener('DOMContentLoaded', function() { initTelephoneCm('.tel-cm-input'); });</script>
