<?php

namespace Tests\Unit;

use App\Support\LogMasking;
use Tests\TestCase;

/**
 * Le masquage des logs a deja coute un diagnostic entier le 30/09/2026 :
 * `reference_id` et `statusCode` etaient retires de la journalisation, si bien
 * que la reponse 201 de /withdrawal paraissait ne contenir aucun identifiant.
 * Ces tests verrouillent le contrat : le masquage ne doit jamais supprimer un
 * champ de reconciliation, et les noms de champs doivent rester auditables.
 */
class LogMaskingTest extends TestCase
{
    public function test_reference_id_et_statuscode_survivent_au_masquage(): void
    {
        $reduit = LogMasking::payloadReduit([
            'statusCode'  => 201,
            'message'     => 'Payment request successful',
            'data'        => [
                'status'       => 'SUCCESSFUL',
                'reference_id' => 'abc123def456',
            ],
        ]);

        $this->assertSame(201, $reduit['statusCode'] ?? null);
        $this->assertSame('abc123def456', $reduit['data']['reference_id'] ?? null);
        $this->assertSame('SUCCESSFUL', $reduit['data']['status'] ?? null);
    }

    public function test_cles_reponse_revele_un_champ_inconnu_non_demasque(): void
    {
        // Cas reel du 30/09 : un champ porte par un nom que le masquage et
        // l'extraction ne connaissent pas. Sans la liste des cles, ce champ
        // restait totalement invisible dans les logs.
        $cles = LogMasking::clesReponse([
            'message'    => 'Payment request successful',
            'data'       => [
                'withdrawal_ref' => 'W-99887',
            ],
        ]);

        $this->assertContains('data.withdrawal_ref', $cles);
        $this->assertContains('message', $cles);
    }

    public function test_cles_reponse_ne_renvoie_que_des_noms_pas_de_valeurs(): void
    {
        $cles = LogMasking::clesReponse([
            'phone_number' => '237693000200',
            'app_key'      => 'SECRET-APP-KEY',
        ]);

        foreach ($cles as $cle) {
            $this->assertStringNotContainsString('237693000200', $cle);
            $this->assertStringNotContainsString('SECRET-APP-KEY', $cle);
        }
    }

    public function test_le_telephone_reste_masque_dans_le_payload(): void
    {
        $reduit = LogMasking::payloadReduit([
            'phone_number' => '237693000200',
        ]);

        $this->assertSame('237693***200', $reduit['phone_number']);
    }
}
