<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress AJAX adapter for deleting a riddle.
 */
class RiddleDeletionAjaxHandler {
    private const ORGANIZER_ROLES = ['organisateur', 'organisateur_creation'];

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_supprimer_enigme', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('supprimer_enigme', 'nonce');

        $isAuthenticated = is_user_logged_in();
        $riddleId = $isAuthenticated ? (int) ($_POST['post_id'] ?? 0) : 0;
        $hasValidTarget = $riddleId > 0 && get_post_type($riddleId) === 'enigme';
        $isAuthorized = $hasValidTarget && self::canDelete(
            $riddleId,
            get_current_user_id()
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
            wp_send_json_error('acces_refuse');
        }

        $huntId = self::huntId($riddleId);
        $redirect = $huntId > 0 ? get_permalink($huntId) : home_url('/');
        $uploads = wp_upload_dir();
        $deleted = (new RiddleDeletionService())->delete(
            $riddleId,
            (string) ($uploads['basedir'] ?? '')
        );
        if (!$deleted) {
            wp_send_json_error('echec_suppression');
        }

        wp_send_json_success(['redirect' => $redirect]);
    }

    private static function canDelete(int $riddleId, int $userId): bool {
        $huntId = self::huntId($riddleId);
        $hasHunt = $huntId > 0 && get_post_type($huntId) === 'chasse';
        $user = $userId > 0 ? get_userdata($userId) : false;
        $roles = $user ? (array) $user->roles : [];
        $isOrganizer = (bool) array_intersect(self::ORGANIZER_ROLES, $roles);
        $isAssociated = $isOrganizer && $hasHunt && self::isAssociated($userId, $huntId);

        return (new RiddleManagementService())->canDelete(
            true,
            $userId > 0,
            $isOrganizer,
            $hasHunt,
            $hasHunt ? (string) get_field('chasse_cache_statut', $huntId) : '',
            $hasHunt ? (string) get_field('chasse_cache_statut_validation', $huntId) : '',
            $isAssociated
        );
    }

    private static function isAssociated(int $userId, int $huntId): bool {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(
            get_field('chasse_cache_organisateur', $huntId)
        );
        $users = $organizerId ? get_field('utilisateurs_associes', $organizerId) : [];

        return is_array($users)
            && in_array($userId, $relationships->normalizeIds($users), true);
    }

    private static function huntId(int $riddleId): int {
        return (new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        ) ?? 0;
    }
}
