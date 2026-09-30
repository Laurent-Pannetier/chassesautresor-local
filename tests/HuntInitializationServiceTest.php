<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntInitializationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntInitializationService.php';

final class HuntInitializationServiceTest extends TestCase {
    private HuntInitializationService $service;

    protected function setUp(): void {
        $this->service = new HuntInitializationService();
    }

    public function testIgnoresOtherPostTypesAndAutosaves(): void {
        $callback = static function (): void {
            self::fail('No persistence callback should run.');
        };

        $wrongType = $this->service->initialize(12, 'enigme', false, 0, $callback, $callback, $callback, $callback);
        $autosave = $this->service->initialize(12, 'chasse', true, 0, $callback, $callback, $callback, $callback);

        $this->assertFalse($wrongType['handled']);
        $this->assertFalse($autosave['handled']);
    }

    public function testPersistsOrganizerAndDefaultEndDate(): void {
        $calls = [];

        $result = $this->service->initialize(
            12,
            'chasse',
            false,
            strtotime('2026-03-10 12:00:00'),
            static fn (int $huntId): int => $huntId === 12 ? 42 : 0,
            static function (int $huntId, int $organizerId) use (&$calls): bool {
                $calls[] = ['organizer', $huntId, $organizerId];
                return true;
            },
            static fn (int $huntId): string => '',
            static function (int $huntId, string $endDate) use (&$calls): bool {
                $calls[] = ['end_date', $huntId, $endDate];
                return true;
            }
        );

        $this->assertSame([
            ['organizer', 12, 42],
            ['end_date', 12, '2028-03-10'],
        ], $calls);
        $this->assertSame(42, $result['organizer_id']);
        $this->assertTrue($result['organizer_persisted']);
        $this->assertSame('2028-03-10', $result['end_date']);
    }

    public function testPreservesExistingDateAndHandlesMissingOrganizer(): void {
        $persist = static function (): void {
            self::fail('Nothing should be persisted.');
        };

        $result = $this->service->initialize(
            12,
            'chasse',
            false,
            0,
            static fn (): int => 0,
            $persist,
            static fn (): string => '2027-01-01',
            $persist
        );

        $this->assertTrue($result['handled']);
        $this->assertSame(0, $result['organizer_id']);
        $this->assertNull($result['organizer_persisted']);
        $this->assertNull($result['end_date']);
    }
}
