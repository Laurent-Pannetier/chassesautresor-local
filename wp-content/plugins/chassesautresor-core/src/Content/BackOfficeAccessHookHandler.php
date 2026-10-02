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

        if (self::canEditRiddleStep($requestUri)) {
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

    private static function canEditRiddleStep(string $requestUri): bool {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $stepId = isset($_GET['post']) ? (int) $_GET['post'] : 0;
        if (basename($path) !== 'post.php' || $stepId <= 0) {
            return false;
        }

        return (new RiddleStepAdminAccessService())->canEdit(
            $stepId,
            'get_post_type',
            static fn (int $id) => get_field('etape_enigme_associee', $id),
            static fn (int $id): bool => utilisateur_peut_modifier_post($id)
        );
    }
}
