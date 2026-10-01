<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** AJAX transport for the organizer conversion modal. */
class ConversionModalAjaxHandler {
    /** @var callable|null */
    private static $accessResolver;

    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_conversion_modal_content', [self::class, 'handle']);
    }

    public static function configure(callable $accessResolver, callable $renderer): void {
        self::$accessResolver = $accessResolver;
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        if (!is_user_logged_in() || !is_callable(self::$accessResolver) || !is_callable(self::$renderer)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $access = call_user_func(self::$accessResolver, (int) get_current_user_id());
        wp_send_json_success([
            'html' => (string) call_user_func(self::$renderer, $access),
            'access' => $access === true,
        ]);
    }
}
