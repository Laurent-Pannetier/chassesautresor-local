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

    public function testPublicationPlanWaitsForHuntCompletion(): void
    {
        $this->assertSame(
            ['state' => 'FIN_CHASSE_DIFFERE', 'target_timestamp' => null],
            $this->service->getPublicationPlan('en_cours', 'differee', 1, '12:00', self::NOW)
        );
    }

    public function testPublicationPlanSchedulesFutureSolution(): void
    {
        $plan = $this->service->getPublicationPlan('termine', 'differee', 1, '12:00', self::NOW);

        $this->assertSame('A_VENIR', $plan['state']);
        $this->assertGreaterThan(self::NOW, $plan['target_timestamp']);
    }

    public function testPublicationPlanMakesImmediateSolutionAccessible(): void
    {
        $this->assertSame(
            ['state' => 'EN_COURS', 'target_timestamp' => null],
            $this->service->getPublicationPlan('termine', 'fin_chasse', 0, '00:00', self::NOW)
        );
    }

    public function testDueSolutionQueryUsesCanonicalStateAndDate(): void
    {
        $args = $this->service->getDueSolutionIdsQueryArgs('2026-10-01 12:00:00');

        $this->assertSame('A_VENIR', $args['meta_query'][0]['value']);
        $this->assertSame('2026-10-01 12:00:00', $args['meta_query'][1]['value']);
        $this->assertSame('DATETIME', $args['meta_query'][1]['type']);
        $this->assertSame('ids', $args['fields']);
        $this->assertSame([], $this->service->getDueSolutionIdsQueryArgs(''));
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
