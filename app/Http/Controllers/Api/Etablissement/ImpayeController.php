<?php

namespace App\Http\Controllers\Api\Etablissement;

use App\Http\Controllers\Controller;
use App\Http\Resources\FraisResource;
use App\Mail\RelanceImpayeMail;
use App\Models\Apprenant;
use App\Models\FraisApprenant;
use App\Models\Paiement;
use App\Models\RelanceImpaye;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Support\AnneeScolaire;

class ImpayeController extends Controller
{
    private const ROLES_ETABLISSEMENT = ['directeur', 'comptable', 'caissier'];

    /**
     * Liste des frais impayés de l'établissement + synthèse (équivalent web ImpayeController::index).
     */
    public function index(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = AnneeScolaire::active();

        // Audit H : le contrat mobile (docs/DOCUMENTATION_API.md) attend
        // `data` = tableau plat, UNE LIGNE PAR APPRENANT (montant_du =
        // somme de ses restes dus). La reponse renvoyait
        // `data.frais_impayes` (une collection de ressource, elle-meme
        // imbriquee) + `data.synthese` : le mobile lisait un objet la ou il
        // attendait un tableau, donc liste vide.
        // Q : `categorie_id` est un identifiant fourni par le client.
        // Sans ce controle, un etablissement pouvait filtrer sur la
        // categorie d'un autre etablissement (son nom, son montant et le
        // nombre d'affectations devenaient lisibles par difference).
        $request->validate([
            'categorie_id' => [
                'nullable',
                'integer',
                Rule::exists('categories_frais', 'id')->where('etablissement_id', $etablissementId),
            ],
        ], [
            'categorie_id.exists' => 'Catégorie de frais invalide pour cet établissement.',
        ]);

        $apprenants = Apprenant::with(['parents'])
            ->where('etablissement_id', $etablissementId)
            ->whereHas('frais', function ($q) use ($anneeScolaire, $request) {
                $q->where('annee_scolaire', $anneeScolaire)
                    ->where('statut', '!=', 'regle');

                if ($request->filled('categorie_id')) {
                    $q->where('categorie_frais_id', $request->categorie_id);
                }
            })
            ->when($request->filled('classe'), fn ($q) => $q->where('classe', $request->classe))
            ->orderBy('nom')
            ->paginate($request->integer('per_page', 20));

        // Restes dus et dernier paiement, calcules en 2 requetes pour la page
        // courante (pas de N+1).
        $apprenantIds = $apprenants->pluck('id')->all();

        $restesDus = FraisApprenant::whereIn('apprenant_id', $apprenantIds)
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->when($request->filled('categorie_id'), fn ($q) => $q->where('categorie_frais_id', $request->categorie_id))
            ->get(['apprenant_id', 'montant_total', 'montant_paye'])
            ->groupBy('apprenant_id')
            ->map(fn ($lignes) => (int) $lignes->sum(fn ($f) => $f->montant_total - $f->montant_paye));

        $derniersPaiements = Paiement::whereIn('apprenant_id', $apprenantIds)
            ->where('statut', 'valide')
            ->orderByDesc('date_paiement')
            ->get(['apprenant_id', 'montant', 'date_paiement'])
            ->groupBy('apprenant_id')
            ->map(fn ($paiements) => $paiements->first());

        // Synthese sur le meme perimetre que la liste (annee active +
        // etablissement + categorie eventuellement demandee), sinon les
        // totaux affiches ne correspondent pas aux lignes listees.
        $baseSynthese = fn (bool $uniquementImpayes = false) => FraisApprenant::query()
            ->where('annee_scolaire', $anneeScolaire)
            ->when($uniquementImpayes, fn ($q) => $q->where('statut', '!=', 'regle'))
            ->when($request->filled('categorie_id'), fn ($q) => $q->where('categorie_frais_id', $request->categorie_id))
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId));

        $totalImpaye = $baseSynthese(true)
            ->get()
            ->sum(fn ($f) => $f->montant_total - $f->montant_paye);

        $totalAttendu = $baseSynthese()->sum('montant_total');

        $totalPaye = $baseSynthese()->sum('montant_paye');

        $tauxRecouvrement = $totalAttendu > 0 ? round(($totalPaye / $totalAttendu) * 100) : 0;

        // U : la colonne valait null en permanence faute de trace des
        // relances. On lit la derniere relance REUSSIE par apprenant.
        $dernieresRelances = RelanceImpaye::query()
            ->whereIn('apprenant_id', $apprenantIds)
            ->where('etablissement_id', $etablissementId)
            ->envoyees()
            ->get(['apprenant_id', 'created_at', 'canal'])
            ->groupBy('apprenant_id')
            ->map(fn ($lignes) => $lignes->sortByDesc('created_at')->first());

        $lignes = $apprenants->getCollection()->map(fn (Apprenant $apprenant) => [
            'apprenant_id'     => $apprenant->id,
            'nom'              => trim($apprenant->prenom . ' ' . $apprenant->nom),
            'classe'           => $apprenant->classe,
            'montant_du'       => (int) ($restesDus[$apprenant->id] ?? 0),
            'dernier_paiement' => isset($derniersPaiements[$apprenant->id]) ? [
                'montant' => (int) $derniersPaiements[$apprenant->id]->montant,
                'date'    => $derniersPaiements[$apprenant->id]->date_paiement?->toIso8601String(),
            ] : null,
            'derniere_relance' => isset($dernieresRelances[$apprenant->id]) ? [
                'date'  => $dernieresRelances[$apprenant->id]->created_at?->toIso8601String(),
                'canal' => $dernieresRelances[$apprenant->id]->canal,
            ] : null,
            'telephone_parent' => $apprenant->parents->first()?->telephone,
        ])->values();

        return response()->json([
            'success' => true,
            'data'    => $lignes,
            'synthese' => [
                'total_impaye'      => (int) $totalImpaye,
                'total_attendu'     => (int) $totalAttendu,
                'total_paye'        => (int) $totalPaye,
                'taux_recouvrement' => (int) $tauxRecouvrement,
            ],
            'pagination' => [
                'current_page' => $apprenants->currentPage(),
                'last_page'    => $apprenants->lastPage(),
                'total'        => $apprenants->total(),
                'per_page'     => $apprenants->perPage(),
            ],
            // Rappel : l'ancienne forme etait data.synthese +
            // data.frais_impayes + data.pagination. `frais_impayes` reste
            // disponible a la racine pour les clients qui l'utilisaient.
            'frais_impayes' => FraisResource::collection(
                FraisApprenant::whereIn('apprenant_id', $apprenantIds)
                    ->where('annee_scolaire', $anneeScolaire)
                    ->where('statut', '!=', 'regle')
                    ->with('categorieFrais')
                    ->get()
            ),
            'classes'       => Apprenant::where('etablissement_id', $etablissementId)
                ->distinct()->orderBy('classe')->pluck('classe'),
        ]);
    }

    /**
     * Relance groupée à tous les parents ayant des impayés (par email).
     */
    public function relancerSms(Request $request): JsonResponse
    {
        $etablissementId = $this->autoriser();
        $anneeScolaire   = AnneeScolaire::active();

        // `categorieFrais` est lu dans l'envoi des relances : sans eager
        // loading, une requete par ligne d'impaye.
        $fraisImpayes = FraisApprenant::with(['apprenant.parents', 'categorieFrais'])
            ->where('annee_scolaire', $anneeScolaire)
            ->where('statut', '!=', 'regle')
            ->whereHas('apprenant', fn ($q) => $q->where('etablissement_id', $etablissementId))
            ->get();

        [$nbEnvoyes, $nbEchecs, $nbIgnores] = $this->envoyerRelancesGroupe($fraisImpayes, $etablissementId, $request->boolean('force'));

        Log::channel('admin')->info("E07 relance groupée — Étab #{$etablissementId} (API) : {$nbEnvoyes} emails envoyés, {$nbEchecs} échecs, {$nbIgnores} ignorés (anti-spam).");

        // 429 : une relance a deja ete envoyee aux memes parents dans les
        // dernieres heures. `force=true` permet de repasser outre.
        if ($nbEnvoyes === 0 && $nbIgnores > 0) {
            return response()->json([
                'message' => 'Une relance a déjà été envoyée à ces parents dans les ' . RelanceImpaye::DELAI_ANTI_SPAM_H . ' dernières heures. Utilisez force=true pour la renvoyer quand même.',
                'envoi'   => [
                    'envoyes' => 0,
                    'echecs'  => $nbEchecs,
                    'ignores' => $nbIgnores,
                ],
            ], 429);
        }

        return response()->json([
            'message'   => $nbEnvoyes > 0
                ? "{$nbEnvoyes} relance(s) envoyée(s) par email." . ($nbEchecs > 0 ? " ({$nbEchecs} échec(s))" : '') . ($nbIgnores > 0 ? " ({$nbIgnores} déjà relancés recently)." : '')
                : 'Aucun email envoyé. Vérifiez les adresses email et les préférences de notification.',
            'envoi'     => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
                'ignores' => $nbIgnores,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    /**
     * Relance pour un apprenant précis (par email).
     */
    public function relancerApprenant(Request $request, Apprenant $apprenant): JsonResponse
    {
        $etablissementId = $this->autoriser();

        if ($apprenant->etablissement_id !== $etablissementId) {
            return response()->json(['message' => 'Accès non autorisé à cet apprenant.'], 403);
        }

        $fraisImpayes = FraisApprenant::with(['apprenant.parents', 'categorieFrais'])
            ->where('apprenant_id', $apprenant->id)
            ->where('annee_scolaire', AnneeScolaire::active($apprenant->etablissement))
            ->where('statut', '!=', 'regle')
            ->get();

        [$nbEnvoyes, $nbEchecs, $nbIgnores] = $this->envoyerRelancesGroupe($fraisImpayes, $etablissementId, $request->boolean('force'));

        if ($nbEnvoyes === 0 && $nbIgnores > 0) {
            return response()->json([
                'message' => 'Ce parent a déjà reçu une relance dans les ' . RelanceImpaye::DELAI_ANTI_SPAM_H . ' dernières heures. Utilisez force=true pour la renvoyer quand même.',
                'envoi'   => [
                    'envoyes' => 0,
                    'echecs'  => $nbEchecs,
                    'ignores' => $nbIgnores,
                ],
            ], 429);
        }

        return response()->json([
            'message' => $nbEnvoyes > 0
                ? "Relance envoyée à {$apprenant->prenom} {$apprenant->nom}."
                : 'Échec — vérifiez l\'email du parent ou ses préférences de notification.',
            'envoi'   => [
                'envoyes' => $nbEnvoyes,
                'echecs'  => $nbEchecs,
                'ignores' => $nbIgnores,
            ],
        ], $nbEnvoyes > 0 ? 200 : 422);
    }

    /**
     * Envoie les relances et trace chaque tentative.
     *
     * U : avant, l'endpoint pouvait etre appele en boucle et le meme parent
     * recevait la meme relance autant de fois que vouloit (aucune trace, aucun
     * delai). Chaque envoi est maintenant enregistre dans `relances_impayes`,
     * et un couple (ligne de frais, parent) n'est pas recontacte avant
     * `RelanceImpaye::DELAI_ANTI_SPAM_H` heures, sauf `force=true`.
     *
     * @return array{0:int,1:int,2:int} [envoyes, echecs, ignores]
     */
    private function envoyerRelancesGroupe($fraisCollection, int $etablissementId, bool $force = false): array
    {
        $nbEnvoyes  = 0;
        $nbEchecs   = 0;
        $nbIgnores  = 0;

        foreach ($fraisCollection as $frais) {
            $reste = $frais->montant_total - $frais->montant_paye;
            if ($reste <= 0) {
                continue;
            }

            foreach ($frais->apprenant->parents as $parent) {
                if (! $force && RelanceImpaye::envoyeeRecently($frais->id, $parent->id)) {
                    $nbIgnores++;
                    RelanceImpaye::create([
                        'frais_apprenant_id' => $frais->id,
                        'apprenant_id'       => $frais->apprenant_id,
                        'etablissement_id'   => $etablissementId,
                        'user_id'            => $parent->id,
                        'canal'              => 'email',
                        'statut'             => 'ignore',
                        'erreur'             => 'Delai anti-spam : relance deja envoyee dans les ' . RelanceImpaye::DELAI_ANTI_SPAM_H . ' dernieres heures.',
                    ]);
                    continue;
                }

                if (! $parent->email || ! $parent->notif_email) {
                    $nbEchecs++;
                    RelanceImpaye::create([
                        'frais_apprenant_id' => $frais->id,
                        'apprenant_id'       => $frais->apprenant_id,
                        'etablissement_id'   => $etablissementId,
                        'user_id'            => $parent->id,
                        'canal'              => 'email',
                        'statut'             => 'echec',
                        'erreur'             => $parent->email
                            ? 'Notifications email desactivees par le parent.'
                            : 'Aucune adresse email renseignee.',
                    ]);
                    continue;
                }

                try {
                    Mail::to($parent->email)->send(new RelanceImpayeMail(
                        $frais->apprenant,
                        $frais->categorieFrais->nom ?? 'frais scolaires',
                        (float) $reste,
                    ));
                    $nbEnvoyes++;
                    RelanceImpaye::create([
                        'frais_apprenant_id' => $frais->id,
                        'apprenant_id'       => $frais->apprenant_id,
                        'etablissement_id'   => $etablissementId,
                        'user_id'            => $parent->id,
                        'canal'              => 'email',
                        'statut'             => 'envoye',
                    ]);
                } catch (\Throwable $e) {
                    $nbEchecs++;
                    RelanceImpaye::create([
                        'frais_apprenant_id' => $frais->id,
                        'apprenant_id'       => $frais->apprenant_id,
                        'etablissement_id'   => $etablissementId,
                        'user_id'            => $parent->id,
                        'canal'              => 'email',
                        'statut'             => 'echec',
                        'erreur'             => mb_substr($e->getMessage(), 0, 500),
                    ]);
                    Log::channel('admin')->error('E07 échec envoi relance email à ' . $parent->email . ' : ' . $e->getMessage());
                }
            }
        }

        return [$nbEnvoyes, $nbEchecs, $nbIgnores];
    }

    private function autoriser(): int
    {
        $user = auth()->user();

        if (! $user->hasAnyRole(self::ROLES_ETABLISSEMENT) || ! $user->etablissement_id) {
            abort(403, 'Ce compte n\'a pas accès au back-office établissement.');
        }

        return $user->etablissement_id;
    }
}
