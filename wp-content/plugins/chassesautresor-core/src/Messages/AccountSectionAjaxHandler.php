<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Validate account section requests while leaving template rendering in the theme. */
class AccountSectionAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_cta_load_admin_section', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        if (check_ajax_referer('cat_account_section', 'nonce', false) === false) {
            wp_send_json_error(['message' => __('Invalid security token', 'chassesautresor-com')], 403);
        }
        $section = sanitize_key($_GET['section'] ?? '');
        $decision = (new AccountSectionAccessService())->resolve(
            is_user_logged_in(),
            $section,
            current_user_can('administrator')
        );
        if ($decision['error'] === 'not_found') {
            wp_send_json_error(['message' => __('Section not found', 'chassesautresor-com')], 404);
        }
        if ($decision['error'] !== null) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $renderer = self::$renderer ?? [new AccountSectionRenderer(), 'render'];
        wp_send_json_success([
            'html' => (string) call_user_func($renderer, $decision['template']),
            'messages' => \myaccount_get_important_messages(),
        ]);
    }
}
