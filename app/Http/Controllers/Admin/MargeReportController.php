<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Services\AangaraaPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rapport de marge EduPay par periode.
 *
 * La marge n'existe nulle part ailleurs dans le back-office : le dashboard
 * montre un total du mois, la page commissions un compteur. Aucun ecran ne
 * permet de repondre a « combien ai-je gagne cette semaine », alors que c'est
 * la seule question qui compte pour le proprietaire.
 *
 * Deux agregats distincts, a ne pas confondre :
 *   frais_service = argent preleve au payeur (casse le modele actuel)
 *   frais_aangaraa = cout presume du prestataire
 *   marge_edupay  = ce qui reste sur le compte AangaraaPay
 */
class MargeReportController extends Controller
{
    /** Periodes proposees, du plus fin au plus large. */
    private const PERIODES = ['jour', 'semaine', 'mois', 'annee'];

    public function index(Request $request, AangaraaPayService $aangaraa)
    {
        $periode = in_array($request->input('periode'), self::PERIODES, true)
            ? $request->input('periode')
            : 'mois';

        [$debut, $fin] = $this->fenetre($periode, $request->input('date'));

        // Requete de base reconstruite a chaque agregat. Un `clone` heriterait
        // des colonnes deja ajoutees par le selectRaw precedent, et la
        // jointure paiements entretiendrait l'ambiguite de frais_aangaraa
        // (present dans les deux tables) que SQLite refuse. Chaque agregat
        // repart donc d'une query neuve.
        //
        // Colonnes prefixees : la jointure paiements ajoute des colonnes de
        // meme nom, et SQLite refuse l'ambiguite.
        $base = fn () => Commission::query()
            ->whereBetween('commissions.created_at', [$debut, $fin]);

        // L'agregat se fait en base et non en PHP : la fenetre peut couvrir
        // une annee entiere, charger toutes les lignes n'a pas de sens.
        $global = $base()
            ->selectRaw('COALESCE(SUM(commissions.montant_commission), 0) AS marge')
            ->selectRaw('COALESCE(SUM(commissions.frais_aangaraa), 0) AS cout')
            ->selectRaw('COALESCE(SUM(commissions.montant_transaction), 0) AS volume')
            ->selectRaw('COUNT(*) AS nb')
            ->first();

        $parStatut = $base()
            ->selectRaw('commissions.statut AS statut, COUNT(*) AS nb')
            ->selectRaw('COALESCE(SUM(commissions.montant_commission), 0) AS marge')
            ->groupBy('commissions.statut')
            ->get()
            ->keyBy('statut');

        $parOperateur = $base()
            ->join('paiements', 'paiements.id', '=', 'commissions.paiement_id')
            ->selectRaw('paiements.operateur AS operateur, COUNT(*) AS nb')
            ->selectRaw('COALESCE(SUM(commissions.montant_commission), 0) AS marge')
            ->selectRaw('COALESCE(SUM(paiements.frais_service), 0) AS frais_preleves')
            ->groupBy('paiements.operateur')
            ->orderByDesc('marge')
            ->get();

        $parEtablissement = $base()
            ->join('etablissements', 'etablissements.id', '=', 'commissions.etablissement_id')
            ->selectRaw('etablissements.nom AS nom, COUNT(*) AS nb')
            ->selectRaw('COALESCE(SUM(commissions.montant_commission), 0) AS marge')
            ->selectRaw('COALESCE(SUM(commissions.montant_transaction), 0) AS volume')
            ->groupBy('etablissements.nom')
            ->orderByDesc('marge')
            ->limit(15)
            ->get();

        // Les frais reellement preleves au payeur viennent de paiements, pas
        // de commissions : c'est la seule source qui reflects ce qu'un parent
        // a debite, frais de service compris.
        $fraisPreleves = (float) DB::table('paiements')
            ->where('statut', 'valide')
            ->whereBetween('date_paiement', [$debut, $fin])
            ->sum('frais_service');

        $totalPaye = (float) DB::table('paiements')
            ->where('statut', 'valide')
            ->whereBetween('date_paiement', [$debut, $fin])
            ->sum('montant');

        // Solde reel chez le prestataire, seul chiffre opposable.
        $solde = $aangaraa->solde();

        // Libelles traduits ici : une cle concatenee dans la vue echappe au
        // controle de traductions et s'affiche en clair si la cle manque.
        $libellesStatut = [
            Commission::STATUT_CALCULEE   => __('admin.statut_calculee'),
            Commission::STATUT_EN_COURS   => __('admin.statut_en_cours'),
            Commission::STATUT_PRELEVEE   => __('admin.statut_prelevee'),
            Commission::STATUT_A_VERIFIER => __('admin.statut_a_verifier'),
            Commission::STATUT_ECHEC      => __('admin.statut_echec'),
        ];

        return view('admin.marge.index', [
            'libellesStatut'   => $libellesStatut,
            'periode'         => $periode,
            'debut'           => $debut,
            'fin'             => $fin,
            'global'          => $global,
            'parStatut'       => $parStatut,
            'parOperateur'    => $parOperateur,
            'parEtablissement' => $parEtablissement,
            'fraisPreleves'   => $fraisPreleves,
            'totalPaye'       => $totalPaye,
            'solde'           => $solde,
            'tauxAangaraa'    => $aangaraa->tauxAangaraa(),
            'margeEdupay'     => $aangaraa->margeEdupay(),
        ]);
    }

    /**
     * Fenetre [debut, fin] pour la periode demandee.
     * Le 31 decembre d'une annee bissextile ne doit pas deborder sur janvier.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function fenetre(string $periode, ?string $date): array
    {
        $ancre = $date ? Carbon::parse($date) : Carbon::now();

        return match ($periode) {
            'jour'    => [$ancre->copy()->startOfDay(), $ancre->copy()->endOfDay()],
            'semaine' => [$ancre->copy()->startOfWeek(), $ancre->copy()->endOfWeek()],
            'annee'   => [$ancre->copy()->startOfYear(), $ancre->copy()->endOfYear()],
            default   => [$ancre->copy()->startOfMonth(), $ancre->copy()->endOfMonth()],
        };
    }
}
