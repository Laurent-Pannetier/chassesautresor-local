<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Site;

final class SingleHuntPublicAccessHandler
{
    /** @var string[] */
    private const CLOSED_PAGE_SLUGS = ['devenir-organisateur', 'creer-mon-profil', 'confirmation-organisateur'];

    public static function register(callable $addAction): void
    {
        $addAction('template_redirect', [self::class, 'redirectClosedEntrances'], 1);
    }

    public static function redirectClosedEntrances(): void
    {
        if (!cat_is_single_hunt_mode() || current_user_can('manage_options')) {
            return;
        }

        $isOrganizerConfirmation = get_query_var('confirmation_organisateur') === '1';

        if (is_page(self::CLOSED_PAGE_SLUGS) || $isOrganizerConfirmation) {
            self::redirectToPublicEntry();
        }

        if (!is_singular('organisateur')) {
            return;
        }

        $organizerId = get_queried_object_id();
        $userId = get_current_user_id();
        $ownerId = (int) get_post_field('post_author', $organizerId);

        if ($userId > 0 && $userId === $ownerId) {
            return;
        }

        self::redirectToPublicEntry();
    }

    private static function redirectToPublicEntry(): void
    {
        $huntId = cat_get_primary_hunt_id();
        $url = $huntId > 0 ? get_permalink($huntId) : home_url('/');

        wp_safe_redirect($url, 302, 'ChassesAuTresor');
        exit;
    }
}
