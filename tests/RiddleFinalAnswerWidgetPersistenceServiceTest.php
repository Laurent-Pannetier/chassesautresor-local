<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleAnswerService;
use ChassesAuTresor\Core\Progress\RiddleFinalAnswerWidgetPersistenceService;
use PHPUnit\Framework\TestCase;

final class RiddleFinalAnswerPersistenceAnswersStub extends RiddleAnswerService {
    /** @var string[] */
    public array $stored = ['soleil'];

    public function get(int $riddleId): array {
        return $this->stored;
    }
}

final class RiddleFinalAnswerWidgetPersistenceServiceTest extends TestCase {
    public function testLoadsLegacyTextAnswersIntoEditorValues(): void {
        $fields = [
            'enigme_reponse_widget' => '',
            'enigme_reponse_casse' => 1,
            'texte_1' => 'presque',
            'message_1' => 'Continuez',
            'enigme_gps_tolerance' => '',
        ];
        $service = new RiddleFinalAnswerWidgetPersistenceService(
            new RiddleFinalAnswerPersistenceAnswersStub()
        );
        $values = $service->loadEditorValues(
            10,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        );

        self::assertSame('text', $values['widget']);
        self::assertSame('soleil', $values['accepted_answers']);
        self::assertTrue($values['case_sensitive']);
        self::assertSame('presque | Continuez', $values['variants']);
        self::assertSame('25', $values['gps_tolerance']);
    }

    public function testMarksGpsConfigurationComplete(): void {
        $fields = [
            'enigme_reponse_widget' => 'gps',
            'enigme_gps_coordinates' => '48.85837 2.29448',
            'enigme_gps_tolerance' => '25',
            'enigme_reponse_casse' => 0,
        ];
        $service = new RiddleFinalAnswerWidgetPersistenceService(
            new RiddleFinalAnswerPersistenceAnswersStub()
        );

        self::assertTrue($service->isComplete(
            12,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        ));
    }

    public function testMarksIncompleteTextWithoutAnswers(): void {
        $fields = [
            'enigme_reponse_widget' => 'text',
            'enigme_reponse_casse' => 0,
        ];
        $answers = new RiddleFinalAnswerPersistenceAnswersStub();
        $answers->stored = [];
        $service = new RiddleFinalAnswerWidgetPersistenceService($answers);

        self::assertFalse($service->isComplete(
            13,
            static fn (string $field, int $postId) => $fields[$field] ?? null
        ));
    }
}
