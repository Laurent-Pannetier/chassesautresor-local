<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleRenderCacheHookHandler;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** AJAX transport for winners and player progression sidebar fragments. */
class RiddleSidebarAjaxHandler {
    private static $winnersRenderer;
    private static $progressionRenderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_enigme_recuperer_gagnants', [self::class, 'winners']);
        $addAction('wp_ajax_nopriv_enigme_recuperer_gagnants', [self::class, 'winners']);
        $addAction('wp_ajax_enigme_recuperer_progression', [self::class, 'progression']);
        $addAction('wp_ajax_nopriv_enigme_recuperer_progression', [self::class, 'progression']);
    }

    public static function configure(
        callable $winnersRenderer,
        callable $progressionRenderer
    ): void {
        self::$winnersRenderer = $winnersRenderer;
        self::$progressionRenderer = $progressionRenderer;
    }

    public static function winners(): void {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $error = (new RiddleSidebarRequestPolicy())->winners(
            self::nonceValid(),
            $riddleId,
            $riddleId > 0 ? (string) get_post_type($riddleId) : '',
            $riddleId > 0 ? (string) get_field('enigme_mode_validation', $riddleId) : ''
        );
        if ($error !== null) {
            wp_send_json_error($error, $error === 'invalid_nonce' ? 403 : 400);
        }
        if (!is_callable(self::$winnersRenderer)) {
            wp_send_json_error('missing_enigme', 400);
        }

        $page = max(1, (int) ($_POST['page'] ?? 1));
        wp_send_json_success([
            'html' => (string) call_user_func(
                self::$winnersRenderer,
                $riddleId,
                (int) get_current_user_id(),
                $page
            ),
        ]);
    }

    public static function progression(): void {
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $relatedHuntId = (int) ((new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        ) ?? 0);
        $error = (new RiddleSidebarRequestPolicy())->progression(
            is_user_logged_in(),
            self::nonceValid(),
            $huntId,
            $huntId > 0 ? (string) get_post_type($huntId) : '',
            $riddleId,
            $riddleId > 0 ? (string) get_post_type($riddleId) : '',
            $relatedHuntId
        );
        if ($error !== null) {
            wp_send_json_error($error, $error === 'non_connecte' || $error === 'invalid_nonce' ? 403 : 400);
        }
        if (!is_callable(self::$progressionRenderer)) {
            wp_send_json_error('missing_chasse', 400);
        }

        $userId = (int) get_current_user_id();
        RiddleRenderCacheHookHandler::clearSidebar($huntId, $userId);
        wp_cache_delete('enigme_sidebar_resolution_' . $riddleId, 'chassesautresor');
        wp_send_json_success([
            'html' => (string) call_user_func(self::$progressionRenderer, $huntId, $riddleId, $userId),
        ]);
    }

    private static function nonceValid(): bool {
        return wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'riddle_sidebar') !== false;
    }
}
