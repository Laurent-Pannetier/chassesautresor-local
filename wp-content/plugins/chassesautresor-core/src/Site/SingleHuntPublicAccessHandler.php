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
        if (current_user_can('manage_options')) {
            return;
        }

        $isOrganizerConfirmation = get_query_var('confirmation_organisateur') === '1';

        if (
            !cat_are_organizer_applications_open()
            && (is_page(self::CLOSED_PAGE_SLUGS) || $isOrganizerConfirmation)
        ) {
            self::redirectToPublicEntry();
        }

        if (!cat_is_single_hunt_mode() || !is_singular('organisateur')) {
            return;
        }

        // Hors plateforme : la fiche organisateur n’est plus une entrée utile
        // (y compris pour l’organisateur propriétaire). Seul l’admin passe.
        wp_safe_redirect(home_url('/'), 302, 'ChassesAuTresor');
        exit;
    }

    private static function redirectToPublicEntry(): void
    {
        $huntId = cat_get_primary_hunt_id();
        $url = $huntId > 0 && get_post_status($huntId) === 'publish'
            ? get_permalink($huntId)
            : home_url('/');

        wp_safe_redirect($url, 302, 'ChassesAuTresor');
        exit;
    }
}
