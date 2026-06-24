<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $pageTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-100 flex flex-col items-center justify-center font-sans antialiased">

    <div class="mb-6 text-center">
        <div class="text-2xl font-bold text-[#0B2545] tracking-tight">
            Edu<span class="text-[#0D9E75]">Pay</span> Cameroun
        </div>
        <div class="text-xs text-gray-400 mt-1">Vérification en deux étapes</div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-sm">

        {{-- Icône téléphone --}}
        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
                <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                </svg>
            </div>
        </div>

        <h1 class="text-lg font-bold text-center text-gray-900 mb-1">Code de vérification</h1>
        <p class="text-sm text-gray-500 text-center mb-6">
            Entrez le code à 6 chiffres envoyé par SMS sur votre téléphone enregistré.
            Ce code expire dans <strong class="text-gray-700">5 minutes</strong>.
        </p>

        @if (session('info'))
            <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg text-center">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg text-center">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.2fa.verify') }}">
            @csrf

            {{-- Champ code OTP --}}
            <div class="mb-5">
                <label for="code" class="block text-xs font-medium text-gray-600 mb-2 text-center">
                    Code à 6 chiffres
                </label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    autofocus
                    required
                    class="w-full text-center text-2xl font-mono font-bold tracking-[0.5em] py-4 border-2 rounded-xl focus:outline-none transition-colors {{ $errors->has('code') ? 'border-red-400 focus:border-red-600' : 'border-gray-300 focus:border-[#0D9E75]' }}"
                    placeholder="000000"
                />
                @error('code')
                    <p class="text-xs text-red-500 mt-1 text-center">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
                Vérifier et accéder au tableau de bord
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('admin.login') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
                Annuler et revenir à la connexion
            </a>
        </div>
    </div>

    <div class="mt-6 text-xs text-gray-400 text-center">
        © 2026 EduPay Cameroun · TLS 1.3 · Authentification 2FA obligatoire
    </div>

</body>
</html>