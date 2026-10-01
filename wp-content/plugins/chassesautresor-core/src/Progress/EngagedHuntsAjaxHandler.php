<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX orchestration for the current user's engaged hunts. */
class EngagedHuntsAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_ca_get_engaged_hunts', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        $userId = (int) get_current_user_id();
        $perPage = (int) apply_filters('ca_engaged_hunts_per_page', 6);
        if ($perPage <= 0) {
            $perPage = 6;
        }
        $request = (new UserProgressPaginationService())->prepare(
            is_user_logged_in(),
            $userId,
            isset($_POST['page']) ? absint(wp_unslash($_POST['page'])) : 1,
            $perPage
        );
        if (!$request['allowed']) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'ca-engaged-hunts')) {
            wp_send_json_error(['message' => __('Security check failed.', 'chassesautresor-com')], 400);
        }
        if (!is_callable(self::$renderer)) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        global $wpdb;
        $application = new EngagedHuntsApplicationService(CoreServiceFactory::huntEngagement($wpdb));
        $huntIds = $application->getVisibleHuntIds($userId);
        $pagination = $application->paginate($huntIds, $request['page'], $request['per_page']);
        $html = (string) call_user_func(self::$renderer, $pagination);
        wp_send_json_success([
            'html' => $html,
            'page' => $pagination['page'],
            'total_pages' => $pagination['total_pages'],
            'total_items' => $pagination['total_items'],
            'nonce' => wp_create_nonce('ca-engaged-hunts'),
        ]);
    }
}
