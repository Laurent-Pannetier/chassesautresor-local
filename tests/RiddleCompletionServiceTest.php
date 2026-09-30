<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCompletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCompletionService.php';

class RiddleCompletionServiceTest extends TestCase
{
    public function testManagementStatusReportsIncompleteRiddlesAndAdditionAccess(): void
    {
        $service = new RiddleCompletionService();

        $this->assertSame(
            ['has_incomplete' => true, 'can_add' => true],
            $service->getManagementStatus([true, false, true], true)
        );
        $this->assertSame(
            ['has_incomplete' => false, 'can_add' => false],
            $service->getManagementStatus([true, true], false)
        );
    }

    public function testManagementEvaluationRefreshesUntilFirstIncompleteRiddle(): void
    {
        $refreshed = [];
        $completion = [10 => true, 20 => false, 30 => true];

        $status = (new RiddleCompletionService())->evaluateManagementStatus(
            [0, 10, 20, 30],
            true,
            static function (int $riddleId) use (&$refreshed): void {
                $refreshed[] = $riddleId;
            },
            static fn (int $riddleId): bool => $completion[$riddleId]
        );

        $this->assertSame(['has_incomplete' => true, 'can_add' => true], $status);
        $this->assertSame([10, 20], $refreshed);
    }

    public function testEmptyRiddleListIsCompleteForManagement(): void
    {
        $status = (new RiddleCompletionService())->evaluateManagementStatus(
            [],
            false,
            static function (): void {
            },
            static fn (): bool => false
        );

        $this->assertSame(['has_incomplete' => false, 'can_add' => false], $status);
    }

    public function testCompleteRiddleRequiresTitleAndNonPlaceholderImage(): void
    {
        $service = new RiddleCompletionService();

        $this->assertTrue($service->isComplete(true, 10, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(false, 10, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(true, 0, 99, 'manuelle', false, 'immediat', false));
        $this->assertFalse($service->isComplete(true, 99, 99, 'manuelle', false, 'immediat', false));
    }

    public function testAutomaticRiddleRequiresAnAnswer(): void
    {
        $service = new RiddleCompletionService();

        $this->assertFalse($service->isComplete(true, 10, 99, 'automatique', false, 'immediat', false));
        $this->assertTrue($service->isComplete(true, 10, 99, 'automatique', true, 'immediat', false));
    }

    public function testPrerequisiteAccessRequiresAtLeastOnePrerequisite(): void
    {
        $service = new RiddleCompletionService();

        $this->assertFalse($service->isComplete(true, 10, 99, 'manuelle', false, 'pre_requis', false));
        $this->assertTrue($service->isComplete(true, 10, 99, 'manuelle', false, 'pre_requis', true));
    }
}
