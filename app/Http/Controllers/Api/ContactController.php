<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Formulaire de contact (équivalent API de Public\LandingController::submitContact).
     * Envoie un message au support EduPay (ContactMessageMail), avec les mêmes
     * validations et le même comportement que le web.
     */
    public function submit(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'phone'   => ['required', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        Log::info('API : nouveau message de contact', [
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'],
            'subject' => $data['subject'],
            'message_length' => strlen($data['message']),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $recipientEmail = config('mail.contact_address', config('mail.from.address'));

            Mail::to($recipientEmail)
                ->send(new ContactMessageMail($data));

            Log::info('API : email de contact envoyé avec succès', [
                'from' => $data['email'],
                'to'   => $recipientEmail,
            ]);

            return response()->json([
                'message' => 'Votre message a bien été envoyé. Nous reviendrons vers vous rapidement.',
            ]);
        } catch (\Throwable $exception) {
            Log::error('API : erreur lors de l\'envoi du message de contact', [
                'email' => $data['email'],
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Impossible d\'envoyer votre message pour le moment. Veuillez réessayer ultérieurement.',
            ], 500);
        }
    }
}
