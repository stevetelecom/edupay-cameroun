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
        <div class="text-xs text-gray-400 mt-1">Espace Super Admin</div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-sm">

        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
                <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-lg font-bold text-center text-gray-900 mb-1">Connexion sécurisée</h1>
        <p class="text-sm text-gray-500 text-center mb-6">
            Accès réservé aux administrateurs système. Authentification 2FA obligatoire.
        </p>

        @if (session('info'))
            <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg text-center">
                {{ session('info') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg text-center">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-xs font-medium text-gray-600 mb-1.5">
                    Adresse email
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autofocus
                    required
                    autocomplete="username"
                    class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none transition-colors {{ $errors->has('email') ? 'border-red-400 focus:border-red-600' : 'border-gray-300 focus:border-[#0D9E75]' }}"
                    placeholder="admin@edupay.cm"
                />
                @error('email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="password" class="block text-xs font-medium text-gray-600 mb-1.5">
                    Mot de passe
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none transition-colors {{ $errors->has('password') ? 'border-red-400 focus:border-red-600' : 'border-gray-300 focus:border-[#0D9E75]' }}"
                />
                @error('password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
                Continuer vers la vérification 2FA
            </button>
        </form>
    </div>

    <div class="mt-6 text-xs text-gray-400 text-center">
        © 2026 EduPay Cameroun · TLS 1.3 · Accès restreint
    </div>

</body>
</html>
