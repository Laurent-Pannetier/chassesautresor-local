<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Secure AJAX orchestration for hunt statistics and participant lists. */
class HuntStatisticsAjaxHandler {
    private static $authorizer;
    private static $summaryBuilder;
    private static $participantLoader;
    private static $participantCounter;
    private static $participantRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_recuperer_stats', [self::class, 'summary']);
        $addAction('wp_ajax_chasse_lister_participants', [self::class, 'participants']);
    }

    public static function configure(
        callable $authorizer,
        callable $summaryBuilder,
        callable $participantLoader,
        callable $participantCounter,
        callable $participantRenderer
    ): void {
        self::$authorizer = $authorizer;
        self::$summaryBuilder = $summaryBuilder;
        self::$participantLoader = $participantLoader;
        self::$participantCounter = $participantCounter;
        self::$participantRenderer = $participantRenderer;
    }

    public static function summary(): void {
        self::verifyNonce();
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if ($huntId <= 0) {
            wp_send_json_error('missing_chasse', 400);
        }
        if (!is_callable(self::$authorizer) || !call_user_func(self::$authorizer, $huntId)) {
            wp_send_json_error('forbidden', 403);
        }

        $period = (new StatisticsPeriodService())->normalize(sanitize_text_field($_POST['periode'] ?? 'total'));
        $cache = new StatisticsCacheService();
        $stats = $cache->get('chasse', $huntId, $period);
        if ($stats === false) {
            if (!is_callable(self::$summaryBuilder)) {
                wp_send_json_error('forbidden', 403);
            }
            $stats = (array) call_user_func(self::$summaryBuilder, $huntId, $period);
            $cache->put('chasse', $huntId, $period, $stats, HOUR_IN_SECONDS);
        }
        wp_send_json_success($stats);
    }

    public static function participants(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
        self::verifyNonce();
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide');
        }
        if (!is_callable(self::$authorizer) || !call_user_func(self::$authorizer, $huntId)) {
            wp_send_json_error('acces_refuse');
        }

        $request = (new StatisticsParticipantRequestService())->prepare(
            (int) ($_POST['page'] ?? 1),
            25,
            sanitize_text_field($_POST['order'] ?? 'ASC')
        );
        $allowedOrderBy = ['inscription', 'username', 'participation', 'resolution'];
        $orderBy = sanitize_text_field($_POST['orderby'] ?? 'inscription');
        $orderBy = in_array($orderBy, $allowedOrderBy, true) ? $orderBy : 'inscription';
        if (!is_callable(self::$participantLoader)
            || !is_callable(self::$participantCounter)
            || !is_callable(self::$participantRenderer)
        ) {
            wp_send_json_error('acces_refuse');
        }
        $rows = (array) call_user_func(
            self::$participantLoader,
            $huntId,
            $request['limit'],
            $request['offset'],
            $orderBy,
            $request['order']
        );
        $total = (int) call_user_func(self::$participantCounter, $huntId);
        $pages = (int) ceil($total / $request['limit']);
        $html = (string) call_user_func(
            self::$participantRenderer,
            $huntId,
            $rows,
            $request,
            $total,
            $pages,
            $orderBy
        );
        wp_send_json_success(['html' => $html, 'total' => $total, 'page' => $request['page'], 'pages' => $pages]);
    }

    private static function verifyNonce(): void {
        if (!wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'statistics_management')) {
            wp_send_json_error('invalid_nonce', 403);
        }
    }
}
