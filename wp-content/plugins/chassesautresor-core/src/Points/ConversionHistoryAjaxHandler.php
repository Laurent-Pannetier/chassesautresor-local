<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX orchestration for organizer and administrator conversion history. */
class ConversionHistoryAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_load_conversion_history', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        $request = (new HistoryPaginationRequestService())->prepare(
            is_user_logged_in(),
            isset($_POST['page']) ? (int) $_POST['page'] : 1,
            10
        );
        if (!$request['allowed']) {
            wp_send_json_error();
        }
        check_ajax_referer('conversion-history-nonce', 'nonce');
        $userId = current_user_can('administrator') ? null : (int) get_current_user_id();
        global $wpdb;
        $requests = CoreServiceFactory::conversion($wpdb)->getRequests(
            $userId,
            null,
            $request['per_page'],
            $request['offset']
        );
        $renderer = self::$renderer ?? [new ConversionHistoryRenderer(), 'rows'];
        wp_send_json_success(['rows' => (string) call_user_func($renderer, $requests, $userId === null)]);
    }
}
