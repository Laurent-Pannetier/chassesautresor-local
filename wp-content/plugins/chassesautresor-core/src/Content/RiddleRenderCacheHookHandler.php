<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Invalidate rendered riddle fragments when content or permissions change.
 */
final class RiddleRenderCacheHookHandler {
    private const CACHE_GROUP = 'chassesautresor';
    private const VERSION_OPTION = 'enigme_permissions_cache_version';

    public static function register(callable $addAction): void {
        $addAction('save_post_enigme', [self::class, 'clear'], 10, 1);
        $addAction('save_post_solution', [self::class, 'clearAfterSolutionSave'], 20, 1);
        $addAction('enigme_resolue', [self::class, 'clearAfterSolve'], 10, 2);

        foreach (
            [
                'set_user_role' => 3,
                'profile_update' => 2,
                'user_register' => 1,
                'deleted_user' => 1,
                'added_user_meta' => 4,
                'updated_user_meta' => 4,
                'deleted_user_meta' => 4,
            ] as $hook => $acceptedArgs
        ) {
            $addAction($hook, [self::class, 'bumpPermissionsVersion'], 10, $acceptedArgs);
        }
    }

    public static function key(string $block, int $riddleId): string {
        $version = (int) get_option(self::VERSION_OPTION, 1);

        return $block . '_' . $riddleId . '_' . $version;
    }

    public static function clear(int $riddleId): void {
        wp_cache_delete(self::key('enigme_sidebar', $riddleId), self::CACHE_GROUP);
        wp_cache_delete(self::key('enigme_solution', $riddleId), self::CACHE_GROUP);
    }

    public static function clearAfterSolutionSave(int $solutionId): void {
        $targetType = (string) get_field('solution_cible_type', $solutionId);
        if ($targetType === 'enigme') {
            $riddleId = (int) get_field('solution_enigme_linked', $solutionId);
            if ($riddleId > 0) {
                self::clear($riddleId);
            }

            return;
        }

        if ($targetType !== 'chasse') {
            return;
        }

        $huntId = (int) get_field('solution_chasse_linked', $solutionId);
        $query = (new \ChassesAuTresor\Core\Relationships\HuntRiddleQueryService())
            ->getRiddleIdsQueryArgs($huntId);
        foreach ($query === [] ? [] : get_posts($query) as $riddleId) {
            self::clear((int) $riddleId);
        }
    }

    public static function clearAfterSolve(int $userId, int $riddleId): void {
        $huntId = (new \ChassesAuTresor\Core\Relationships\RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        );
        if ($huntId !== null) {
            self::clearSidebar($huntId, $userId);
        }

        wp_cache_delete('enigme_sidebar_resolution_' . $riddleId, self::CACHE_GROUP);
    }

    public static function clearSidebar(int $huntId, int $userId): void {
        wp_cache_delete('enigme_sidebar_progression_' . $huntId . '_' . $userId, self::CACHE_GROUP);
    }

    public static function bumpPermissionsVersion(...$arguments): void {
        $version = (int) get_option(self::VERSION_OPTION, 1);
        update_option(self::VERSION_OPTION, $version + 1);
    }
}
