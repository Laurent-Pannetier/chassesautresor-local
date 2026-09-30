<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionCacheService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionCacheService.php';

class SolutionCacheServiceTest extends TestCase
{
    private SolutionCacheService $service;

    protected function setUp(): void
    {
        $this->service = new SolutionCacheService();
    }

    public function testMissingTargetMakesSolutionInvalidAndPending(): void
    {
        $this->assertSame(
            ['complete' => 0, 'state' => 'INVALIDE', 'publication_status' => 'pending'],
            $this->service->buildUpdate(true, null, 'publish')
        );
    }

    public function testEmptySolutionIsDisabled(): void
    {
        $this->assertSame(
            ['complete' => 0, 'state' => 'DESACTIVE', 'publication_status' => null],
            $this->service->buildUpdate(false, 42, 'pending')
        );
    }

    public function testCompleteSolutionIsPublished(): void
    {
        $this->assertSame(
            ['complete' => 1, 'state' => 'EN_COURS', 'publication_status' => 'publish'],
            $this->service->buildUpdate(true, 42, 'pending')
        );
    }

    public function testAlreadyPublishedCompleteSolutionNeedsNoTransition(): void
    {
        $this->assertSame(
            ['complete' => 1, 'state' => 'EN_COURS', 'publication_status' => null],
            $this->service->buildUpdate(true, 42, 'publish')
        );
    }
}
