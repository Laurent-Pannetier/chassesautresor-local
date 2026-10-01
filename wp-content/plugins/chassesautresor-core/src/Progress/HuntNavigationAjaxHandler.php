<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX transport for hunt navigation refreshes. */
class HuntNavigationAjaxHandler {
    private static $navigationBuilder;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_chasse_recuperer_navigation', [self::class, 'handle']);
        $addAction('wp_ajax_nopriv_chasse_recuperer_navigation', [self::class, 'handle']);
    }

    public static function configure(callable $navigationBuilder): void {
        self::$navigationBuilder = $navigationBuilder;
    }

    public static function handle(): void {
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        if (!wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'hunt_navigation')) {
            wp_send_json_error('invalid_nonce', 403);
        }
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide', 400);
        }
        $userId = (int) get_current_user_id();
        if (!self::canView($userId, $huntId)) {
            wp_send_json_error('non_engage', 403);
        }
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $builder = self::$navigationBuilder ?? 'sidebar_prepare_chasse_nav';
        $data = (array) call_user_func($builder, $huntId, $userId, $riddleId);
        wp_send_json_success([
            'html' => implode('', (array) ($data['menu_items'] ?? [])),
            'ids' => array_values((array) ($data['visible_ids'] ?? [])),
        ]);
    }

    private static function canView(int $userId, int $huntId): bool {
        global $wpdb;

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $organizerUsers = $organizerId !== null
            ? $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId))
            : [];

        return (new HuntNavigationAccessService())->canView(
            $userId,
            current_user_can('manage_options'),
            in_array($userId, $organizerUsers, true),
            CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId)
        );
    }
}
