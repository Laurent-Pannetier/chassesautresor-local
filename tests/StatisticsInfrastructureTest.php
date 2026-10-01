<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\StatisticsCacheInvalidationHookHandler;
use ChassesAuTresor\Core\Progress\StatisticsCacheService;
use ChassesAuTresor\Core\Progress\StatisticsPeriodService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsPeriodService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsCacheService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/StatisticsCacheInvalidationHookHandler.php';

final class StatisticsInfrastructureTest extends TestCase
{
    public function testPeriodRangeUsesParisCalendarBoundaries(): void
    {
        $service = new StatisticsPeriodService();
        $now = new DateTimeImmutable('2026-10-01 04:30:00', new DateTimeZone('Europe/Paris'));

        self::assertSame(['2026-10-01 00:00:00', '2026-10-01 04:30:00'], $service->range('jour', $now));
        self::assertSame(['2026-09-28 00:00:00', '2026-10-01 04:30:00'], $service->range('semaine', $now));
        self::assertSame([null, null], $service->range('unknown', $now));
    }

    public function testCacheUsesTransientFallbackAndWritesBothLayers(): void
    {
        $writes = [];
        $service = new StatisticsCacheService(
            static fn () => false,
            static function (...$args) use (&$writes): void {
                $writes[] = ['object', $args];
            },
            static function (): void {
            },
            static fn () => ['participants' => 3],
            static function (...$args) use (&$writes): void {
                $writes[] = ['transient', $args];
            },
            static function (): void {
            }
        );

        self::assertSame(['participants' => 3], $service->get('enigme', 12, 'mois'));
        $service->put('enigme', 12, 'mois', ['participants' => 4], 3600);

        self::assertSame('enigme_stats_12_mois', $writes[0][1][0]);
        self::assertSame('enigme_stats', $writes[0][1][2]);
        self::assertSame('enigme_stats_12_mois', $writes[1][1][0]);
    }

    public function testClearRemovesEveryPeriodFromBothLayers(): void
    {
        $objectKeys = [];
        $transientKeys = [];
        $service = new StatisticsCacheService(
            static function (): bool {
                return false;
            },
            static function (): void {
            },
            static function (string $key) use (&$objectKeys): void {
                $objectKeys[] = $key;
            },
            static function (): bool {
                return false;
            },
            static function (): void {
            },
            static function (string $key) use (&$transientKeys): void {
                $transientKeys[] = $key;
            }
        );

        $service->clear('chasse', 8);

        self::assertSame([
            'chasse_stats_8_jour',
            'chasse_stats_8_semaine',
            'chasse_stats_8_mois',
            'chasse_stats_8_total',
        ], $objectKeys);
        self::assertSame($objectKeys, $transientKeys);
    }

    public function testInvalidationHooksAreRegisteredOnce(): void
    {
        $hooks = [];
        StatisticsCacheInvalidationHookHandler::register(
            static function (...$args) use (&$hooks): void {
                $hooks[] = $args;
            }
        );

        self::assertSame([
            'chasse_engagement_created',
            'enigme_engagement_created',
            'enigme_tentative_created',
        ], array_column($hooks, 0));
    }
}
