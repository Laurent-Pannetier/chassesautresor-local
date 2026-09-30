<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionDeletionService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionDeletionService.php';

class SolutionDeletionServiceTest extends TestCase {
    private SolutionDeletionService $service;

    protected function setUp(): void {
        $this->service = new SolutionDeletionService();
    }

    public function testRiddleTargetUsesLinkedRiddle(): void {
        $this->assertSame(
            ['id' => 24, 'type' => 'enigme'],
            $this->service->resolveTarget('enigme', 12, (object) ['ID' => 24])
        );
    }

    public function testLegacyUnknownTypeFallsBackToLinkedHunt(): void {
        $this->assertSame(
            ['id' => 12, 'type' => 'chasse'],
            $this->service->resolveTarget('', ['ID' => 12], 24)
        );
    }

    public function testMissingSelectedRelationshipIsRejected(): void {
        $this->assertNull($this->service->resolveTarget('enigme', 12, null));
        $this->assertNull($this->service->resolveTarget('chasse', 0, 24));
    }
}
