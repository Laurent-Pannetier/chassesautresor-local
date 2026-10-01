<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Keep a hunt related to an organizer when it is saved.
 */
final class HuntOrganizerAssignmentHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('save_post', [self::class, 'handle'], 10, 2);
    }

    public static function handle(int $postId, object $post): void
    {
        if (($post->post_type ?? '') !== 'chasse') {
            return;
        }

        $authorId = (int) ($post->post_author ?? 0);
        $user = $authorId > 0 ? get_userdata($authorId) : false;
        $roles = $user ? (array) $user->roles : [];
        $organizerRole = defined('ROLE_ORGANISATEUR') ? ROLE_ORGANISATEUR : 'organisateur';
        $creationRole = defined('ROLE_ORGANISATEUR_CREATION')
            ? ROLE_ORGANISATEUR_CREATION
            : 'organisateur_en_creation';

        if (!(new OrganizerRoleService())->isOrganizer($roles, $organizerRole, $creationRole)) {
            return;
        }

        update_field('organisateur_id', $authorId, $postId);
    }
}
