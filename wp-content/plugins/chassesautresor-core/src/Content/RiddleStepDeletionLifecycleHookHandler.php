<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Remove child steps and their player data during permanent deletion. */
final class RiddleStepDeletionLifecycleHookHandler {
    public static function register(callable $addAction): void {
        $addAction('before_delete_post', [self::class, 'handle'], 20, 1);
    }

    public static function handle(int $postId): void {
        $postType = (string) get_post_type($postId);
        global $wpdb;

        if ($postType === RiddleStepPostTypeRegistrar::POST_TYPE) {
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
}
