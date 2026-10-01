<?php

namespace Tests\Unit;

use App\Services\AangaraaPayService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Detection de l'operateur a partir du numero de telephone.
 *
 * Le navigateur fait la meme detection et envoie l'operateur choisi dans
 * `mode_paiement` : le paiement s'initie donc correctement pour Orange comme
 * pour MTN. Ces cas figent les prefixes confirmes pour 2026, afin qu'un
 * ajustement du service ne casse pas un encaissement qui fonctionne.
 */
class AangaraaOperateurTest extends TestCase
{
    #[DataProvider('prefixes')]
    public function test_le_prefixe_determine_l_operateur(string $numero, string $attendu): void
    {
        $this->assertSame($attendu, (new AangaraaPayService())->detecterOperateur($numero));
    }

    public static function prefixes(): array
    {
        return [
            // MTN MoMo : 650-654 et 670-679
            'MTN 650'        => ['650000000', 'MTN_Cameroon'],
            'MTN 654'        => ['654000000', 'MTN_Cameroon'],
            'MTN 670'        => ['670000000', 'MTN_Cameroon'],
            'MTN 679'        => ['679000000', 'MTN_Cameroon'],
            'MTN +237'       => ['+237650000000', 'MTN_Cameroon'],
            'MTN 00237'      => ['00237650000000', 'MTN_Cameroon'],
            'MTN espaces'    => ['650 00 00 00', 'MTN_Cameroon'],

            // Orange Money : plages documentees par AangaraaPay, confirmes en prod
            'Orange 655'     => ['655000000', 'Orange_Cameroon'],
            'Orange 659'     => ['659000000', 'Orange_Cameroon'],
            'Orange 690'     => ['690000000', 'Orange_Cameroon'],
            'Orange 699'     => ['699000000', 'Orange_Cameroon'],
            'Orange +237'    => ['+237655000000', 'Orange_Cameroon'],

            // Orange Money : plages du plan ART, absentes de la doc AangaraaPay.
            // 688 echouait en detection avant ce correctif : le numero est
            // Orange, la page de paiement laissait le selecteur sur MTN.
            'Orange 640'     => ['640000000', 'Orange_Cameroon'],
            'Orange 686'     => ['686000000', 'Orange_Cameroon'],
            'Orange 687'     => ['687000000', 'Orange_Cameroon'],
            'Orange 688'     => ['688000000', 'Orange_Cameroon'],
            'Orange 689'     => ['689000000', 'Orange_Cameroon'],
            'Orange 688 +237'=> ['+237688123456', 'Orange_Cameroon'],

            // 680-683 : Nexttel / Viettel, ni MTN ni Orange.
            'Nexttel 680'    => ['680000000', 'ALL'],
            'Nexttel 683'    => ['683000000', 'ALL'],

            // 685 : hors attribution Orange (ART 86-87 et 88 uniquement).
            'libre 685'      => ['685000000', 'ALL'],

            // Prefixe inconnu : on ne devine pas, l'utilisateur choisit.
            'prefixe libre'  => ['222000000', 'ALL'],
        ];
    }

    public function test_chaque_operateur_est_bien_detecte_de_puis_les_deux_cotes(): void
    {
        $service = new AangaraaPayService();

        $this->assertSame('MTN_Cameroon', $service->detecterOperateur('671234567'));
        $this->assertSame('Orange_Cameroon', $service->detecterOperateur('691234567'));
    }
}
