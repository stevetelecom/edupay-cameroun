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

        $dernierAbonnement = Abonnement::where('etablissement_id', $etablissement->id)
            ->latest()
            ->first();

        return view('etablissement.abonnement-requis', [
            'etablissement' => $etablissement,
            'abonnement'    => $dernierAbonnement,
            'plans'         => Abonnement::PLANS,
        ]);
    }
}
