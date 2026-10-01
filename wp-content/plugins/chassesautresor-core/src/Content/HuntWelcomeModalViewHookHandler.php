<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist the one-time welcome modal state outside the hunt template.
 */
final class HuntWelcomeModalViewHookHandler
{
    private const CONTENT_POST_ID = 9004;

    public static function register(callable $addAction): void
    {
        $addAction('wp_footer', [self::class, 'handle'], 99);
    }

    public static function handle(): void
    {
        if (!is_singular('chasse')) {
            return;
        }

        $huntId = (int) get_queried_object_id();
        if ($huntId <= 0 || get_post_meta($huntId, 'chasse_modal_bienvenue_vue', true)) {
            return;
        }

        $welcome = get_post(self::CONTENT_POST_ID);
        if (!$welcome || $welcome->post_status !== 'publish') {
            return;
        }

        update_post_meta($huntId, 'chasse_modal_bienvenue_vue', '1');
    }
}
