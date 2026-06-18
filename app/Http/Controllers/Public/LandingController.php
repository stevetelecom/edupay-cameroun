<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('public.landing');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function temoignages(): View
    {
        return view('public.temoignages');
    }
}
