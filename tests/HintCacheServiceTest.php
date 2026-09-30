<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCacheService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintStatusService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCacheService.php';

class HintCacheServiceTest extends TestCase
{
    private HintCacheService $service;

    protected function setUp(): void
    {
        $this->service = new HintCacheService();
    }

    public function testIncompleteHintIsDisabledAndMovedToPending(): void
    {
        $update = $this->service->buildUpdate(false, false, 'immediate', null, 1000, 'publish');

        $this->assertSame(0, $update['complete']);
        $this->assertSame('desactive', $update['state']);
        $this->assertSame('pending', $update['publication_status']);
    }

    public function testImmediateCompleteHintBecomesAccessibleAndPublished(): void
    {
        $update = $this->service->buildUpdate(true, false, 'immediate', null, 1000, 'pending');

        $this->assertSame(1, $update['complete']);
        $this->assertSame('accessible', $update['state']);
        $this->assertSame('publish', $update['publication_status']);
    }

    public function testFutureHintIsProgrammedWithoutChangingSupportedPublicationStatus(): void
    {
        $update = $this->service->buildUpdate(false, true, 'differe', 2000, 1000, 'draft');

        $this->assertSame(1, $update['complete']);
        $this->assertSame('programme', $update['state']);
        $this->assertNull($update['publication_status']);
    }

    public function testElapsedProgrammedHintBecomesAccessible(): void
    {
        $update = $this->service->buildUpdate(true, false, 'differe', 900, 1000, 'pending');

        $this->assertSame(1, $update['complete']);
        $this->assertSame('accessible', $update['state']);
        $this->assertSame('publish', $update['publication_status']);
    }
}
