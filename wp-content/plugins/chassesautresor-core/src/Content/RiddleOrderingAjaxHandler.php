<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for ordering riddles within a hunt.
 */
class RiddleOrderingAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_reordonner_enigmes', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('reordonner_enigmes', 'nonce');

        $isAuthenticated = is_user_logged_in();
        $huntId = $isAuthenticated ? (int) ($_POST['chasse_id'] ?? 0) : 0;
        $hasValidTarget = $huntId > 0 && get_post_type($huntId) === 'chasse';
        $isAuthorized = $hasValidTarget && self::isUserAssociated(
            get_current_user_id(),
            $huntId
        );
        $error = (new RiddleActionPolicyService())->getError(
            $isAuthenticated,
            $hasValidTarget,
            $isAuthorized
        );

        if ($error === 'authentication_required') {
            wp_send_json_error('non_connecte');
        }
        if ($error === 'invalid_target') {
            wp_send_json_error('id_invalide');
        }
        if ($error === 'forbidden') {
            wp_send_json_error('non_autorise');
        }

        $order = array_map('intval', (array) ($_POST['ordre'] ?? []));
        (new RiddleOrderingService())->apply(
            $order,
            self::riddleIds($huntId),
            static function (int $riddleId, int $menuOrder): void {
                wp_update_post(['ID' => $riddleId, 'menu_order' => $menuOrder]);
            }
        );

        wp_send_json_success();
    }

    private static function isUserAssociated(int $userId, int $huntId): bool {
        if ($userId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(
            get_field('chasse_cache_organisateur', $huntId)
        );
        if ($organizerId === null) {
            return false;
        }

        $users = get_field('utilisateurs_associes', $organizerId);

        return is_array($users)
            && in_array($userId, $relationships->normalizeIds($users), true);
    }

    /** @return int[] */
    private static function riddleIds(int $huntId): array {
        $cache = get_field(RiddleCacheMutationService::FIELD_NAME, $huntId);
        $ids = (new RelationshipService())->normalizeIds(is_array($cache) ? $cache : []);

        return array_values(array_filter(
            array_unique($ids),
            static fn (int $riddleId): bool => get_post_type($riddleId) === 'enigme'
        ));
    }
}
