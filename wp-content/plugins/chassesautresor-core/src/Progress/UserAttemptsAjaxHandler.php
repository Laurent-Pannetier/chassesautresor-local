<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX orchestration for the current user's attempts table. */
class UserAttemptsAjaxHandler {
    /** @var callable|null */
    private static $rowRenderer;

    /** @var callable|null */
    private static $pagerRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_ca_fetch_tentatives', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_ca_fetch_tentatives', [self::class, 'handle']);
    }

    public static function configure(
        callable $rowRenderer,
        callable $pagerRenderer
    ): void {
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
        if (!$request['allowed']) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        $hasSearch = isset($_POST['search'])
            && is_array($_POST['search'])
            && isset($_POST['search']['tentatives']);
        $search = $hasSearch
            ? sanitize_text_field(wp_unslash((string) $_POST['search']['tentatives']))
            : '';
        global $wpdb;
        $service = CoreServiceFactory::userAttemptStatistics($wpdb);
        $summary = $service->summarize($userId);
        $pagination = $service->paginate($userId, $request['page'], $request['per_page'], $search);
        $view = [
            'pending' => $summary['pending'],
            'total' => $summary['total'],
            'success' => $summary['success'],
            'search_term' => $search,
            'page' => $pagination['page'],
            'pages' => $pagination['pages'],
            'per_page' => $request['per_page'],
            'filtered_total' => $pagination['total'],
            'tentatives' => $pagination['items'],
            'no_results_message' => $search !== ''
                ? __('Aucune tentative ne correspond à votre recherche.', 'chassesautresor-com')
                : __('Vous n\'avez pas encore enregistré de tentative.', 'chassesautresor-com'),
        ];
        $rowRenderer = self::$rowRenderer ?? [new UserAttemptsRenderer(), 'rows'];
        $pagerRenderer = self::$pagerRenderer ?? [new UserAttemptsRenderer(), 'pager'];
        wp_send_json_success([
            'rows' => (string) call_user_func($rowRenderer, $view),
            'pager' => (string) call_user_func($pagerRenderer, $view),
            'page' => $view['page'],
            'pages' => $view['pages'],
            'per_page' => $view['per_page'],
            'search_term' => $view['search_term'],
            'filtered_total' => $view['filtered_total'],
            'no_results_text' => $view['no_results_message'],
        ]);
    }
}
