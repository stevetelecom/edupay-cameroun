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

    /**
     * Normalise un numéro camerounais saisi sous n'importe quel format
     * (+237 6XX XXX XXX, 237699123456, 6 99 12 34 56, etc.) vers 9 chiffres
     * bruts sans indicatif — format déjà utilisé dans le reste de l'app
     * (voir LoginController::login()).
     */
    private function normaliserTelephoneCm(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) > 9) {
            $digits = substr($digits, -9);
        }
        return $digits;
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        // Normaliser les numéros AVANT validation (accepte +237, espaces, tirets en saisie)
        $request->merge([
            'telephone'               => $this->normaliserTelephoneCm((string) $request->input('telephone', '')),
            'resp_telephone'          => $this->normaliserTelephoneCm((string) $request->input('resp_telephone', '')),
            'numero_momo_reversement' => $this->normaliserTelephoneCm((string) $request->input('numero_momo_reversement', '')),
        ]);

        $validated = $request->validate([
            // Téléphone établissement : mobile (6) OU fixe/Camtel (2 ou 3), 9 chiffres
            'telephone'              => ['required', 'regex:/^[236]\d{8}$/'],
            'email'                  => 'required|email|max:150|unique:etablissements,email',
            'site_web'               => 'nullable|url|max:200',
            'mobile_money_principal'   => 'required|in:mtn,orange',
            // Numéro Mobile Money : forcément un mobile (compte MoMo réel)
            'numero_momo_reversement'  => ['required', 'regex:/^6\d{8}$/'],

            'resp_prenom'    => 'required|string|max:100',
            'resp_nom'       => 'required|string|max:100',
            // Téléphone du responsable : mobile uniquement
            'resp_telephone' => ['required', 'regex:/^6\d{8}$/', 'unique:users,telephone'],
            'resp_email'     => 'required|email|max:150|unique:users,email',
            'resp_password'  => 'nullable|string|min:8|confirmed',
        ], [
            'email.unique'          => 'Cet email est déjà utilisé par un autre établissement. Si vous avez déjà un compte, ',
            'resp_email.unique'     => 'Cet email est déjà utilisé pour un compte existant. ',
            'resp_telephone.unique' => 'Ce numéro de téléphone est déjà utilisé pour un compte existant. ',
            'telephone.regex'              => 'Numéro invalide. Format attendu : 6XXXXXXXX (mobile) ou 2XXXXXXXX / 3XXXXXXXX (fixe/Camtel).',
            'resp_telephone.regex'         => 'Numéro invalide. Format attendu : 6XXXXXXXX (mobile camerounais).',
            'numero_momo_reversement.regex'=> 'Numéro Mobile Money invalide. Format attendu : 6XXXXXXXX.',
        ]);

        // Gestion du mot de passe :
        // - Nouveau mot de passé saisi → hasher et stocker
        // - Champ vide (retour arrière) → conserver le hash existant en session
        // - Aucun hash existant (premier passage) → erreur
        if (!empty($validated['resp_password'])) {
            $validated['resp_password'] = Hash::make($validated['resp_password']);
        } else {
            $existingPassword = session('register_ecole.step2.resp_password');
            if ($existingPassword) {
                $validated['resp_password'] = $existingPassword;
            } else {
                return back()->withErrors([
                    'resp_password' => 'Le mot de passe est obligatoire.',
                ])->withInput();
            }
        }

        unset($validated['resp_password_confirmation']);

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
        // Si l'inscription vient d'être finalisée (flash 'inscription_reussie'),
        // on affiche la page de confirmation même si 'register_ecole' a déjà
        // été vidée par store(). Sans ce check, la page rebondissait vers
        // step1 et le toast de succès disparaissait silencieusement.
        if (!session('inscription_reussie') && !session()->has('register_ecole.step2')) {
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
            'mobile_money_principal'      => $step2['mobile_money_principal'] ?? null,
            'numero_momo_reversement'     => $step2['numero_momo_reversement'] ?? null,
            'operateur_momo_reversement'  => $step2['mobile_money_principal'] ?? null,
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
            ->with('inscription_reussie', true)
            ->with('code_etablissement', $codeEtablissement)
            ->with('success', 'Votre demande d\'inscription a été envoyée avec succès. Notre équipe va étudier votre dossier.');
    }

    /**
     * Sauvegarde les données de l'étape en cours (sans validation stricte)
     * et redirige vers l'étape précédente.
     * Les boutons « ← Retour » soumettent le formulaire via formaction
     * pour ne pas perdre les modifications non enregistrées.
     */
    public function saveAndBack(Request $request, int $step): \Illuminate\Http\RedirectResponse
    {
        $sessionKey = "register_ecole.step{$step}";
        $existing   = session($sessionKey, []);
        $incoming   = $request->except(['_token']);

        switch ($step) {
            case 1:
                $data = array_merge($existing, $incoming);
                break;

            case 2:
                if (!empty($incoming['telephone'])) {
                    $incoming['telephone'] = $this->normaliserTelephoneCm((string) $incoming['telephone']);
                }
                if (!empty($incoming['resp_telephone'])) {
                    $incoming['resp_telephone'] = $this->normaliserTelephoneCm((string) $incoming['resp_telephone']);
                }
                if (!empty($incoming['numero_momo_reversement'])) {
                    $incoming['numero_momo_reversement'] = $this->normaliserTelephoneCm((string) $incoming['numero_momo_reversement']);
                }
                $hasNewPassword  = !empty($incoming['resp_password']);
                $plainPassword   = $incoming['resp_password'] ?? null;
                unset($incoming['resp_password'], $incoming['resp_password_confirmation']);
                $data = array_merge($existing, $incoming);
                if ($hasNewPassword && $plainPassword !== null) {
                    $data['resp_password'] = Hash::make($plainPassword);
                }
                break;

            case 3:
                $data = array_merge($existing, $incoming);
                $data['document_agrement'] = $request->hasFile('document_agrement')
                    ? $request->file('document_agrement')->store('agrements', 'public')
                    : ($existing['document_agrement'] ?? null);
                $data['logo'] = $request->hasFile('logo')
                    ? $request->file('logo')->store('logos', 'public')
                    : ($existing['logo'] ?? null);
                break;

            default:
                // Étape 4 = validation, pas de données éditables
                $data = $existing;
                break;
        }

        $request->session()->put($sessionKey, $data);

        $previousStep = max(1, $step - 1);
        return redirect()->route("register.ecole.step{$previousStep}");
    }
}