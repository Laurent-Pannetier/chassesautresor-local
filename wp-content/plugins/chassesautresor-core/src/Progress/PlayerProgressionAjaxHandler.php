<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Messages\PlayerProgressionRenderer;

/** AJAX pagination for player rankings and personal riddle submissions. */
final class PlayerProgressionAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cat_player_progression_leaderboard', [self::class, 'leaderboard']);
        $addAction('wp_ajax_cat_player_progression_riddle_ranking', [self::class, 'riddleRanking']);
        $addAction('wp_ajax_cat_player_progression_attempts', [self::class, 'attempts']);
    }

    public static function leaderboard(): void
    {
        self::authorize();
        $userId = (int) get_current_user_id();
        $huntId = isset($_POST['hunt_id']) ? (int) $_POST['hunt_id'] : 0;
        if (!self::canViewHunt($userId, $huntId)) {
            wp_send_json_error(['message' => __('Chasse inaccessible.', 'chassesautresor-com')], 403);
        }

        $view = self::service()->leaderboard($userId, $huntId, (int) ($_POST['page'] ?? 1));
        wp_send_json_success([
            'html' => (new PlayerProgressionRenderer())->leaderboard($view),
            'page' => (int) $view['page'],
            'pages' => (int) $view['pages'],
        ]);
    }

    public static function riddleRanking(): void
    {
        self::authorize();
        $userId = (int) get_current_user_id();
        $huntId = isset($_POST['hunt_id']) ? (int) $_POST['hunt_id'] : 0;
        if (!self::canViewHunt($userId, $huntId)) {
            wp_send_json_error(['message' => __('Chasse inaccessible.', 'chassesautresor-com')], 403);
        }

        $view = self::service()->riddleRanking(
            $userId,
            $huntId,
            (int) ($_POST['riddle_id'] ?? 0),
            (int) ($_POST['page'] ?? 1)
        );
        wp_send_json_success([
            'html' => (new PlayerProgressionRenderer())->riddleRanking($view),
            'page' => (int) $view['page'],
            'pages' => (int) $view['pages'],
        ]);
    }

    public static function attempts(): void
    {
        self::authorize();
        $userId = (int) get_current_user_id();
        $huntId = isset($_POST['hunt_id']) ? (int) $_POST['hunt_id'] : 0;
        if (!self::canViewHunt($userId, $huntId)) {
            wp_send_json_error(['message' => __('Chasse inaccessible.', 'chassesautresor-com')], 403);
        }

        $view = self::service()->attempts(
            $userId,
            $huntId,
            (int) ($_POST['riddle_id'] ?? 0),
            (int) ($_POST['page'] ?? 1)
        );
        wp_send_json_success([
            'html' => (new PlayerProgressionRenderer())->attempts($view),
            'page' => (int) $view['page'],
            'pages' => (int) $view['pages'],
        ]);
    }

    private static function authorize(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Connexion requise.', 'chassesautresor-com')], 401);
        }
        if (check_ajax_referer('cat_player_progression', 'nonce', false) === false) {
            wp_send_json_error(['message' => __('Requête expirée.', 'chassesautresor-com')], 403);
        }
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

        if (
            function_exists('ca_get_user_engaged_hunt_ids')
            && in_array($huntId, array_map('intval', ca_get_user_engaged_hunt_ids($userId)), true)
        ) {
            return true;
        }

        return function_exists('cat_get_managed_hunt_id_for_user')
            && (int) cat_get_managed_hunt_id_for_user($userId) === $huntId;
    }

    private static function service(): PlayerProgressionApplicationService
    {
        global $wpdb;

        return new PlayerProgressionApplicationService(
            new ProgressionOverviewRepository($wpdb),
            new PlayerProgressionRepository($wpdb)
        );
    }
}
