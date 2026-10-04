<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\AnswerWidgetConfigurationService;
use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use PHPUnit\Framework\TestCase;

final class AnswerWidgetConfigurationAnswersStub extends RiddleAnswerService {
    public function get(int $riddleId): array {
        return ['final answer'];
    }
}

final class AnswerWidgetConfigurationServiceTest extends TestCase {
    public function testAdaptsStepTextFieldsToVersionedConfiguration(): void {
        $fields = [
            'etape_reponse_widget' => 'text',
            'etape_reponses_texte' => "Étoile\nLune",
            'etape_reponse_casse' => false,
            'etape_reponses_variantes' => 'astre | Vous êtes proche',
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forStep(
            12,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame(1, $configuration['version']);
        self::assertSame('enigme_etape', $configuration['target_type']);
        self::assertSame(12, $configuration['target_id']);
        self::assertSame(['Étoile', 'Lune'], $configuration['accepted_answers']);
        self::assertSame('Vous êtes proche', $configuration['variants'][1]['message']);
    }

    public function testAdaptsLegacyRiddleFieldsToTheSameConfiguration(): void {
        $fields = [
            'enigme_reponse_casse' => 1,
            'texte_1' => 'almost',
            'message_1' => 'Close',
            'respecter_casse_1' => 1,
        ];
        $configuration = (new AnswerWidgetConfigurationService(new AnswerWidgetConfigurationAnswersStub()))
            ->forRiddle(42, static fn (string $field, int $postId) => $fields[$field] ?? null);

        self::assertSame('enigme', $configuration['target_type']);
        self::assertSame('text', $configuration['type']);
        self::assertSame(['final answer'], $configuration['accepted_answers']);
        self::assertTrue($configuration['case_sensitive']);
        self::assertSame('almost', $configuration['variants'][1]['texte']);
    }

    public function testAdaptsRiddleGpsWidgetConfiguration(): void {
        $fields = [
            'enigme_reponse_widget' => 'gps',
            'enigme_gps_coordinates' => '48.85837 2.29448',
            'enigme_gps_tolerance' => '40',
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forRiddle(
            55,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('gps', $configuration['type']);
        self::assertSame('enigme', $configuration['target_type']);
        self::assertSame('48.85837 2.29448', $configuration['target_coordinates']);
        self::assertSame(40, $configuration['tolerance_meters']);
    }

    public function testAdaptsRiddleDirectionSequences(): void {
        $fields = [
            'enigme_reponse_widget' => 'directions',
            'enigme_directions_sequences' => "N,E\nS,O",
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forRiddle(
            56,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('directions', $configuration['type']);
        self::assertSame(['N,E', 'S,O'], $configuration['accepted_sequences']);
    }

    public function testAdaptsDirectionSequences(): void {
        $fields = [
            'etape_reponse_widget' => 'directions',
            'etape_directions_sequences' => "N,NE,E\nS,SO,O",
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forStep(
            18,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('directions', $configuration['type']);
        self::assertSame(['N,NE,E', 'S,SO,O'], $configuration['accepted_sequences']);
    }

    public function testAdaptsNumericAndSafeDialSequences(): void {
        $service = new AnswerWidgetConfigurationService();
        $fields = [
            'etape_reponse_widget' => 'numbers',
            'etape_number_sequences' => "0129\n987",
        ];
        $numbers = $service->forStep(19, static fn (string $field, int $postId) => $fields[$field] ?? null);

        $fields = [
            'etape_reponse_widget' => 'safe_dial',
            'etape_safe_dial_sequences' => "H11 A51\nA4 H92",
        ];
        $safeDial = $service->forStep(20, static fn (string $field, int $postId) => $fields[$field] ?? null);

        self::assertSame(['0129', '987'], $numbers['accepted_sequences']);
        self::assertSame(['H11 A51', 'A4 H92'], $safeDial['accepted_sequences']);
    }

    public function testAdaptsGpsConfiguration(): void {
        $fields = [
            'etape_reponse_widget' => 'gps',
            'etape_gps_coordinates' => '48.85837 2.29448',
            'etape_gps_tolerance' => '30',
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forStep(
            21,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('gps', $configuration['type']);
        self::assertSame('48.85837 2.29448', $configuration['target_coordinates']);
        self::assertSame(30, $configuration['tolerance_meters']);
    }

    public function testAdaptsPianoSequences(): void {
        $fields = [
            'etape_reponse_widget' => 'piano',
            'etape_piano_sequences' => "F1 F#2 B2 C1\nC1 E1 G1",
        ];
        $configuration = (new AnswerWidgetConfigurationService())->forStep(
            22,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('piano', $configuration['type']);
        self::assertSame(['F1 F#2 B2 C1', 'C1 E1 G1'], $configuration['accepted_sequences']);
    }
}
