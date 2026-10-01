<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/** Validate account section requests while leaving template rendering in the theme. */
class AccountSectionAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    /** @var callable|null */
    private static $messageLoader;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_cta_load_admin_section', [self::class, 'handle']);
    }

    public static function configure(callable $renderer, callable $messageLoader): void {
        self::$renderer = $renderer;
        self::$messageLoader = $messageLoader;
    }

    public static function handle(): void {
        $section = sanitize_key($_GET['section'] ?? '');
        $decision = (new AccountSectionAccessService())->resolve(
            is_user_logged_in(),
            $section,
            current_user_can('administrator')
        );
        if ($decision['error'] === 'not_found') {
            wp_send_json_error(['message' => __('Section not found', 'chassesautresor-com')], 404);
        }
        if ($decision['error'] !== null || !is_callable(self::$renderer) || !is_callable(self::$messageLoader)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        wp_send_json_success([
            'html' => (string) call_user_func(self::$renderer, $decision['template']),
            'messages' => call_user_func(self::$messageLoader),
        ]);
    }
}
