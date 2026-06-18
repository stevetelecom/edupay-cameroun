<?php
namespace App\Http\Controllers\Payeur;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $apprenants = $user->apprenants ?? collect();

        return view('payeur.dashboard', [
            'apprenants' => $apprenants,
            'pageTitle'  => 'Mon espace — EduPay',
        ]);
    }
}
