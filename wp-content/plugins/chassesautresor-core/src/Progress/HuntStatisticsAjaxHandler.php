<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Secure AJAX orchestration for hunt statistics and participant lists. */
class HuntStatisticsAjaxHandler {
    private static $participantRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_recuperer_stats', [self::class, 'summary']);
        $addAction('wp_ajax_chasse_lister_participants', [self::class, 'participants']);
    }

    public static function configure(
        callable $participantRenderer
    ): void {
        self::$participantRenderer = $participantRenderer;
    }

    public static function summary(): void {
        self::verifyNonce();
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if ($huntId <= 0) {
            wp_send_json_error('missing_chasse', 400);
        }
        if (!self::application()->canManage((int) get_current_user_id(), $huntId)) {
            wp_send_json_error('forbidden', 403);
        }

        $period = (new StatisticsPeriodService())->normalize(sanitize_text_field($_POST['periode'] ?? 'total'));
        $cache = new StatisticsCacheService();
        $stats = $cache->get('chasse', $huntId, $period);
        if ($stats === false) {
            $stats = self::application()->summary($huntId, $period);
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
        if (!self::application()->canManage((int) get_current_user_id(), $huntId)) {
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
        if (!is_callable(self::$participantRenderer)) {
            wp_send_json_error('acces_refuse');
        }
        $application = self::application();
        $rows = $application->participants(
            $huntId,
            $request['limit'],
            $request['offset'],
            $orderBy,
            $request['order']
        );
        $total = $application->participantCount($huntId);
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

    private static function application(): HuntStatisticsApplicationService {
        global $wpdb;

        return new HuntStatisticsApplicationService(
            CoreServiceFactory::huntStatistics($wpdb),
            CoreServiceFactory::huntEngagement($wpdb)
        );
    }
}
