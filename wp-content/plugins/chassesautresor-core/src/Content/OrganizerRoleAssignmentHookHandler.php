<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Grant the temporary organizer role when an organizer profile is created.
 */
final class OrganizerRoleAssignmentHookHandler {
    private const ORGANIZER_POST_TYPE = 'organisateur';
    private const SUBSCRIBER_ROLE = 'subscriber';
    private const ORGANIZER_CREATION_ROLE = 'organisateur_creation';

    public static function register(callable $addAction): void {
        $addAction('save_post', [self::class, 'handle'], 10, 3);
    }

    public static function handle(int $postId, \WP_Post $post, bool $update): void {
        if ($post->post_type !== self::ORGANIZER_POST_TYPE || $post->post_status === 'auto-draft') {
            return;
        }

        $user = get_userdata(get_current_user_id());
        if (!$user instanceof \WP_User || !in_array(self::SUBSCRIBER_ROLE, $user->roles, true)) {
            return;
        }

        $user->add_role(self::ORGANIZER_CREATION_ROLE);
    }
}
