<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return view('etablissement.dashboard');
    }
}
