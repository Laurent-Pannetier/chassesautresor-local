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
}
