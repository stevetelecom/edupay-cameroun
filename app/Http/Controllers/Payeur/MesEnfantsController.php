<?php
namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MesEnfantsController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $apprenants = $user->apprenants()
            ->with(['etablissement', 'frais.categorieFrais'])
            ->get();

        $premierFraisImpaye = null;
        foreach ($apprenants as $apprenant) {
            $fraisImpaye = $apprenant->frais->first(fn($f) => $f->statut !== 'regle');
            if ($fraisImpaye) {
                $premierFraisImpaye = $fraisImpaye;
                break;
            }
        }

        return view('payeur.mes-enfants', [
            'apprenants'         => $apprenants,
            'premierFraisImpaye' => $premierFraisImpaye,
        ]);
    }
}
