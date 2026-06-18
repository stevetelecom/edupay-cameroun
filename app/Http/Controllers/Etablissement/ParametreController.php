<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;

class ParametreController extends Controller
{
    public function index() { return view('etablissement.parametres.index'); }
}
