<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\HuntRiddleCacheService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleCacheService.php';

class HuntRiddleCacheServiceTest extends TestCase
{
    private HuntRiddleCacheService $service;

    protected function setUp(): void
    {
        $this->service = new HuntRiddleCacheService();
    }

    public function testComparisonIgnoresOrderAndDuplicateIds(): void
    {
        $result = $this->service->compare([12, 24, 12], [24, 12], true);

        $this->assertTrue($result['synced']);
        $this->assertFalse($result['correction']);
        $this->assertSame([12, 24], $result['expected']);
        $this->assertSame([24, 12], $result['cached']);
    }

    public function testComparisonRequestsCorrectionForDifferentRelationships(): void
    {
        $result = $this->service->compare([12, 24], [12, 36], true);

        $this->assertFalse($result['synced']);
        $this->assertTrue($result['correction']);
    }

    public function testValidationIdentifiesRiddlesLinkedElsewhereOrMissing(): void
    {
        $result = $this->service->validate(5, [12, 24, 36], [12 => 5, 24 => 7], true);

        $this->assertFalse($result['synced']);
        $this->assertSame([24, 36], $result['invalid']);
        $this->assertSame([12], $result['corrected']);
        $this->assertTrue($result['correction']);
    }

    public function testValidationDoesNotRequestDisabledCorrection(): void
    {
        $result = $this->service->validate(5, [12], [12 => 7], false);

        $this->assertFalse($result['correction']);
        $this->assertSame([12], $result['invalid']);
    }
}
