<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Applique la langue choisie par le visiteur (session ou cookie)
     * parmi les langues autorisées (fr / en).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale')
            ?? $request->cookie('locale');

        if (in_array($locale, ['fr', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
