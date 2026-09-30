<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Ensure that a newly saved organizer remains related to its author.
 */
class OrganizerRelationshipSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handle'], 20, 1);
    }

    public static function handle(int $postId): void {
        (new OrganizerCreationService())->ensureAuthorRelationship(
            $postId,
            (string) get_post_type($postId),
            defined('DOING_AUTOSAVE') && DOING_AUTOSAVE,
            (int) get_post_field('post_author', $postId),
            get_post_meta($postId, 'utilisateurs_associes', true),
            static fn (string $field, $value, int $id) => update_field($field, $value, $id)
        );
    }
}
