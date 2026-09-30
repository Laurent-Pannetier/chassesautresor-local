<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionFileScheduler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePolicyService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFileScheduler.php';

class RiddleSolutionFileSchedulerTest extends TestCase {
    public function testSchedulerUsesHistoricalPublicationHook(): void {
        $this->assertSame('publier_solution_enigme', RiddleSolutionFileScheduler::HOOK);
    }

    public function testSchedulerRejectsUnsupportedOrIncompletePlan(): void {
        $this->assertNull(RiddleSolutionFileScheduler::resolveTimestamp('immediate', 1, '12:00', 100));
        $this->assertNull(RiddleSolutionFileScheduler::resolveTimestamp('fin_de_chasse', null, '12:00', 100));
    }

    public function testSchedulerAppliesMinimumDelay(): void {
        $this->assertSame(
            1_704_110_405,
            RiddleSolutionFileScheduler::resolveTimestamp(
                'fin_de_chasse',
                -1,
                '00:00',
                1_704_110_400
            )
        );
    }
}
