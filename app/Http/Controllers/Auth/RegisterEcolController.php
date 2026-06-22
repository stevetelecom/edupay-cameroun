<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterEcolController extends Controller
{
    public function step1(): View
    {
        return view('auth.register-ecole', [
            'step' => 1,
            'old'  => session('register_ecole.step1', []),
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom'              => 'required|string|max:150',
            'type'             => 'required|in:maternelle,primaire,college,lycee_general,lycee_technique,universite,institut_prive,groupe_scolaire',
            'statut_juridique' => 'required|in:public,prive_laic,prive_catholique,prive_protestant,prive_islamique',
            'numero_agrement'  => 'required|string|max:100',
            'nb_eleves'        => 'nullable|in:moins_100,100_300,300_500,500_1000,plus_1000',
            'region'           => 'required|in:centre,littoral,ouest,nord,adamaoua,est,sud,sud_ouest,nord_ouest,extreme_nord',
            'ville'            => 'required|string|max:100',
            'quartier'         => 'nullable|string|max:100',
            'boite_postale'    => 'nullable|string|max:50',
        ]);

        $request->session()->put('register_ecole.step1', $validated);

        return redirect()->route('register.ecole.step2');
    }

    public function step2(): View|RedirectResponse
    {
        if (!session()->has('register_ecole.step1')) {
            return redirect()->route('register.ecole.step1');
        }

        return view('auth.register-ecole', [
            'step' => 2,
            'old'  => session('register_ecole.step2', []),
        ]);
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'telephone'              => 'required|string|max:20',
            'email'                  => 'required|email|max:150|unique:etablissements,email',
            'site_web'               => 'nullable|url|max:200',
            'mobile_money_principal' => 'nullable|in:mtn,orange,les_deux',

            'resp_prenom'    => 'required|string|max:100',
            'resp_nom'       => 'required|string|max:100',
            'resp_telephone' => 'required|string|max:20|unique:users,telephone',
            'resp_email'     => 'required|email|max:150|unique:users,email',
            'resp_password'  => 'required|string|min:8|confirmed',
        ]);

        // On hash le mot de passe avant de le stocker en session
        // (le cast 'hashed' du modèle User détecte qu'il est déjà hashé et ne le re-hash pas)
        $validated['resp_password'] = Hash::make($validated['resp_password']);

        $request->session()->put('register_ecole.step2', $validated);

        return redirect()->route('register.ecole.step3');
    }

    public function step3(): View|RedirectResponse
    {
        if (!session()->has('register_ecole.step2')) {
            return redirect()->route('register.ecole.step1');
        }

        return view('auth.register-ecole', [
            'step' => 3,
            'old'  => session('register_ecole.step3', []),
        ]);
    }

    public function storeStep3(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_agrement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'logo'               => 'nullable|file|mimes:png,jpg,jpeg,svg|max:2048',
            'description'       => 'nullable|string|max:1000',
        ]);

        $documentPath = $request->file('document_agrement')->store('agrements', 'public');

        $logoPath = $request->hasFile('logo')
            ? $request->file('logo')->store('logos', 'public')
            : null;

        $request->session()->put('register_ecole.step3', [
            'document_agrement' => $documentPath,
            'logo'              => $logoPath,
            'description'       => $validated['description'] ?? null,
        ]);

        return redirect()->route('register.ecole.validation');
    }

    public function validation(): View|RedirectResponse
    {
        if (!session()->has('register_ecole.step2')) {
            return redirect()->route('register.ecole.step1');
        }

        return view('auth.register-ecole', [
            'step' => 4,
            'data' => session('register_ecole'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'cgu_accepted'            => 'required|accepted',
            'certification_accepted' => 'required|accepted',
        ]);

        $step1 = session('register_ecole.step1');
        $step2 = session('register_ecole.step2');
        $step3 = session('register_ecole.step3', []);

        if (!$step1 || !$step2) {
            return redirect()->route('register.ecole.step1')
                ->with('error', 'Session expirée, merci de recommencer.');
        }

        $prefixeType = match ($step1['type']) {
            'lycee_general', 'lycee_technique' => 'LYC',
            'college'    => 'COL',
            'primaire'   => 'PRI',
            'maternelle' => 'MAT',
            'universite' => 'UNI',
            default      => 'ETB',
        };
        $codeVille = Str::upper(Str::substr(preg_replace('/[^a-zA-Z]/', '', $step1['nom']), 0, 3));
        $codeEtablissement = $prefixeType . '-' . $codeVille . '-' . date('Y') . '-' . random_int(100, 999);

        // ── Enregistrement n°1 : l'établissement ───────────────────────────
        $etablissement = Etablissement::create([
            'code_etablissement'     => $codeEtablissement,
            'nom'                    => $step1['nom'],
            'type'                   => $step1['type'],
            'statut_juridique'       => $step1['statut_juridique'],
            'numero_agrement'        => $step1['numero_agrement'],
            'nb_eleves'              => $step1['nb_eleves'] ?? null,
            'region'                 => $step1['region'],
            'ville'                  => $step1['ville'],
            'quartier'               => $step1['quartier'] ?? null,
            'boite_postale'          => $step1['boite_postale'] ?? null,
            'telephone'              => $step2['telephone'],
            'email'                  => $step2['email'],
            'site_web'               => $step2['site_web'] ?? null,
            'mobile_money_principal' => $step2['mobile_money_principal'] ?? null,
            'document_agrement'      => $step3['document_agrement'] ?? null,
            'logo'                    => $step3['logo'] ?? null,
            'description'             => $step3['description'] ?? null,
            'statut'                 => 'en_attente',
        ]);

        // ── Enregistrement n°2 : le compte du responsable (directeur) ──────
        $directeur = User::create([
            'prenom'           => $step2['resp_prenom'],
            'nom'              => $step2['resp_nom'],
            'telephone'        => $step2['resp_telephone'],
            'email'            => $step2['resp_email'],
            'password'         => $step2['resp_password'],
            'etablissement_id' => $etablissement->id,
        ]);

        $directeur->assignRole('directeur');

        $request->session()->forget('register_ecole');

        return redirect()->route('register.ecole.validation')
            ->with('success', true)
            ->with('code_etablissement', $codeEtablissement);
    }
}