<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Keep organizer accounts out of wp-admin while allowing background requests.
 */
final class BackOfficeAccessHookHandler {
    private const ORGANIZER_ROLES = ['organisateur', 'organisateur_creation'];

    public static function register(callable $addAction): void {
        $addAction('admin_init', [self::class, 'handle']);
    }

    public static function handle(): void {
        if (!is_user_logged_in() || self::isBackgroundRequest()) {
            return;
        }

        $roles = (array) wp_get_current_user()->roles;
        if (in_array('administrator', $roles, true) || !array_intersect(self::ORGANIZER_ROLES, $roles)) {
            return;
        }

        $requestUri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '';
        if (strpos($requestUri, '/upload.php') !== false) {
            return;
        }

        wp_safe_redirect(home_url('/'));
        exit;
    }

    private static function isBackgroundRequest(): bool {
        return (defined('DOING_AJAX') && DOING_AJAX)
            || (defined('REST_REQUEST') && REST_REQUEST)
            || wp_doing_cron();
    }
}
