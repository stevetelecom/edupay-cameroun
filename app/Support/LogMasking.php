<?php

namespace App\Support;

/**
 * Masquage de donnees personnelles avant journalisation (M-04 audit).
 */
class LogMasking
{
    public static function telephone(?string $numero): ?string
    {
        if (! $numero) {
            return $numero;
        }
        $numero = (string) $numero;
        if (strlen($numero) <= 6) {
            return str_repeat('*', strlen($numero));
        }
        return substr($numero, 0, 6) . '***' . substr($numero, -3);
    }

    public static function email(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return $email;
        }
        [$local, $domaine] = explode('@', $email, 2);
        $visible = substr($local, 0, 2);
        return $visible . '***@' . $domaine;
    }

    public static function payloadReduit(array $data): array
    {
        $champsUtiles = ['status', 'transaction_id', 'pay_token', 'payToken', 'message', 'operator', 'currency', 'amount'];
        $reduit = array_intersect_key($data, array_flip($champsUtiles));

        if (isset($data['phone_number'])) {
            $reduit['phone_number'] = self::telephone((string) $data['phone_number']);
        }
        if (isset($data['data']) && is_array($data['data'])) {
            $reduit['data'] = self::payloadReduit($data['data']);
        }
        if (isset($data['details']) && is_array($data['details'])) {
            $reduit['details'] = ['reason' => $data['details']['reason'] ?? null];
        }

        return $reduit;
    }
}
