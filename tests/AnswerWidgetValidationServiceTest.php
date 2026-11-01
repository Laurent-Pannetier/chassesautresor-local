<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\AnswerWidgetValidationService;
use PHPUnit\Framework\TestCase;

final class AnswerWidgetValidationServiceTest extends TestCase {
    private AnswerWidgetValidationService $service;

    protected function setUp(): void {
        $this->service = new AnswerWidgetValidationService();
    }

    public function testNormalizesValidTextAnswersBeforePersistence(): void {
        $configuration = $this->service->validate($this->configuration([
            'widget' => 'text',
            'accepted_answers' => " étoile \n\n astre ",
        ]));

        self::assertIsArray($configuration);
        self::assertSame("étoile\nastre", $configuration['accepted_answers']);
    }

    /** @dataProvider invalidConfigurationProvider */
    public function testRejectsInvalidOrEmptyWidgetConfiguration(array $changes): void {
        self::assertInstanceOf(WP_Error::class, $this->service->validate($this->configuration($changes)));
    }

    /** @dataProvider issueMessageProvider */
    public function testExplainsWhyAnAnswerConfigurationIsIncomplete(array $changes, string $message): void {
        self::assertSame($message, $this->service->issueMessage($this->configuration($changes)));
        $result = $this->service->validate($this->configuration($changes));
        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame($message, $result->get_error_message());
    }

    public function issueMessageProvider(): array {
        return [
            'empty text answers' => [
                ['widget' => 'text', 'accepted_answers' => ''],
                'Indiquez au moins une réponse acceptée, une par ligne.',
            ],
            'incomplete variant' => [
                ['widget' => 'text', 'accepted_answers' => 'étoile', 'variants' => 'astre |'],
                'Chaque variante doit contenir une réponse et un message, séparés par |.',
            ],
            'empty direction code' => [
                ['widget' => 'directions', 'direction_sequences' => ''],
                'Indiquez au moins un code accepté, un par ligne.',
            ],
            'invalid direction code' => [
                ['widget' => 'directions', 'direction_sequences' => 'N,UP,E'],
                'Un code ne respecte pas le format attendu pour ce mode de réponse.',
            ],
            'empty click label' => [
                ['widget' => 'click', 'button_label' => ''],
                'Indiquez le libellé du bouton.',
            ],
        ];
    }

    public function invalidConfigurationProvider(): array {
        return [
            'unknown widget' => [['widget' => 'unknown']],
            'empty click label' => [['widget' => 'click', 'button_label' => '']],
            'empty text answers' => [['widget' => 'text', 'accepted_answers' => '']],
            'incomplete text variant' => [[
                'widget' => 'text',
                'accepted_answers' => 'étoile',
                'variants' => 'astre |',
            ]],
            'unknown direction' => [['widget' => 'directions', 'direction_sequences' => 'N,UP,E']],
            'unknown color' => [['widget' => 'colors', 'color_sequences' => 'red,turquoise']],
            'non-numeric code' => [['widget' => 'numbers', 'number_sequences' => '12A3']],
            'invalid safe value' => [['widget' => 'safe_dial', 'safe_dial_sequences' => 'H100 A12']],
            'invalid safe direction' => [['widget' => 'safe_dial', 'safe_dial_sequences' => 'D11 A12']],
            'invalid piano note' => [['widget' => 'piano', 'piano_sequences' => 'C1 H2']],
            'invalid GPS latitude' => [['widget' => 'gps', 'gps_coordinates' => '91 2', 'gps_tolerance' => '25']],
            'invalid GPS tolerance' => [['widget' => 'gps', 'gps_coordinates' => '48 2', 'gps_tolerance' => '0']],
        ];
    }

    /** @dataProvider validSequenceProvider */
    public function testAcceptsEverySupportedSequenceWidget(string $widget, string $field, string $sequence): void {
        $configuration = $this->service->validate($this->configuration([
            'widget' => $widget,
            $field => $sequence,
        ]));

        self::assertIsArray($configuration);
        self::assertSame($sequence, $configuration[$field]);
    }

    public function validSequenceProvider(): array {
        return [
            'French directions' => ['directions', 'direction_sequences', 'N, NE, SO, O, NO'],
            'colors' => ['colors', 'color_sequences', 'red, pink, black'],
            'leading zeroes' => ['numbers', 'number_sequences', '00129'],
            'single zero' => ['numbers', 'number_sequences', '0'],
            'double zero' => ['numbers', 'number_sequences', '00'],
            'safe dial' => ['safe_dial', 'safe_dial_sequences', 'H11 A51'],
            'piano' => ['piano', 'piano_sequences', 'F1 F#2 B2 C1'],
        ];
    }

    private function configuration(array $changes): array {
        return array_merge([
            'widget' => 'click',
            'button_label' => 'Continuer',
            'accepted_answers' => '',
            'case_sensitive' => 0,
            'variants' => '',
            'direction_sequences' => '',
            'color_sequences' => '',
            'number_sequences' => '',
            'safe_dial_sequences' => '',
            'piano_sequences' => '',
            'gps_coordinates' => '',
            'gps_tolerance' => '',
        ], $changes);
    }

    public function testKeepsZeroWhenItIsTheOnlyAcceptedNumericCode(): void {
        $configuration = $this->service->validate($this->configuration([
            'widget' => 'numbers',
            'number_sequences' => "123\n0\n\n00\n",
        ]));

        self::assertIsArray($configuration);
        self::assertSame("123\n0\n00", $configuration['number_sequences']);
    }

    public function testKeepsTextAnswerEqualToZero(): void {
        $configuration = $this->service->validate($this->configuration([
            'widget' => 'text',
            'accepted_answers' => "0\n00",
        ]));

        self::assertIsArray($configuration);
        self::assertSame("0\n00", $configuration['accepted_answers']);
    }

    public function testAcceptsGpsCoordinatesAndTolerance(): void {
        $configuration = $this->service->validate($this->configuration([
            'widget' => 'gps',
            'gps_coordinates' => '48.85837 2.29448',
            'gps_tolerance' => '25',
        ]));

        self::assertIsArray($configuration);
        self::assertSame('48.85837 2.29448', $configuration['gps_coordinates']);
        self::assertSame('25', $configuration['gps_tolerance']);
    }
}
