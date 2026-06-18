<?php
namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class ApprenantController extends Controller
{
    public function index() { return view('etablissement.apprenants.index'); }
    public function create() { return view('etablissement.apprenants.create'); }
    public function store(Request $request) { return back(); }
    public function show(Apprenant $apprenant) { return view('etablissement.apprenants.show', compact('apprenant')); }
    public function edit(Apprenant $apprenant) { return view('etablissement.apprenants.edit', compact('apprenant')); }
    public function update(Request $request, Apprenant $apprenant) { return back(); }
    public function destroy(Apprenant $apprenant) { return back(); }
}
