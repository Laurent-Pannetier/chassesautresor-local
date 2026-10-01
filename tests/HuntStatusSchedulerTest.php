<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusScheduler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusScheduler.php';

final class HuntStatusSchedulerTest extends TestCase {
    public function testRegistersHourlyProcessingHookOnce(): void {
        $hooks = [];
        HuntStatusScheduler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            HuntStatusScheduler::HOOK,
            [HuntStatusScheduler::class, 'process'],
            10,
            1,
        ]], $hooks);
    }

    public function testProcessesHuntsInBoundedCacheFreeBatches(): void {
        $queries = [];
        $processed = [];
        $batches = [[2, 4], [7]];

        $count = HuntStatusScheduler::process(
            2,
            static function (array $args) use (&$queries, &$batches): array {
                $queries[] = $args;
                return array_shift($batches);
            },
            static function (int $huntId) use (&$processed): void {
                $processed[] = $huntId;
            }
        );

        $this->assertSame(3, $count);
        $this->assertSame([2, 4, 7], $processed);
        $this->assertSame([0, 2], array_column($queries, 'offset'));
        $this->assertSame([2, 2], array_column($queries, 'posts_per_page'));
        $this->assertTrue($queries[0]['no_found_rows']);
        $this->assertFalse($queries[0]['update_post_meta_cache']);
        $this->assertFalse($queries[0]['update_post_term_cache']);
    }

    public function testDefaultDispatchRefreshesStatusDirectlyInCore(): void {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusScheduler.php'
        );

        $this->assertStringContainsString("\$dispatch = \$dispatch ?? [self::class, 'refresh']", $source);
        $this->assertStringContainsString('(new HuntStatusUpdater())->refreshIfStale($huntId)', $source);
        $this->assertStringNotContainsString('chassesautresor_hunt_status_stale_check_requested', $source);
    }
}
