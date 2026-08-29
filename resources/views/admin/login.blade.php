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
        <div class="text-xs text-gray-400 mt-1">{{ __('admin.espace_super_admin') }}</div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-sm">

        <div class="flex justify-center mb-5">
            <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
                <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-lg font-bold text-center text-gray-900 mb-1">{{ __('admin.connexion_securisee') }}</h1>
        <p class="text-sm text-gray-500 text-center mb-6">
            {{ __('admin.acces_reserve_admins') }}
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
                    {{ __('auth.adresse_email') }}
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
                    {{ __('auth.mot_de_passe') }}
                </label>
                <div style="position:relative;">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none transition-colors {{ $errors->has('password') ? 'border-red-400 focus:border-red-600' : 'border-gray-300 focus:border-[#0D9E75]' }}"
                        style="padding-right:44px;"
                    />
                    <button type="button" onclick="togglePwd()" tabindex="-1"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);
                                   background:none;border:none;cursor:pointer;padding:0;color:#888;">
                        <svg id="eye-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg id="eye-off-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" style="display:none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <script>
            function togglePwd() {
                var inp = document.getElementById('password');
                var eye = document.getElementById('eye-icon');
                var eyeOff = document.getElementById('eye-off-icon');
                if (inp.type === 'password') {
                    inp.type = 'text';
                    eye.style.display = 'none';
                    eyeOff.style.display = 'block';
                } else {
                    inp.type = 'password';
                    eye.style.display = 'block';
                    eyeOff.style.display = 'none';
                }
            }
            </script>

            <button
                type="submit"
                class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
                {{ __('admin.continuer_2fa') }}
            </button>
        </form>
        <div class="mt-4 text-center">
            <a href="{{ route('admin.password.forgot') }}" class="text-xs text-gray-400 hover:text-[#0D9E75] transition-colors">
                {{ __('auth.mot_de_passe_oublie') }}
            </a>
        </div>
    </div>

    <div class="mt-6 text-xs text-gray-400 text-center">
        {{ __('admin.copyright_tls') }}
    </div>

</body>
</html>
