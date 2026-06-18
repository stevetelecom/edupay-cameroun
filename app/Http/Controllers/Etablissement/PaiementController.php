<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;

class PaiementController extends Controller
{
    public function index() { return view('etablissement.paiements.index'); }
}
