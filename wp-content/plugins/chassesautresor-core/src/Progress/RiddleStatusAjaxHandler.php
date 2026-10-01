<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Secure AJAX transport for on-demand riddle status refreshes.
 */
class RiddleStatusAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_forcer_recalcul_statut_enigme', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('modifier_champ_enigme', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
        $riddleId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('post_invalide');
        }
        if (!apply_filters('chassesautresor_can_modify_riddle', false, $riddleId)) {
            wp_send_json_error('acces_refuse');
        }

        do_action('chassesautresor_riddle_state_refresh_requested', $riddleId);
        wp_send_json_success('statut_enigme_recalcule');
    }
}
