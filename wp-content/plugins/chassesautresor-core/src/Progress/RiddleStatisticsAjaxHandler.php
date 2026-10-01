<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Secure AJAX orchestration for riddle statistics and participant lists. */
class RiddleStatisticsAjaxHandler {
    private static $participantRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_enigme_recuperer_stats', [self::class, 'summary']);
        $addAction('wp_ajax_enigme_lister_participants', [self::class, 'participants']);
    }

    public static function configure(
        callable $participantRenderer
    ): void {
        self::$participantRenderer = $participantRenderer;
    }

    public static function summary(): void {
        self::verifyNonce();
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($riddleId <= 0) {
            wp_send_json_error('missing_enigme', 400);
        }
        $access = new RiddleAttemptAccessService();
        if (!$access->canViewRiddlePanel((int) get_current_user_id(), $riddleId)) {
            wp_send_json_error('forbidden', 403);
        }
        $period = (new StatisticsPeriodService())->normalize(sanitize_text_field($_POST['periode'] ?? 'total'));
        $cache = new StatisticsCacheService();
        $stats = $cache->get('enigme', $riddleId, $period);
        if ($stats === false) {
            $stats = self::application()->summary($riddleId, $period);
            $cache->put('enigme', $riddleId, $period, $stats, HOUR_IN_SECONDS);
        }
        wp_send_json_success($stats);
    }

    public static function participants(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
        self::verifyNonce();
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('post_invalide');
        }
        if (!(new RiddleAttemptAccessService())->canModifyRiddle((int) get_current_user_id(), $riddleId)) {
            wp_send_json_error('acces_refuse');
        }
        $request = (new StatisticsParticipantRequestService())->prepare(
            (int) ($_POST['page'] ?? 1),
            25,
            sanitize_text_field($_POST['order'] ?? 'ASC')
        );
        $orderBy = sanitize_text_field($_POST['orderby'] ?? 'date');
        if (!is_callable(self::$participantRenderer)) {
            wp_send_json_error('acces_refuse');
        }
        $application = self::application();
        $rows = $application->participants(
            $riddleId,
            $request['limit'],
            $request['offset'],
            $orderBy,
            $request['order']
        );
        $total = $application->participantCount($riddleId);
        $pages = (int) ceil($total / $request['limit']);
        $html = (string) call_user_func(
            self::$participantRenderer,
            $riddleId,
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

    private static function application(): RiddleStatisticsApplicationService {
        global $wpdb;

        return new RiddleStatisticsApplicationService(CoreServiceFactory::riddleStatistics($wpdb));
    }
}
