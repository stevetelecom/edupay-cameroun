<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;

class ParametreSystemeController extends Controller
{
    public function index()
    {
        $parametres = [
            'taux_commission'     => config('services.edupay.taux_commission', 0.025),
            'timeout_paiement'    => config('services.edupay.timeout_paiement', 120),
            'sms_actif'           => config('services.edupay.sms_actif', true),
            'maintenance'         => config('services.edupay.maintenance', false),
            'max_tranches'        => config('services.edupay.max_tranches', 3),
            'aangaraa_api_url'    => config('services.aangaraa.api_url', env('AANGARAA_API_URL', '')),
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
        ], [
            'taux_commission.required'  => 'Le taux est obligatoire.',
            'taux_commission.max'       => 'Le taux maximum est 10%.',
            'timeout_paiement.required' => 'Le timeout est obligatoire.',
            'max_tranches.required'     => 'Le nombre de tranches est obligatoire.',
        ]);

        // Mettre a jour le .env
        $this->updateEnv([
            'EDUPAY_TAUX_COMMISSION'  => $request->taux_commission,
            'EDUPAY_TIMEOUT_PAIEMENT' => $request->timeout_paiement,
            'EDUPAY_MAX_TRANCHES'     => $request->max_tranches,
            'EDUPAY_SMS_ACTIF'        => $request->has('sms_actif') ? 'true' : 'false',
            'EDUPAY_MAINTENANCE'      => $request->has('maintenance') ? 'true' : 'false',
        ]);

        // Vider le cache de config
        Artisan::call('config:clear');

        AuditLog::enregistrer(
            Auth::guard('admin')->user(),
            'PARAMETRES_MODIFIES',
            'Parametres systeme mis a jour : taux=' . $request->taux_commission
                . ', timeout=' . $request->timeout_paiement
                . ', max_tranches=' . $request->max_tranches,
            $request,
            'WARNING'
        );

        return back()->with('success', 'Parametres systeme mis a jour avec succes.');
    }

    public function viderCache(Request $request)
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
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

    private function updateEnv(array $values)
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "
{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
