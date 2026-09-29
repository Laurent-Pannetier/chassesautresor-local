<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionAvailabilityService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionAvailabilityService.php';

class SolutionAvailabilityServiceTest extends TestCase
{
    private const NOW = 1_704_110_400; // 2024-01-01 12:00:00 UTC.

    private SolutionAvailabilityService $service;
    private string $originalTimezone;

    protected function setUp(): void
    {
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $this->service = new SolutionAvailabilityService();
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
    }

    public function testSolutionIsHiddenUntilHuntIsFinished(): void
    {
        $this->assertFalse($this->isAvailable(huntStatus: 'en_cours'));
    }

    public function testImmediateSolutionIsAvailableWhenHuntIsFinished(): void
    {
        $this->assertTrue($this->isAvailable());
    }

    public function testDeferredSolutionIsHiddenBeforeTargetDate(): void
    {
        $this->assertFalse($this->isAvailable(
            availabilityMode: 'differee',
            baseTimestamp: strtotime('2024-01-01 00:00:00'),
            delayDays: 1,
            publicationTime: '12:00'
        ));
    }

    public function testDeferredSolutionIsAvailableAtTargetDate(): void
    {
        $this->assertTrue($this->isAvailable(
            availabilityMode: 'differee',
            baseTimestamp: strtotime('2023-12-31 00:00:00'),
            delayDays: 1,
            publicationTime: '12:00'
        ));
    }

    public function testMissingBaseUsesCurrentTime(): void
    {
        $this->assertTrue($this->isAvailable(
            availabilityMode: 'differee',
            publicationTime: '00:00'
        ));
    }

    private function isAvailable(
        string $huntStatus = 'termine',
        string $availabilityMode = 'fin_chasse',
        ?int $baseTimestamp = null,
        int $delayDays = 0,
        string $publicationTime = '00:00'
    ): bool {
        return $this->service->isAvailable(
            $huntStatus,
            $availabilityMode,
            $baseTimestamp,
            $delayDays,
            $publicationTime,
            self::NOW
        );
    }
}
