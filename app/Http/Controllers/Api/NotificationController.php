<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\NotificationPayeur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = NotificationPayeur::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'data'       => NotificationResource::collection($notifications),
            'non_lues'   => $notifications->whereNull('lu_at')->count(),
        ]);
    }

    /**
     * Marque une notification comme lue.
     */
    public function lire(Request $request): JsonResponse
    {
        $ids = (array) $request->input('ids', []);

        if (empty($ids)) {
            // Marquer toutes comme lues
            NotificationPayeur::where('user_id', $request->user()->id)
                ->whereNull('lu_at')
                ->update(['lu_at' => now()]);

            return response()->json(['message' => 'Notifications marquées comme lues.']);
        }

        $nb = NotificationPayeur::where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->whereNull('lu_at')
            ->update(['lu_at' => now()]);

        return response()->json([
            'message' => 'Notifications marquées comme lues.',
            'marquees' => $nb,
        ]);
    }
}
