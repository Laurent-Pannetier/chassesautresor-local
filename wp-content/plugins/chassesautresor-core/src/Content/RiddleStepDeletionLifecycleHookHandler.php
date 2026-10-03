<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Remove child steps and their player data during permanent deletion. */
final class RiddleStepDeletionLifecycleHookHandler {
    /** @var array<int,int> */
    private static array $riddleIdsByStep = [];

    public static function register(callable $addAction): void {
        $addAction('before_delete_post', [self::class, 'handle'], 20, 1);
        $addAction('deleted_post', [self::class, 'refreshParentCompleteness'], 20, 1);
    }

    public static function handle(int $postId): void {
        $postType = (string) get_post_type($postId);
        global $wpdb;

        if ($postType === RiddleStepPostTypeRegistrar::POST_TYPE) {
            $riddleId = (int) get_field('etape_enigme_associee', $postId);
            if ($riddleId > 0) {
                self::$riddleIdsByStep[$postId] = $riddleId;
            }
            CoreServiceFactory::riddleStepProgress($wpdb)->deleteForStep($postId);
            CoreServiceFactory::riddleAttempts($wpdb)->deleteForStep($postId);
            return;
        }

        if ($postType !== 'enigme') {
            return;
        }

        $progress = CoreServiceFactory::riddleStepProgress($wpdb);
        $progress->deleteForRiddle($postId);
        foreach ((new RiddleStepQueryService())->findOrderedIds($postId) as $stepId) {
            wp_delete_post($stepId, true);
        }
    }

    public static function refreshParentCompleteness(int $postId): void {
        $riddleId = self::$riddleIdsByStep[$postId] ?? 0;
        unset(self::$riddleIdsByStep[$postId]);
        if ($riddleId > 0) {
            do_action('chassesautresor_riddle_completeness_refresh_requested', $riddleId);
        }
    }
}
