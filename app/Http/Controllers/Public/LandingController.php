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
        return view('public.landing');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function temoignages(): View
    {
        return view('public.temoignages');
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
            $recipientEmail = config('mail.from.address', 'support@edupay.cm');
            
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
