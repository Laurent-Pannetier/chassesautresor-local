<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Relationships\OrganizerRepository;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX transport for the organizer conversion modal. */
class ConversionModalAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_conversion_modal_content', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        if (!is_user_logged_in() || !is_callable(self::$renderer)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        global $wpdb;
        $access = (new ConversionAccessService(
            CoreServiceFactory::conversion($wpdb),
            CoreServiceFactory::points($wpdb),
            new OrganizerRepository($wpdb)
        ))->resolve((int) get_current_user_id());
        wp_send_json_success([
            'html' => (string) call_user_func(self::$renderer, $access),
            'access' => $access === true,
        ]);
    }
}
