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

    public static function bumpPermissionsVersion(...$arguments): void {
        $version = (int) get_option(self::VERSION_OPTION, 1);
        update_option(self::VERSION_OPTION, $version + 1);
    }
}
