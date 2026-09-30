<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress hook adapter for hunt initialization on save.
 */
class HuntInitializationHookHandler {
    private const ORGANIZER_FIELD = 'chasse_cache_organisateur';
    private const ORGANIZER_FIELD_KEY = 'field_67cfcba8c3bec';

    public static function register(callable $addAction): void {
        $addAction('save_post_chasse', [self::class, 'handle'], 20, 2);
    }

    /** @param object $post */
    public static function handle(int $postId, $post): void {
        (new HuntInitializationService())->initialize(
            $postId,
            (string) ($post->post_type ?? ''),
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE,
            (int) current_time('timestamp'),
            static function (int $huntId): int {
                return (new RelationshipService())->normalizeId(
                    get_field(self::ORGANIZER_FIELD, $huntId)
                ) ?? 0;
            },
            [self::class, 'persistOrganizer'],
            static fn (int $huntId) => get_post_meta($huntId, 'chasse_infos_date_fin', true),
            static function (int $huntId, string $endDate): bool {
                $updated = update_field('chasse_infos_date_fin', $endDate, $huntId);

                return $updated !== false
                    || update_post_meta($huntId, 'chasse_infos_date_fin', $endDate) !== false;
            }
        );
    }

    public static function persistOrganizer(int $huntId, int $organizerId): bool {
        if ($huntId <= 0 || $organizerId <= 0) {
            return false;
        }

        update_post_meta($huntId, self::ORGANIZER_FIELD, [(string) $organizerId]);
        update_post_meta($huntId, '_' . self::ORGANIZER_FIELD, self::ORGANIZER_FIELD_KEY);
        $stored = get_post_meta($huntId, self::ORGANIZER_FIELD, true);

        return is_array($stored) && in_array((string) $organizerId, $stored, true);
    }
}
