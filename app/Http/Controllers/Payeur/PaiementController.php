<?php
namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaiementController extends Controller
{
    public function show(FraisApprenant $fraisApprenant)
    {
        return view('payeur.paiement', compact('fraisApprenant'));
    }

    public function initier(Request $request, FraisApprenant $fraisApprenant)
    {
        return back();
    }

    public function historique()
    {
        $paiements = Paiement::where('user_id', Auth::id())->paginate(15);
        return view('payeur.historique', compact('paiements'));
    }
}
