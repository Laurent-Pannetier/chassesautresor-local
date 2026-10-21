<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Keep organizer accounts out of wp-admin and off the front-end toolbar.
 */
final class BackOfficeAccessHookHandler {
    private const ORGANIZER_ROLES = ['organisateur', 'organisateur_creation'];

    public static function register(callable $addAction, callable $addFilter): void {
        $addAction('admin_init', [self::class, 'handle']);
        // After WooCommerce (10) and Members Admin Access (95), so an organizer
        // role cannot put the toolbar back into the page.
        $addFilter('show_admin_bar', [self::class, 'hideForOrganizers'], 999);
    }

    /**
     * Remove the toolbar markup for organizers. Administrators keep it.
     */
    public static function hideForOrganizers(bool $show): bool {
        if (!$show || !is_user_logged_in()) {
            return $show;
        }

        return self::shouldShowAdminBar($show, (array) wp_get_current_user()->roles);
    }

    /**
     * @param array<int, string> $roles
     */
    public static function shouldShowAdminBar(bool $show, array $roles): bool {
        if (!$show || in_array('administrator', $roles, true)) {
            return $show;
        }

        if (array_intersect(self::ORGANIZER_ROLES, $roles) !== []) {
            return false;
        }

        return $show;
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
