<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress hook adapter for the hunt-riddle cache lifecycle.
 */
class RiddleRelationshipHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handleSave'], 20, 1);
        $addAction('before_delete_post', [self::class, 'handleBeforeDelete'], 10, 1);
    }

    /** @param int|string $postId */
    public static function handleSave($postId): void {
        if (
            !is_numeric($postId)
            || get_post_type($postId) !== 'enigme'
            || wp_is_post_revision($postId)
            || wp_is_post_autosave($postId)
        ) {
            return;
        }

        (new RiddleRelationshipLifecycleService())->attach(
            (int) $postId,
            get_field('enigme_chasse_associee', $postId),
            static fn (int $huntId): bool => get_post_type($huntId) === 'chasse',
            [self::class, 'attach']
        );
    }

    public static function handleBeforeDelete(int $postId): void {
        if (get_post_type($postId) !== 'enigme') {
            return;
        }

        $hunt = get_field('enigme_chasse_associee', $postId)
            ?: get_field('chasse_associee', $postId);
        (new RiddleRelationshipLifecycleService())->detach(
            $postId,
            $hunt,
            [self::class, 'detach'],
            [self::class, 'cleanOrphans']
        );
    }

    public static function attach(int $huntId, int $riddleId): bool {
        return self::mutate($huntId, $riddleId, 'add');
    }

    public static function detach(int $huntId, int $riddleId): bool {
        return self::mutate($huntId, $riddleId, 'remove');
    }

    public static function cleanOrphans(): void {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'chasse_cache_enigmes'"
        );

        (new RiddleRelationshipCleanupService())->clean(
            is_array($rows) ? $rows : [],
            'maybe_unserialize',
            static function (array $riddleIds) use ($wpdb): array {
                $placeholders = implode(', ', array_fill(0, count($riddleIds), '%d'));
                $query = "SELECT ID FROM {$wpdb->posts} WHERE ID IN ({$placeholders})";

                return array_map('intval', $wpdb->get_col($wpdb->prepare($query, ...$riddleIds)));
            },
            static function (int $huntId, array $riddleIds): void {
                update_post_meta($huntId, RiddleCacheMutationService::FIELD_NAME, $riddleIds);
            }
        );
    }

    private static function mutate(int $huntId, int $riddleId, string $action): bool {
        return (new RiddleCacheMutationService())->mutate(
            $huntId,
            $riddleId,
            $action,
            static fn (int $postId, string $key) => get_post_meta($postId, $key, true),
            'update_post_meta'
        );
    }
}
