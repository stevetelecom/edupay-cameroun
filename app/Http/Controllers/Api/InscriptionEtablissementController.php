<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InscriptionEtablissementController extends Controller
{
    /**
     * Inscription d'un établissement (équivalent API des 3 étapes web + validation).
     * Endpoint unique et sans état : l'app mobile envoie toutes les données d'un coup.
     * Le compte créé est un directeur, l'établissement est en statut 'en_attente'
     * (validation par l'équipe EduPay avant activation).
     */
    public function store(Request $request): JsonResponse
    {
        // Normaliser les numéros camerounais avant validation
        $request->merge([
            'telephone'            => $this->normaliserTelephoneCm((string) $request->input('telephone', '')),
            'resp_telephone'       => $this->normaliserTelephoneCm((string) $request->input('resp_telephone', '')),
            'numero_momo_reversement' => $this->normaliserTelephoneCm((string) $request->input('numero_momo_reversement', '')),
        ]);

        $validated = $request->validate([
            // ── Étape 1 : identité de l'établissement ──
            'nom'              => ['required', 'string', 'max:150'],
            'type'             => ['required', 'in:maternelle,primaire,college,lycee_general,lycee_technique,universite,institut_prive,groupe_scolaire'],
            'statut_juridique' => ['required', 'in:public,prive_laic,prive_catholique,prive_protestant,prive_islamique'],
            'numero_agrement'  => ['required', 'string', 'max:100'],
            'nb_eleves'        => ['nullable', 'in:moins_100,100_300,300_500,500_1000,plus_1000'],
            'region'           => ['required', 'in:centre,littoral,ouest,nord,adamaoua,est,sud,sud_ouest,nord_ouest,extreme_nord'],
            'ville'            => ['required', 'string', 'max:100'],
            'quartier'         => ['nullable', 'string', 'max:100'],
            'boite_postale'    => ['nullable', 'string', 'max:50'],

            // ── Étape 2 : coordonnées & compte responsable ──
            'telephone'              => ['required', 'regex:/^[236]\d{8}$/'],
            'email'                  => ['required', 'email', 'max:150', 'unique:etablissements,email'],
            'site_web'               => ['nullable', 'url', 'max:200'],
            'mobile_money_principal' => ['required', 'in:mtn,orange'],
            'numero_momo_reversement'=> ['required', 'regex:/^6\d{8}$/'],
            'resp_prenom'            => ['required', 'string', 'max:100'],
            'resp_nom'               => ['required', 'string', 'max:100'],
            'resp_telephone'         => ['required', 'regex:/^6\d{8}$/', 'unique:users,telephone'],
            'resp_email'             => ['required', 'email', 'max:150', 'unique:users,email'],
            'resp_password'          => ['required', 'string', 'min:8', 'confirmed'],

            // ── Étape 3 : documents ──
            'document_agrement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'logo'               => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'description'        => ['nullable', 'string', 'max:1000'],

            // ── Étape 4 : validation ──
            'cgu_accepted'             => ['required', 'accepted'],
            'certification_accepted'   => ['required', 'accepted'],
        ], [
            'email.unique'          => 'Cet email est déjà utilisé par un autre établissement. Si vous avez déjà un compte, connectez-vous.',
            'resp_email.unique'     => 'Cet email est déjà utilisé pour un compte existant.',
            'resp_telephone.unique' => 'Ce numéro de téléphone est déjà utilisé pour un compte existant.',
            'telephone.regex'       => 'Numéro invalide. Format attendu : 6XXXXXXXX (mobile) ou 2XXXXXXXX / 3XXXXXXXX (fixe/Camtel).',
            'resp_telephone.regex'  => 'Numéro invalide. Format attendu : 6XXXXXXXX (mobile camerounais).',
            'numero_momo_reversement.regex' => 'Numéro Mobile Money invalide. Format attendu : 6XXXXXXXX.',
        ]);

        $documentPath = $request->file('document_agrement')->store('agrements', 'public');
        $logoPath     = $request->hasFile('logo') ? $request->file('logo')->store('logos', 'public') : null;

        $prefixeType = match ($validated['type']) {
            'lycee_general', 'lycee_technique' => 'LYC',
            'college'    => 'COL',
            'primaire'   => 'PRI',
            'maternelle' => 'MAT',
            'universite' => 'UNI',
            default      => 'ETB',
        };
        $codeVille = Str::upper(Str::substr(preg_replace('/[^a-zA-Z]/', '', $validated['nom']), 0, 3));
        $codeEtablissement = $prefixeType . '-' . $codeVille . '-' . date('Y') . '-' . random_int(100, 999);

        $etablissement = Etablissement::create([
            'code_etablissement'         => $codeEtablissement,
            'nom'                        => $validated['nom'],
            'type'                       => $validated['type'],
            'statut_juridique'           => $validated['statut_juridique'],
            'numero_agrement'            => $validated['numero_agrement'],
            'nb_eleves'                  => $validated['nb_eleves'] ?? null,
            'region'                     => $validated['region'],
            'ville'                      => $validated['ville'],
            'quartier'                   => $validated['quartier'] ?? null,
            'boite_postale'              => $validated['boite_postale'] ?? null,
            'telephone'                  => $validated['telephone'],
            'email'                      => $validated['email'],
            'site_web'                   => $validated['site_web'] ?? null,
            'mobile_money_principal'     => $validated['mobile_money_principal'],
            'numero_momo_reversement'    => $validated['numero_momo_reversement'],
            'operateur_momo_reversement' => $validated['mobile_money_principal'],
            'document_agrement'          => $documentPath,
            'logo'                       => $logoPath,
            'description'                => $validated['description'] ?? null,
            'statut'                     => 'en_attente',
        ]);

        $directeur = User::create([
            'prenom'           => $validated['resp_prenom'],
            'nom'              => $validated['resp_nom'],
            'telephone'        => $validated['resp_telephone'],
            'email'            => $validated['resp_email'],
            'password'         => Hash::make($validated['resp_password']),
            'etablissement_id' => $etablissement->id,
        ]);
        $directeur->assignRole('directeur');

        return response()->json([
            'message'              => 'Votre demande d\'inscription a été envoyée avec succès. Notre équipe va étudier votre dossier.',
            'code_etablissement'   => $codeEtablissement,
            'statut'               => 'en_attente',
            'compte_creer'         => true,
        ], 201);
    }

    private function normaliserTelephoneCm(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);
        if (strlen($digits) > 9) {
            $digits = substr($digits, -9);
        }
        return $digits;
    }
}
