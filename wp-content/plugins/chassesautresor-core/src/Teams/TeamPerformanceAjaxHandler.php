<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Paginate the hunt-scoped team leaderboard without reloading the page. */
final class TeamPerformanceAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cat_team_performance_ranking', [self::class, 'ranking']);
    }

    public static function ranking(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Connexion requise.', 'chassesautresor-com')], 401);
        }
        if (check_ajax_referer('cat_team_performance', 'nonce', false) === false) {
            wp_send_json_error(['message' => __('Requête expirée.', 'chassesautresor-com')], 403);
        }

        $userId = (int) get_current_user_id();
        $huntId = (int) ($_POST['hunt_id'] ?? 0);
        if (!self::canViewHunt($userId, $huntId)) {
            wp_send_json_error(['message' => __('Chasse inaccessible.', 'chassesautresor-com')], 403);
        }

        global $wpdb;
        $repository = new TeamPerformanceRepository($wpdb);
        $teamId = (new TeamRepository($wpdb))->activeTeamId($userId);
        $view = (new TeamPerformanceApplicationService($repository))->ranking(
            $teamId,
            $huntId,
            (int) ($_POST['page'] ?? 1),
            $userId
        );
        $renderer = new TeamPageRenderer(
            static function (int $attachmentId): string {
                $url = function_exists('wp_get_attachment_image_url')
                    ? wp_get_attachment_image_url($attachmentId, 'thumbnail')
                    : '';

                return is_string($url) ? $url : '';
            },
            function_exists('cta_shell_icon') ? (string) cta_shell_icon('team') : ''
        );
        wp_send_json_success([
            'html' => $renderer->ranking($view),
            'page' => (int) $view['page'],
            'pages' => (int) $view['pages'],
        ]);
    }

    private static function canViewHunt(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }
        if (function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode()) {
            return function_exists('cat_get_primary_hunt_id')
                && (int) cat_get_primary_hunt_id() === $huntId;
        }

        return function_exists('ca_get_user_engaged_hunt_ids')
            && in_array($huntId, array_map('intval', ca_get_user_engaged_hunt_ids($userId)), true);
    }
}
