<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Secure transport for revealing the answer stored on an attempt. */
class RiddleAttemptViewAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_ca_view_tentative_proposition', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_ca_view_tentative_proposition', [self::class, 'handle']);
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

        global $wpdb;
        $attempt = CoreServiceFactory::riddleAttempts($wpdb)->findByUid($uid);
        if (!is_object($attempt)) {
            wp_send_json_error(['message' => __('Tentative introuvable.', 'chassesautresor-com')], 404);
        }

        if (!(new RiddleAttemptAccessService())->canViewAttempt($attempt, (int) get_current_user_id())) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        wp_send_json_success([
            'proposition' => isset($attempt->reponse_saisie) ? (string) $attempt->reponse_saisie : '',
        ]);
    }
}
