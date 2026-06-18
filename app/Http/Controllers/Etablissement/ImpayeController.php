<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ImpayeController extends Controller
{
    public function index() { return view('etablissement.impayes.index'); }
    public function relancerSms(Request $request) { return back(); }
}
