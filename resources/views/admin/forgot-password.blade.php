<!DOCTYPE html><html lang="fr" class="h-full">
<head><meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>{{ $pageTitle }}</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="h-full bg-gray-100 flex flex-col items-center justify-center font-sans antialiased">
<div class="mb-6 text-center">
  <div class="text-2xl font-bold text-[#0B2545] tracking-tight">Edu<span class="text-[#0D9E75]">Pay</span> Cameroun</div>
  <div class="text-xs text-gray-400 mt-1">{{ __('admin.reinitialisation_super_admin') }}</div>
</div>
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 w-full max-w-sm">
  <div class="flex justify-center mb-5">
    <div class="w-14 h-14 bg-[#E0F5EE] rounded-full flex items-center justify-center">
      <svg class="w-7 h-7 text-[#0D9E75]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
        <path d="M7 11V7a5 5 0 0110 0v4"/>
      </svg>
    </div>
  </div>
  <h1 class="text-lg font-bold text-center text-gray-900 mb-1">{{ __('auth.mot_de_passe_oublie') }}</h1>
  <p class="text-sm text-gray-500 text-center mb-6">
    {{ __('admin.entrez_email_admin') }}
  </p>
  @if(session('info'))
  <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-700 text-sm px-4 py-3 rounded-lg text-center">{{ session('info') }}</div>
  @endif
  @if($errors->any())
  <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
  </div>
  @endif
  <form method="POST" action="{{ route('admin.password.forgot.send') }}">
    @csrf
    <div class="mb-5">
      <label for="email" class="block text-xs font-medium text-gray-600 mb-1.5">{{ __('auth.adresse_email') }}</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
             placeholder="admin@edupay.cm"
             class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none transition-colors border-gray-300 focus:border-[#0D9E75]" />
      @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="w-full bg-[#0D9E75] hover:bg-[#0A8562] text-white font-semibold text-sm py-3 rounded-lg transition-colors">
      {{ __('admin.envoyer_code_reinit') }}
    </button>
  </form>
  <div class="mt-4 text-center">
    <a href="{{ route('admin.login') }}" class="text-xs text-gray-400 hover:text-gray-600 transition-colors">
      {{ __('auth.retour_connexion') }}
    </a>
  </div>
</div>
<div class="mt-6 text-xs text-gray-400 text-center">{{ __('admin.copyright_tls') }}</div>
</body></html>
