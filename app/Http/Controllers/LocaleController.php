<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Bascule la langue de l'interface (fr / en)
     * et redirige vers la page précédente.
     */
    public function switch(Request $request)
    {
        $locale = $request->input('locale', 'fr');

        if (!in_array($locale, ['fr', 'en'])) {
            $locale = 'fr';
        }

        // On mémorise la langue en session ET en cookie persistant (30 jours),
        // afin que la traduction soit stable sur toutes les pages et visites.
        session(['locale' => $locale]);
        app()->setLocale($locale);

        return back()->withCookie(cookie()->forever('locale', $locale));
    }
}
