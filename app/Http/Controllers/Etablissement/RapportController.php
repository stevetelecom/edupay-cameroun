<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;

class RapportController extends Controller
{
    public function index() { return view('etablissement.rapports.index'); }
}
