<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** AJAX transport for hunt navigation refreshes. */
class HuntNavigationAjaxHandler {
    private static $accessChecker;
    private static $navigationBuilder;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_recuperer_navigation', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_chasse_recuperer_navigation', [self::class, 'handle']);
    }

    public static function configure(callable $accessChecker, callable $navigationBuilder): void {
        self::$accessChecker = $accessChecker;
        self::$navigationBuilder = $navigationBuilder;
    }

    public static function handle(): void {
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if (!wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'hunt_navigation')) {
            wp_send_json_error('invalid_nonce', 403);
        }
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide', 400);
        }
        $userId = (int) get_current_user_id();
        if (!is_callable(self::$accessChecker) || !call_user_func(self::$accessChecker, $userId, $huntId)) {
            wp_send_json_error('non_engage', 403);
        }
        if (!is_callable(self::$navigationBuilder)) {
            wp_send_json_error('non_engage', 403);
        }

        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $data = (array) call_user_func(self::$navigationBuilder, $huntId, $userId, $riddleId);
        wp_send_json_success([
            'html' => implode('', (array) ($data['menu_items'] ?? [])),
            'ids' => array_values((array) ($data['visible_ids'] ?? [])),
        ]);
    }
}
