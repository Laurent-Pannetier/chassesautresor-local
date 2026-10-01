<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Schedule and process bounded hunt status maintenance batches.
 */
class HuntStatusScheduler {
    public const HOOK = 'cat_recalculate_chasse_statuses';

    public static function register(callable $addAction): void {
        $addAction(self::HOOK, [self::class, 'process'], 10, 1);
    }

    public static function schedule(): void {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), 'hourly', self::HOOK);
        }
    }

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    public static function process(
        int $batchSize = 100,
        ?callable $getPosts = null,
        ?callable $dispatch = null
    ): int {
        $batchSize = max(1, $batchSize);
        $getPosts = $getPosts ?? 'get_posts';
        $dispatch = $dispatch ?? [self::class, 'refresh'];
        $processed = 0;
        $offset = 0;
        do {
            $huntIds = $getPosts([
                'post_type' => 'chasse',
                'post_status' => 'any',
                'fields' => 'ids',
                'posts_per_page' => $batchSize,
                'offset' => $offset,
                'orderby' => 'ID',
                'order' => 'ASC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            ]);
            foreach ($huntIds as $huntId) {
                $dispatch((int) $huntId);
                $processed++;
            }
            $offset += $batchSize;
        } while (count($huntIds) === $batchSize);

        return $processed;
    }

    public static function refresh(int $huntId): void {
        (new HuntStatusUpdater())->refreshIfStale($huntId);
    }
}
