<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** AJAX orchestration for the current user's attempts table. */
class UserAttemptsAjaxHandler {
    /** @var callable|null */
    private static $contextRegistrar;

    /** @var callable|null */
    private static $viewLoader;

    /** @var callable|null */
    private static $rowRenderer;

    /** @var callable|null */
    private static $pagerRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_ca_fetch_tentatives', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_ca_fetch_tentatives', [self::class, 'handle']);
    }

    public static function configure(
        callable $contextRegistrar,
        callable $viewLoader,
        callable $rowRenderer,
        callable $pagerRenderer
    ): void {
        self::$contextRegistrar = $contextRegistrar;
        self::$viewLoader = $viewLoader;
        self::$rowRenderer = $rowRenderer;
        self::$pagerRenderer = $pagerRenderer;
    }

    public static function handle(): void {
        $userId = (int) get_current_user_id();
        $request = (new UserProgressPaginationService())->prepare(
            is_user_logged_in(),
            $userId,
            isset($_POST['page']) ? (int) $_POST['page'] : 1,
            isset($_POST['per_page']) ? (int) $_POST['per_page'] : 10
        );
        $ready = is_callable(self::$contextRegistrar)
            && is_callable(self::$viewLoader)
            && is_callable(self::$rowRenderer)
            && is_callable(self::$pagerRenderer);
        if (!$request['allowed'] || !$ready) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        call_user_func(self::$contextRegistrar);
        $view = (array) call_user_func(self::$viewLoader, $userId, $request['page'], $request['per_page']);
        wp_send_json_success([
            'rows' => (string) call_user_func(self::$rowRenderer, $view),
            'pager' => (string) call_user_func(self::$pagerRenderer, $view),
            'page' => $view['page'],
            'pages' => $view['pages'],
            'per_page' => $view['per_page'],
            'search_term' => $view['search_term'],
            'filtered_total' => $view['filtered_total'],
            'no_results_text' => $view['no_results_message'],
        ]);
    }
}
