<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AideController extends Controller
{
    public function index(): View
    {
        $etablissement = Auth::user()->etablissement;

        return view('etablissement.aide', compact('etablissement'));
    }
}
