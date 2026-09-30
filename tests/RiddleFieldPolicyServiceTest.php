<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleFieldPolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldPolicyService.php';

class RiddleFieldPolicyServiceTest extends TestCase {
    private RiddleFieldPolicyService $service;

    protected function setUp(): void {
        $this->service = new RiddleFieldPolicyService();
    }

    public function testOnlyManualAccessConditionsCanBeSubmitted(): void {
        $this->assertTrue($this->service->isAllowedManualAccessCondition('immediat'));
        $this->assertTrue($this->service->isAllowedManualAccessCondition('date_programmee'));
        $this->assertTrue($this->service->isForbiddenAccessCondition('pre_requis'));
        $this->assertFalse($this->service->isAllowedManualAccessCondition('pre_requis'));
    }

    public function testAttemptFieldsAreMappedToTheirStoredAcfFields(): void {
        $this->assertSame(
            'enigme_tentative_cout_points',
            $this->service->getAttemptStorageField('enigme_tentative.enigme_tentative_cout_points')
        );
        $this->assertSame(
            'enigme_tentative_max',
            $this->service->getAttemptStorageField('enigme_tentative.enigme_tentative_max')
        );
        $this->assertNull($this->service->getAttemptStorageField('enigme_tentative.inconnu'));
    }

    public function testPastScheduledAccessMustBecomeImmediate(): void {
        $today = 1_800_000_000;

        $this->assertTrue($this->service->shouldResetScheduledAccess($today - 1, $today, 'date_programmee'));
        $this->assertFalse($this->service->shouldResetScheduledAccess($today, $today, 'date_programmee'));
        $this->assertFalse($this->service->shouldResetScheduledAccess($today - 1, $today, 'immediat'));
    }

    public function testAnswersAreSanitizedAndEmptyValuesRemoved(): void {
        $answers = $this->service->normalizeAnswers(
            [' Trésor ', '', '<b>Carte</b>'],
            static fn ($value): string => trim(strip_tags((string) $value))
        );

        $this->assertSame(['Trésor', 'Carte'], $answers);
        $this->assertNull($this->service->getAnswersError($answers));
    }

    public function testAnswersAreLimitedByCountAndLength(): void {
        $this->assertSame(
            'trop_de_reponses',
            $this->service->getAnswersError(['1', '2', '3', '4', '5', '6'])
        );
        $this->assertSame('longueur_max', $this->service->getAnswersError([str_repeat('a', 76)]));
    }

    public function testPrerequisitesAreNormalizedAndDriveAccessCondition(): void {
        $ids = $this->service->normalizePrerequisiteIds(['12', 0, '34', '']);

        $this->assertSame([12, 34], $ids);
        $this->assertSame('pre_requis', $this->service->getAccessConditionForPrerequisites($ids));
        $this->assertSame('immediat', $this->service->getAccessConditionForPrerequisites([]));
    }
}
