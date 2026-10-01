<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Invalidate cached hunt presentation data when its business inputs change.
 */
final class HuntDisplayCacheInvalidationHookHandler
{
    public static function register(callable $addAction, callable $addFilter): void
    {
        $addAction('acf/save_post', [self::class, 'clearAfterAcfSave'], 20, 1);
        $addAction('save_post', [self::class, 'clearAfterPostSave'], 10, 3);
        $addAction('chasse_engagement_created', [self::class, 'clearHunt'], 10, 1);
        $addAction('set_object_terms', [self::class, 'clearAfterTermsChange'], 10, 6);
        $addAction('chassesautresor_hunt_display_cache_clear_requested', [self::class, 'clearHunt'], 10, 1);
        $addFilter(
            'acf/update_value/name=utilisateurs_associes',
            [self::class, 'clearAfterOrganizerUsersChange'],
            20,
            2
        );
    }

    public static function clearHunt($huntId): void
    {
        $huntId = is_numeric($huntId) ? (int) $huntId : 0;
        if ($huntId <= 0) {
            return;
        }

        $key = self::cacheKey($huntId);
        wp_cache_delete($key, 'chasse_affichage');
        delete_transient($key);
    }

    public static function clearAfterAcfSave($postId): void
    {
        $postId = is_numeric($postId) ? (int) $postId : 0;
        $type = $postId > 0 ? get_post_type($postId) : false;

        if ($type === 'chasse') {
            self::clearHunt($postId);
        } elseif ($type === 'organisateur') {
            self::clearOrganizerHunts($postId);
        }
    }

    public static function clearAfterPostSave(int $postId, \WP_Post $post, bool $update): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        if ($post->post_type === 'chasse') {
            self::clearHunt($postId);
        } elseif ($post->post_type === 'enigme') {
            self::clearHunt((int) get_field('chasse_associee', $postId));
        } elseif ($post->post_type === 'organisateur') {
            self::clearOrganizerHunts($postId);
        }
    }

    public static function clearAfterTermsChange(
        int $objectId,
        $terms,
        $termTaxonomyIds,
        string $taxonomy,
        $append,
        $oldTermTaxonomyIds
    ): void {
        if (
            in_array($taxonomy, ['chasse_region', 'theme_chasse'], true)
            && get_post_type($objectId) === 'chasse'
        ) {
            self::clearHunt($objectId);
        }
    }

    public static function clearAfterOrganizerUsersChange($value, $postId)
    {
        if (is_numeric($postId) && get_post_type((int) $postId) === 'organisateur') {
            self::clearOrganizerHunts((int) $postId);
        }

        return $value;
    }

    public static function cacheKey(int $huntId): string
    {
        return 'chasse_infos_affichage_v2_' . $huntId;
    }

    private static function clearOrganizerHunts(int $organizerId): void
    {
        if ($organizerId <= 0) {
            return;
        }

        $huntIds = get_posts([
            'post_type' => 'chasse',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_key' => 'chasse_cache_organisateur',
            'meta_value' => $organizerId,
        ]);

        foreach ($huntIds as $huntId) {
            self::clearHunt((int) $huntId);
        }
    }
}
