<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ParametreSysteme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class ParametreSystemeController extends Controller
{
    /**
     * Couts et taux qui pilotent l'argent des paiements. Les frais visibles
     * preleves au payeur valent (taux_aangaraa + marge_edupay) du montant des
     * frais de scolarite : c'est ce qui couvre le reversement au prestataire
     * et laisse la marge d'EduPay sur le compte AangaraaPay.
     */
    public const TAUX_AANGARAA_DEFAUT = \App\Services\AangaraaPayService::TAUX_AANGARAA_DEFAUT;

    public const MARGE_EDUPAY_DEFAUT   = \App\Services\AangaraaPayService::MARGE_EDUPAY_DEFAUT;

    public function index()
    {
        $tauxAangaraa = (float) ParametreSysteme::obtenir('taux_aangaraa', self::TAUX_AANGARAA_DEFAUT);
        $margeEdupay  = (float) ParametreSysteme::obtenir('marge_edupay', self::MARGE_EDUPAY_DEFAUT);

        $parametres = [
            'taux_aangaraa'    => $tauxAangaraa,
            'marge_edupay'     => $margeEdupay,
            'taux_commission'  => $tauxAangaraa + $margeEdupay,
            'timeout_paiement' => (int) ParametreSysteme::obtenir('timeout_paiement', 120),
            'max_tranches'     => (int) ParametreSysteme::obtenir('max_tranches', 3),
            'sms_actif'        => ParametreSysteme::obtenirBool('sms_actif', true),
            'maintenance'      => ParametreSysteme::obtenirBool('maintenance', false),
            'mtn_actif'        => ParametreSysteme::obtenirBool('mtn_actif', true),
            'orange_actif'     => ParametreSysteme::obtenirBool('orange_actif', true),
            'langue_defaut'    => ParametreSysteme::obtenir('langue_defaut', 'fr'),
            'aangaraa_api_url' => config('services.aangaraa.api_url', ''),
        ];

        $stats = [
            'version_laravel' => app()->version(),
            'version_php'     => PHP_VERSION,
            'env'             => config('app.env'),
            'cache_driver'    => config('cache.default'),
            'queue_driver'    => config('queue.default'),
            'db_driver'       => config('database.default'),
        ];

        return view('admin.parametres.index', compact('parametres', 'stats'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'taux_aangaraa'    => ['required', 'numeric', 'min:0', 'max:0.5'],
            'marge_edupay'     => ['required', 'numeric', 'min:0', 'max:0.1'],
            'timeout_paiement' => ['required', 'integer', 'min:30', 'max:600'],
            'max_tranches'     => ['required', 'integer', 'min:1', 'max:12'],
            'langue_defaut'    => ['required', 'in:fr,en'],
        ], [
            'taux_aangaraa.required'    => 'Le taux AangaraaPay est obligatoire.',
            'taux_aangaraa.max'         => 'Le taux AangaraaPay maximum est 50%.',
            'marge_edupay.required'     => 'La marge EduPay est obligatoire.',
            'marge_edupay.max'          => 'La marge EduPay maximum est 10%.',
            'timeout_paiement.required' => 'Le timeout est obligatoire.',
            'max_tranches.required'     => 'Le nombre de tranches est obligatoire.',
            'langue_defaut.in'          => 'Langue invalide (fr ou en uniquement).',
        ]);

        // Refus explicite : en dessous du cout du prestataire, chaque paiement
        // fait perdre de l'argent a la plateforme. Preferer une erreur lisible a
        // un deficit silencieux sur tous les encaissements.
        if ((float) $request->marge_edupay < 0) {
            return back()->withErrors(['marge_edupay' => 'La marge ne peut pas etre negative.'])->withInput();
        }

        $mtnActif    = $request->has('mtn_actif');
        $orangeActif = $request->has('orange_actif');

        if (! $mtnActif && ! $orangeActif) {
            return back()
                ->withErrors(['mtn_actif' => 'Au moins un mode de paiement (MTN ou Orange) doit rester actif.'])
                ->withInput();
        }

        ParametreSysteme::definir([
            'taux_aangaraa'    => $request->taux_aangaraa,
            'marge_edupay'     => $request->marge_edupay,
            'timeout_paiement' => $request->timeout_paiement,
            'max_tranches'     => $request->max_tranches,
            'sms_actif'        => $request->has('sms_actif') ? '1' : '0',
            'maintenance'      => $request->input('maintenance', '0') === '1' ? '1' : '0',
            'mtn_actif'        => $mtnActif ? '1' : '0',
            'orange_actif'     => $orangeActif ? '1' : '0',
            'langue_defaut'    => $request->langue_defaut,
        ]);

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PARAMETRES_MODIFIES',
            'Parametres systeme mis a jour : taux_aangaraa=' . $request->taux_aangaraa
                . ', marge_edupay=' . $request->marge_edupay
                . ', taux_frais_total=' . ((float) $request->taux_aangaraa + (float) $request->marge_edupay)
                . ', timeout=' . $request->timeout_paiement
                . ', max_tranches=' . $request->max_tranches
                . ', mtn=' . ($mtnActif ? 'on' : 'off')
                . ', orange=' . ($orangeActif ? 'on' : 'off')
                . ', langue=' . $request->langue_defaut,
            $request,
            'WARNING'
        );

        return back()->with('success', 'Parametres systeme mis a jour avec succes.');
    }

    public function viderCache(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'CACHE_VIDE',
            'Cache application vide par le Super Admin',
            $request,
            'INFO'
        );

        return back()->with('success', 'Cache vide avec succes.');
    }
}
