<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionPublicationPlanner;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionPublicationPlanner.php';

class SolutionPublicationPlannerTest extends TestCase {
    public function testHuntTargetUsesDirectHuntRelationship(): void {
        $this->assertSame(12, SolutionPublicationPlanner::resolveHuntId('chasse', ['ID' => 12], 24));
    }

    public function testRiddleTargetUsesRiddleHuntRelationship(): void {
        $this->assertSame(
            24,
            SolutionPublicationPlanner::resolveHuntId('enigme', 12, (object) ['ID' => 24])
        );
    }

    public function testUnknownOrMissingTargetIsRejected(): void {
        $this->assertNull(SolutionPublicationPlanner::resolveHuntId('solution', 12, 24));
        $this->assertNull(SolutionPublicationPlanner::resolveHuntId('enigme', 12, null));
    }
}
