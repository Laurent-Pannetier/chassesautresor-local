<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;

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

        $riddles = (new \WP_Query(
            (new HuntRiddleQueryService())->getVisibleRiddlesQueryArgs($huntId)
        ))->posts;
        $rankQuery = (new HintQueryService())->getRankedHintIdsQueryArgs($huntId, 'chasse');
        $nextRank = count($rankQuery === [] ? [] : get_posts($rankQuery)) + 1;
        $excludeSolutions = !empty($_POST['sans_solution']);
        $solutionQueries = new SolutionQueryService();
        $options = (new HintManagementService())->buildRiddleOptions(
            $riddles,
            $nextRank,
            static function ($riddle) use ($excludeSolutions, $solutionQueries): bool {
                if (!$excludeSolutions) {
                    return false;
                }
                $query = $solutionQueries->getExistingSolutionIdsQueryArgs((int) $riddle->ID, 'enigme');
                return $query !== [] && (new \WP_Query($query))->posts !== [];
            },
            static fn ($riddle): string => (string) get_the_title($riddle)
        );

        wp_send_json_success(['enigmes' => $options]);
    }
}
