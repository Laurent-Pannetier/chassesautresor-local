<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Secure endpoint for dismissing account and site messages. */
class AccountMessageDismissalAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_cta_dismiss_message', [self::class, 'handle']);
    }

    public static function handle(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $key = sanitize_key($_POST['key'] ?? '');
        if ($key === '') {
            wp_send_json_error(['message' => __('Clé de message invalide.', 'chassesautresor-com')], 400);
        }

        global $wpdb;
        $repository = new UserMessageRepository($wpdb);
        (new AccountMessageService($repository))->removePersistent((int) get_current_user_id(), $key);
        (new SiteMessageService($repository))->removeByKey($key);
        wp_send_json_success();
    }
}
