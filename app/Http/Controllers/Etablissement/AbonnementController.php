<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AbonnementController extends Controller
{
    public function requis(): View
    {
        $etablissement = Auth::user()->etablissement;

        // Meme selection que CheckAbonnement et Etablissement::abonnementCourant()
        // (date_debut puis id) : `latest()` triait sur created_at, donc deux
        // abonnements inseres le meme jour pouvaient afficher une periode
        // differente de celle appliquee par le middleware.
        $dernierAbonnement = $etablissement->abonnementCourant();

        return view('etablissement.abonnement-requis', [
            'etablissement' => $etablissement,
            'abonnement'    => $dernierAbonnement,
            'plans'         => Abonnement::PLANS,
        ]);
    }
}
