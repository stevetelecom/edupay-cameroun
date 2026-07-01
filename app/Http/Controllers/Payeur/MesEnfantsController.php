<?php
namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
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

        $etablissements = Etablissement::where('statut', 'actif')
            ->select(['id', 'nom', 'ville', 'type', 'code_etablissement', 'logo'])
            ->orderBy('nom')
            ->get();

        $monDossier = null;
        if (in_array(Auth::user()->profil ?? '', ['eleve', 'etudiant'])) {
            $monDossier = $user->apprenants()->first();
        }

        return view('payeur.mes-enfants', compact(
            'apprenants',
            'premierFraisImpaye',
            'etablissements',
            'monDossier',
        ));
    }
}
