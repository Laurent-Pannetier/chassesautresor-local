<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for refreshing a hunt hint card.
 */
class HintCardAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_lister_indices', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hint_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide');
        }
        if (!(new RelatedContentAccessResolver())->canPerform('edit', 'chasse', $huntId)) {
            wp_send_json_error('acces_refuse');
        }

        $html = (string) apply_filters('chassesautresor_render_hint_card', '', $huntId);
        wp_send_json_success(['html' => $html]);
    }
}
