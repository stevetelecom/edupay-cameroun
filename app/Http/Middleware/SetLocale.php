<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Langues autorisees. */
    private const LANGUES = ['fr', 'en'];

    /**
     * Applique la langue du visiteur parmi fr / en.
     *
     * Audit P : ce middleware n'etait attache qu'au groupe `web`. L'API
     * (front mobile) restait donc toujours en francais, et les traductions
     * `resources/lang/en` n'etaient atteignables par aucun appel API.
     *
     * Ordre de resolution :
     *   1. ?lang=xx        (explicite, pratique pour le mobile)
     *   2. session
     *   3. cookie `locale` (pose par l'application)
     *   4. locale par defaut de l'application (fr)
     *
     * `Accept-Language` n'est volontairement PAS pris en compte : un client
     * qui envoie par heritage un en-tete navigateur (en-US) ne doit pas
     * voir les messages du back-office bascule en anglais. Le changement de
     * langue reste une decision explicite de l'application.
     *
     * L'acces session est garde : le groupe `api` n'a pas de session, un
     * appel direct sur `$request->session()` y echouerait.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?: ($request->hasSession() ? $request->session()->get('locale') : null)
            ?: $request->cookie('locale')
            ?: config('app.locale');

        if (in_array($locale, self::LANGUES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
