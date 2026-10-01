<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Relationships\HuntRiddleCacheSynchronizer;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleCacheService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleQueryService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleCacheSynchronizer.php';

final class HuntRiddleCacheSynchronizerTest extends TestCase {
    public function testComparesOnlyRiddlesScopedToTheRequestedHuntAndCorrectsOnce(): void {
        $query = [];
        $updates = [];
        $service = new HuntRiddleCacheSynchronizer();

        $result = $service->compareReality(
            42,
            true,
            static fn (int $postId): string => $postId === 42 ? 'chasse' : '',
            static function (array $args) use (&$query): array {
                $query = $args;
                return [8, 9];
            },
            static fn (): array => [8, 10],
            static function (string $field, array $value, int $postId) use (&$updates): bool {
                $updates[] = [$field, $value, $postId];
                return true;
            }
        );

        $this->assertSame(42, $query['meta_query'][0]['value']);
        $this->assertSame('ids', $query['fields']);
        $this->assertSame([8, 9], $result['attendu']);
        $this->assertSame([8, 10], $result['cache']);
        $this->assertFalse($result['synchro']);
        $this->assertTrue($result['correction']);
        $this->assertSame([['chasse_cache_enigmes', [8, 9], 42]], $updates);
    }

    public function testRejectsInvalidHuntWithoutQueryOrMutation(): void {
        $queries = 0;
        $updates = 0;
        $result = (new HuntRiddleCacheSynchronizer())->compareReality(
            7,
            true,
            static fn (): string => 'enigme',
            static function () use (&$queries): array {
                $queries++;
                return [];
            },
            static fn (): array => [],
            static function () use (&$updates): bool {
                $updates++;
                return true;
            }
        );

        $this->assertFalse($result['valide']);
        $this->assertSame(0, $queries);
        $this->assertSame(0, $updates);
    }

    public function testSynchronizesAllHuntsInBoundedBatches(): void {
        $queries = [];
        $synchronized = [];
        $batches = [[4, 5], [8, 9], [12]];

        $processed = (new HuntRiddleCacheSynchronizer())->synchronizeAll(
            2,
            static function (array $args) use (&$queries, &$batches): array {
                $queries[] = $args;
                return array_shift($batches);
            },
            static function (int $huntId) use (&$synchronized): void {
                $synchronized[] = $huntId;
            }
        );

        $this->assertSame(5, $processed);
        $this->assertSame([4, 5, 8, 9, 12], $synchronized);
        $this->assertSame([0, 2, 4], array_column($queries, 'offset'));
        $this->assertSame([2, 2, 2], array_column($queries, 'posts_per_page'));
        $this->assertTrue($queries[0]['no_found_rows']);
        $this->assertFalse($queries[0]['update_post_meta_cache']);
        $this->assertFalse($queries[0]['update_post_term_cache']);
    }
}
