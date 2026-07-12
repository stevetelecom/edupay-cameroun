<?php

namespace App\Http\Controllers\Etablissement;

use App\Http\Controllers\Controller;
use App\Models\CategoriesFrais;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ParametreController extends Controller
{
    public function index()
    {
        $etablissement = Auth::user()->etablissement;
        $categoriesFrais = CategoriesFrais::where('etablissement_id', $etablissement->id)
            ->orderBy('nom')->get();
        return view('etablissement.parametres.index', compact('etablissement', 'categoriesFrais'));
    }

    public function update(Request $request)
    {
        $etablissement = Auth::user()->etablissement;

        $validated = $request->validate([
            'nom'                    => ['required', 'string', 'max:150'],
            'type'                   => ['required', Rule::in(['maternelle','primaire','secondaire','universitaire','formation'])],
            'statut_juridique'       => ['nullable', 'string', 'max:100'],
            'numero_agrement'        => ['nullable', 'string', 'max:100'],
            'nb_eleves'              => ['nullable', 'in:moins_100,100_300,300_500,500_1000,plus_1000'],
            'region'                 => ['nullable', 'string', 'max:100'],
            'ville'                  => ['required', 'string', 'max:100'],
            'quartier'               => ['nullable', 'string', 'max:100'],
            'boite_postale'          => ['nullable', 'string', 'max:50'],
            'telephone'              => ['required', 'string', 'max:20'],
            'email'                  => ['required', 'email', 'max:150'],
            'site_web'               => ['nullable', 'url', 'max:200'],
            'description'            => ['nullable', 'string', 'max:1000'],
            'mobile_money_principal'      => ['required', Rule::in(['mtn', 'orange'])],
            'numero_momo_reversement'     => ['required', 'string', 'max:20'],
            'logo'                   => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'document_agrement'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            if ($etablissement->logo) {
                Storage::disk('public')->delete($etablissement->logo);
            }
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        } else {
            unset($validated['logo']);
        }

        if ($request->hasFile('document_agrement')) {
            if ($etablissement->document_agrement) {
                Storage::disk('public')->delete($etablissement->document_agrement);
            }
            $validated['document_agrement'] = $request->file('document_agrement')->store('agrements', 'public');
        } else {
            unset($validated['document_agrement']);
        }

        $etablissement->update($validated);

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }
}
