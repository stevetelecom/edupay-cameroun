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
        // `reference_id` et `statusCode` ont ete ajoutes le 30/09/2026 : sans
        // eux, la journalisation du retrait AangaraaPay supprimait precisement
        // les deux champs qui permettent de reconcilier un virement. Le log
        // affichait `{"message":"Payment request successful"}` et laissait
        // croire que la reponse ne comportait aucune reference, alors que le
        // masquage venait de la retirer. Diagnostic Impossible a partir des
        // logs, et plusieurs hypotheses fausses emises en consequence.
        $champsUtiles = [
            'status', 'statusCode', 'status_code',
            'transaction_id', 'transactionId', 'reference_id', 'reference',
            'pay_token', 'payToken', 'message', 'operator', 'currency', 'amount',
        ];
        $reduit = array_intersect_key($data, array_flip($champsUtiles));

        if (isset($data['phone_number'])) {
            $reduit['phone_number'] = self::telephone((string) $data['phone_number']);
        }
        if (isset($data['data']) && is_array($data['data'])) {
            $reduit['data'] = self::payloadReduit($data['data']);
        }
        if (isset($data['details']) && is_array($data['details'])) {
            $reduit['details'] = [
                'reason' => $data['details']['reason'] ?? null,
                'financialTransactionId' => $data['details']['financialTransactionId'] ?? null,
            ];
        }

        return $reduit;
    }

    /**
     * Liste des CLES presentes dans une reponse, sans aucune valeur.
     *
     * Integre a la journalisation : meme si le masquage retire un champ, on
     * voit qu'il existait. C'est ce qui a manque pour comprendre la reponse
     * 201 de /withdrawal — le champ porteait un nom inconnu, donc ni extrait
     * ni journalise. Aucun risque : ce sont des noms de champs, pas des
     * donnees.
     */
    public static function clesReponse(array $data, string $prefixe = ''): array
    {
        $cles = [];

        foreach (array_keys($data) as $cle) {
            $chemin = $prefixe === '' ? (string) $cle : $prefixe . '.' . $cle;
            $cles[] = $chemin;

            if (is_array($data[$cle]) && $data[$cle] !== [] && ! array_is_list($data[$cle])) {
                $cles = array_merge($cles, self::clesReponse($data[$cle], $chemin));
            }
        }

        return $cles;
    }
}
