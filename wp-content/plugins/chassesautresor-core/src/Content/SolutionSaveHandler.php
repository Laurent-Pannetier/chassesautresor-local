<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Coordinate solution cache and publication planning after an ACF save.
 */
class SolutionSaveHandler {
    public static function handle(int $postId): void {
        if (get_post_type($postId) !== 'solution') {
            return;
        }

        SolutionCacheUpdater::update($postId);
        SolutionPublicationPlanner::plan($postId);
    }
}
