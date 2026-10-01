<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * WordPress AJAX adapter for riddles selectable as hint targets.
 */
class HintRiddleOptionsAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_lister_enigmes', [self::class, 'handle'], 10, 1);
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
        if (!(new HintAccessResolver())->canPerform('create', 'chasse', $huntId)) {
            wp_send_json_error('acces_refuse');
        }

        $riddles = (array) apply_filters('chassesautresor_hint_target_riddles', [], $huntId);
        $nextRank = (int) apply_filters('chassesautresor_next_hint_rank', 1, $huntId, 'chasse');
        $excludeSolutions = !empty($_POST['sans_solution']);
        $options = (new HintManagementService())->buildRiddleOptions(
            $riddles,
            $nextRank,
            static fn ($riddle): bool => $excludeSolutions && (bool) apply_filters(
                'chassesautresor_hint_target_has_solution',
                false,
                (int) $riddle->ID,
                'enigme'
            ),
            static fn ($riddle): string => (string) get_the_title($riddle)
        );

        wp_send_json_success(['enigmes' => $options]);
    }
}
