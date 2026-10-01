<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

/** AJAX orchestration for the current user's points history. */
class PointsHistoryAjaxHandler {
    /** @var callable|null */
    private static $loader;

    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_load_points_history', [self::class, 'handle']);
    }

    public static function configure(callable $loader, callable $renderer): void {
        self::$loader = $loader;
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        $request = (new HistoryPaginationRequestService())->prepare(
            is_user_logged_in(),
            isset($_POST['page']) ? (int) $_POST['page'] : 1,
            20
        );
        if (!$request['allowed']) {
            wp_send_json_error();
        }
        check_ajax_referer('points-history-nonce', 'nonce');
        if (!is_callable(self::$loader) || !is_callable(self::$renderer)) {
            wp_send_json_error();
        }

        $operations = (array) call_user_func(
            self::$loader,
            (int) get_current_user_id(),
            $request['page'],
            $request['per_page']
        );
        wp_send_json_success(['rows' => (string) call_user_func(self::$renderer, $operations)]);
    }
}
