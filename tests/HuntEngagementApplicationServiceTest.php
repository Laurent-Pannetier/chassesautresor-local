<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntEngagementApplicationService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntEngagementApplicationService.php';

final class HuntEngagementApplicationServiceTest extends TestCase {
    /** @dataProvider rejectionProvider */
    public function testRejectsWithoutPersistenceOrDebit(array $context, string $status): void {
        $persisted = false;
        $charged = false;
        $result = (new HuntEngagementApplicationService())->engage(
            ...array_merge($context, [
                static function () use (&$persisted): bool {
                    $persisted = true;
                    return true;
                },
                static function () use (&$charged): void {
                    $charged = true;
                },
            ])
        );

        $this->assertSame(['status' => $status, 'charged' => 0], $result);
        $this->assertFalse($persisted);
        $this->assertFalse($charged);
    }

    public function rejectionProvider(): array {
        return [
            'anonymous' => [[0, 2, 'chasse', true, false, false, 0, 0], 'invalid_request'],
            'CPT' => [[1, 2, 'enigme', true, false, false, 0, 0], 'invalid_request'],
            'nonce' => [[1, 2, 'chasse', false, false, false, 0, 0], 'invalid_nonce'],
            'administrator' => [[1, 2, 'chasse', true, true, false, 0, 0], 'engagement_failed'],
            'organizer' => [[1, 2, 'chasse', true, false, true, 0, 0], 'engagement_failed'],
            'points' => [[1, 2, 'chasse', true, false, false, 11, 10], 'points_insuffisants'],
        ];
    }

    public function testPersistsBeforeChargingPoints(): void {
        $events = [];
        $result = (new HuntEngagementApplicationService())->engage(
            1,
            2,
            'chasse',
            true,
            false,
            false,
            10,
            20,
            static function () use (&$events): bool {
                $events[] = 'persist';
                return true;
            },
            static function () use (&$events): void {
                $events[] = 'debit';
            }
        );

        $this->assertSame(['status' => 'success', 'charged' => 10], $result);
        $this->assertSame(['persist', 'debit'], $events);
    }

    public function testDoesNotChargeWhenPersistenceFails(): void {
        $charged = false;
        $result = (new HuntEngagementApplicationService())->engage(
            1, 2, 'chasse', true, false, false, 10, 20,
            static fn (): bool => false,
            static function () use (&$charged): void {
                $charged = true;
            }
        );

        $this->assertSame(['status' => 'engagement_failed', 'charged' => 0], $result);
        $this->assertFalse($charged);
    }
}
