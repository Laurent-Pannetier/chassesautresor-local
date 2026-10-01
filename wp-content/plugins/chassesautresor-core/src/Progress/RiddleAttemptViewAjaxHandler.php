<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Secure transport for revealing the answer stored on an attempt. */
class RiddleAttemptViewAjaxHandler {
    /** @var callable|null */
    private static $attemptLoader;

    /** @var callable|null */
    private static $authorization;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_ca_view_tentative_proposition', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_ca_view_tentative_proposition', [self::class, 'handle']);
    }

    public static function configure(callable $attemptLoader, callable $authorization): void {
        self::$attemptLoader = $attemptLoader;
        self::$authorization = $authorization;
    }

    public static function handle(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $uid = isset($_POST['uid']) ? sanitize_text_field(wp_unslash((string) $_POST['uid'])) : '';
        if ($uid === '') {
            wp_send_json_error(['message' => __('Identifiant de tentative invalide.', 'chassesautresor-com')], 400);
        }

        if (check_ajax_referer('ca_view_tentative_' . $uid, 'nonce', false) === false) {
            wp_send_json_error(['message' => __('Vérification de sécurité échouée.', 'chassesautresor-com')], 403);
        }

        if (!is_callable(self::$attemptLoader) || !is_callable(self::$authorization)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $attempt = call_user_func(self::$attemptLoader, $uid);
        if (!is_object($attempt)) {
            wp_send_json_error(['message' => __('Tentative introuvable.', 'chassesautresor-com')], 404);
        }

        if (!call_user_func(self::$authorization, $attempt)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        wp_send_json_success([
            'proposition' => isset($attempt->reponse_saisie) ? (string) $attempt->reponse_saisie : '',
        ]);
    }
}
