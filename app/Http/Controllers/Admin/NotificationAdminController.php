<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * File de notifications du Super Admin (cloche du header).
 *
 * Le compteur de la cloche vit dans AdminSidebarComposer, pas ici : le layout
 * admin est partage par tous les controleurs de l'espace.
 */
class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $notifications = NotificationAdmin::query()
            ->with('reclamation')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $nonLues = NotificationAdmin::where('admin_id', $request->user()->id)
            ->whereNull('lu_at')
            ->count();

        return view('admin.notifications.index', compact('notifications', 'nonLues'));
    }

    /**
     * Marquer une notification comme lue. Un ticket lié est marqué lu en
     * même temps : c'est le même geste, et laisser la pastille rouge sur un
     * ticket deja traite dans la liste des reclamations est trompeur.
     */
    public function lu(Request $request, NotificationAdmin $notification): RedirectResponse
    {
        if ($notification->admin_id !== $request->user()->id) {
            abort(403, __('admin.acces_refuse'));
        }

        if (! $notification->lu_at) {
            $notification->update(['lu_at' => now()]);

            if ($notification->reclamation && $notification->reclamation->statut === 'ouvert') {
                $notification->reclamation->update(['statut' => 'en_cours']);
            }
        }

        return back();
    }

    /**
     * Tout passer en lu d'un geste. Le payload est volontairement filtre par
     * `admin_id` : sans cela, un administrateur pourrait lire les notifications
     * de ses collègues en postant des identifiants arbitraires.
     */
    public function toutLu(Request $request): RedirectResponse
    {
        NotificationAdmin::where('admin_id', $request->user()->id)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);

        return back();
    }
}
