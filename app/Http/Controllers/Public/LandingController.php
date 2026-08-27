<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        // Stats réelles pour la landing page
        $stats = [
            'nb_etablissements' => \App\Models\Etablissement::where('statut', 'actif')->count(),
            'nb_apprenants'     => \App\Models\Apprenant::where('actif', true)->count(),
            'nb_paiements'      => \App\Models\Paiement::where('statut', 'valide')->count(),
            'montant_total'     => \App\Models\Paiement::where('statut', 'valide')->sum('montant'),
        ];

        // Liste des établissements actifs avec logo
        $etablissements = \App\Models\Etablissement::where('statut', 'actif')
            ->orderBy('nom')
            ->get(['id', 'nom', 'ville', 'type', 'logo', 'code_etablissement', 'region']);

        return view('public.landing', compact('stats', 'etablissements'));
    }

    public function about(): View
    {
        $stats = [
            'nb_etablissements' => \App\Models\Etablissement::where('statut', 'actif')->count(),
            'nb_apprenants'     => \App\Models\Apprenant::where('actif', true)->count(),
            'nb_paiements'      => \App\Models\Paiement::where('statut', 'valide')->count(),
            'montant_total'     => \App\Models\Paiement::where('statut', 'valide')->sum('montant'),
        ];
        return view('public.about', compact('stats'));
    }

    public function temoignages(): View
    {
        $stats = [
            'nb_etablissements' => \App\Models\Etablissement::where('statut', 'actif')->count(),
            'nb_apprenants'     => \App\Models\Apprenant::where('actif', true)->count(),
            'nb_paiements'      => \App\Models\Paiement::where('statut', 'valide')->count(),
            'montant_total'     => \App\Models\Paiement::where('statut', 'valide')->sum('montant'),
        ];
        return view('public.temoignages', compact('stats'));
    }

    public function etablissement(\App\Models\Etablissement $etablissement): View
    {
        $etablissement->load(['categoriesFrais' => function ($q) {
            $q->where('actif', true)->orderBy('nom');
        }]);

        $nbApprenants = \App\Models\Apprenant::where('etablissement_id', $etablissement->id)
            ->where('actif', true)
            ->count();

        return view('public.etablissement', compact('etablissement', 'nbApprenants'));
    }

    public function guide(): View
    {
        return view('public.guide');
    }

    public function confidentialite(): View
    {
        return view('public.confidentialite');
    }

    public function cgu(): View
    {
        return view('public.cgu');
    }

    public function support(): View
    {
        return view('public.support');
    }

    public function tarifs(): View
    {
        return view('public.tarifs', [
            'plans' => \App\Models\Abonnement::PLANS,
        ]);
    }

    public function contact(): View
    {
        return view('public.contact');
    }

    public function submitContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // 📝 Logging: Message reçu
        Log::info('📧 NOUVEAU MESSAGE DE CONTACT', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'subject' => $data['subject'],
            'message_length' => strlen($data['message']),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $recipientEmail = config('mail.contact_address', config('mail.from.address'));
            
            // 📝 Logging: Tentative d'envoi
            Log::info("📨 Envoi de l'email à: {$recipientEmail}", [
                'from' => $data['email'],
                'name' => $data['name'],
            ]);

            Mail::to($recipientEmail)
                ->send(new ContactMessageMail($data));

            // 📝 Logging: Succès
            Log::info("✅ Email de contact envoyé avec succès", [
                'from' => $data['email'],
                'to' => $recipientEmail,
            ]);

        } catch (\Throwable $exception) {
            // 📝 Logging: Erreur
            Log::error("❌ Erreur lors de l'envoi du message de contact", [
                'email' => $data['email'],
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return back()->withInput()->with('error', 'Impossible d\'envoyer votre message pour le moment. Veuillez réessayer ultérieurement.');
        }

        return redirect()->route('contact')->with('success', 'Votre message a bien été envoyé. Nous reviendrons vers vous rapidement.');
    }
}
