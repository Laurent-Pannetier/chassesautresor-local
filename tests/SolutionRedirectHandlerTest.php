<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionRedirectHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionRedirectHandler.php';

class SolutionRedirectHandlerTest extends TestCase {
    public function testHuntSolutionRedirectsToLinkedHunt(): void {
        $this->assertSame(
            12,
            SolutionRedirectHandler::resolveTargetId('chasse', ['ID' => 12], 24)
        );
    }

    public function testRiddleSolutionRedirectsToLinkedRiddle(): void {
        $this->assertSame(
            24,
            SolutionRedirectHandler::resolveTargetId('enigme', 12, (object) ['ID' => 24])
        );
    }

    public function testUnknownOrInvalidRelationshipHasNoRedirectTarget(): void {
        $this->assertNull(SolutionRedirectHandler::resolveTargetId('solution', 12, 24));
        $this->assertNull(SolutionRedirectHandler::resolveTargetId('chasse', 0, 24));
        $this->assertNull(SolutionRedirectHandler::resolveTargetId('enigme', 12, null));
    }
}
