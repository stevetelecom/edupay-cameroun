<?php

namespace App\Services;

use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Facades\Log;

class SmsService
{
    private $sms;

    public function __construct()
    {
        $AT  = new AfricasTalking(
            config('services.africastalking.username'),
            config('services.africastalking.api_key')
        );
        $this->sms = $AT->sms();
    }

    /**
     * Envoie un code OTP 2FA (Super Admin).
     */
    public function envoyerOtp(string $telephone, string $code): bool
    {
        return $this->envoyer(
            $telephone,
            "EduPay Cameroun - Code de verification : $code\nValable 5 minutes. Ne le partagez jamais."
        );
    }

    /**
     * Envoie un SMS de relance pour impayé (back-office établissement).
     */
    public function envoyerRelance(string $telephone, string $message): bool
    {
        return $this->envoyer($telephone, $message);
    }

    /**
     * Envoi générique — utilisé par envoyerOtp() et envoyerRelance().
     */
    private function envoyer(string $telephone, string $message): bool
    {
        try {
            $numero = $this->normaliserNumero($telephone);

            $result = $this->sms->send([
                'to'      => $numero,
                'message' => $message,
                'from'    => config('services.africastalking.sender_id') ?: null,
            ]);

            Log::channel('admin')->info('SMS envoyé à ' . $numero, [
                'status' => $result['data']['SMSMessageData']['Recipients'][0]['status'] ?? 'unknown',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('admin')->error('Échec envoi SMS : ' . $e->getMessage());
            return false;
        }
    }

    private function normaliserNumero(string $tel): string
    {
        $tel = preg_replace('/[\s\-]/', '', $tel);

        if (str_starts_with($tel, '+')) {
            return $tel;
        }

        if (str_starts_with($tel, '6') && strlen($tel) === 9) {
            return '+237' . $tel;
        }

        if (str_starts_with($tel, '237')) {
            return '+' . $tel;
        }

        return '+237' . $tel;
    }
}
