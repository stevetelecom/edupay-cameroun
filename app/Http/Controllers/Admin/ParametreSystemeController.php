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
    public function index()
    {
        $parametres = [
            'taux_commission'  => (float) ParametreSysteme::obtenir('taux_commission', 0.025),
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
            'taux_commission'  => ['required', 'numeric', 'min:0', 'max:0.1'],
            'timeout_paiement' => ['required', 'integer', 'min:30', 'max:600'],
            'max_tranches'     => ['required', 'integer', 'min:1', 'max:12'],
            'langue_defaut'    => ['required', 'in:fr,en'],
        ], [
            'taux_commission.required'  => 'Le taux est obligatoire.',
            'taux_commission.max'       => 'Le taux maximum est 10%.',
            'timeout_paiement.required' => 'Le timeout est obligatoire.',
            'max_tranches.required'     => 'Le nombre de tranches est obligatoire.',
            'langue_defaut.in'          => 'Langue invalide (fr ou en uniquement).',
        ]);

        $mtnActif    = $request->has('mtn_actif');
        $orangeActif = $request->has('orange_actif');

        if (! $mtnActif && ! $orangeActif) {
            return back()
                ->withErrors(['mtn_actif' => 'Au moins un mode de paiement (MTN ou Orange) doit rester actif.'])
                ->withInput();
        }

        ParametreSysteme::definir([
            'taux_commission'  => $request->taux_commission,
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
            'Parametres systeme mis a jour : taux=' . $request->taux_commission
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
