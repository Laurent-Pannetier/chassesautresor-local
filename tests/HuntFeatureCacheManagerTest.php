<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintQueryService;
use ChassesAuTresor\Core\Content\HuntFeatureCacheManager;
use ChassesAuTresor\Core\Content\HuntFeatureService;
use ChassesAuTresor\Core\Content\SolutionQueryService;
use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFeatureService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HintQueryService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionQueryService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/HuntRiddleQueryService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntFeatureCacheManager.php';

final class HuntFeatureCacheManagerTest extends TestCase
{
    public function testRecalculateAggregatesRiddleFeaturesAndPersistsFlags(): void
    {
        $updates = [];
        $manager = $this->manager(
            static fn (int $id): string => $id === 10 ? 'chasse' : 'enigme',
            static fn (): null => null,
            static function (string $field, int $value, int $postId) use (&$updates): void {
                $updates[] = [$field, $value, $postId];
            },
            static function (array $args): array {
                if (($args['post_type'] ?? '') === 'enigme') {
                    return [20];
                }
                if (($args['post_type'] ?? '') === 'solution') {
                    return str_contains(serialize($args['meta_query']), 'i:20;') ? [91] : [];
                }
                if (($args['post_type'] ?? '') === 'indice') {
                    self::assertSame(1, $args['posts_per_page']);
                }
                return [];
            }
        );

        self::assertTrue($manager->recalculate(10));
        self::assertSame([
            ['chasse_cache_has_solutions', 1, 10],
            ['chasse_cache_has_indices', 0, 10],
        ], $updates);
    }

    public function testRiddleSaveClearsObjectCacheAndRefreshesHunt(): void
    {
        $deleted = [];
        $updates = [];
        $manager = $this->manager(
            static fn (int $id): string => $id === 20 ? 'enigme' : 'chasse',
            static fn (string $field): int => $field === 'enigme_chasse_associee' ? 10 : 0,
            static function (string $field, int $value, int $postId) use (&$updates): void {
                $updates[] = [$field, $value, $postId];
            },
            static fn (): array => [],
            static function (string $key, string $group) use (&$deleted): void {
                $deleted[] = [$key, $group];
            }
        );

        $manager->handleRiddleSave(20);

        self::assertSame([['enigmes_chasse_10', 'chassesautresor']], $deleted);
        self::assertCount(2, $updates);
    }

    public function testHintRelatedToRiddleRefreshesItsHunt(): void
    {
        $updates = [];
        $fields = [
            'indice_cible_type' => 'enigme',
            'indice_enigme_linked' => 20,
            'enigme_chasse_associee' => 10,
        ];
        $manager = $this->manager(
            static fn (int $id): string => $id === 30 ? 'indice' : 'chasse',
            static fn (string $field) => $fields[$field] ?? null,
            static function (string $field, int $value, int $postId) use (&$updates): void {
                $updates[] = [$field, $value, $postId];
            },
            static fn (): array => []
        );

        $manager->handleHintSave(30);

        self::assertCount(2, $updates);
        self::assertSame(10, $updates[0][2]);
    }

    private function manager(
        callable $getPostType,
        callable $getField,
        callable $updateField,
        callable $getPosts,
        ?callable $cacheDelete = null
    ): HuntFeatureCacheManager {
        return new HuntFeatureCacheManager(
            new HuntFeatureService(),
            new HuntRiddleQueryService(),
            new RelationshipService(),
            new SolutionQueryService(),
            new HintQueryService(),
            $getPostType,
            $getField,
            $updateField,
            $getPosts,
            $cacheDelete ?? static function (): void {
            }
        );
    }
}
