<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** AJAX transport for hunt validation cancellation and CTA refreshes. */
class HuntValidationAjaxHandler {
    private static $ctaBuilder;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_annulation_validation_chasse', [self::class, 'cancel']);
        $addAction('wp_ajax_nopriv_annulation_validation_chasse', [self::class, 'cancel']);
        $addAction('wp_ajax_actualiser_cta_validation_chasse', [self::class, 'refreshCta']);
    }

    public static function configure(callable $ctaBuilder): void {
        self::$ctaBuilder = $ctaBuilder;
    }

    public static function cancel(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_redirect(home_url());
            exit;
        }
        $huntId = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
        $userId = (int) get_current_user_id();
        if ($userId <= 0 || $huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_redirect(home_url());
            exit;
        }
        $nonce = (string) ($_POST['annulation_validation_chasse_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'annulation_validation_chasse_' . $huntId)) {
            wp_die(__('Vérification de sécurité échouée.', 'chassesautresor-com'));
        }
        $allowed = current_user_can('administrator')
            || self::isOrganizerAssociatedWithHunt($userId, $huntId);
        if (!$allowed) {
            wp_die(__('Conditions non remplies.', 'chassesautresor-com'));
        }
        if (empty($_POST['annuler_validation_chasse'])) {
            wp_redirect(home_url());
            exit;
        }

        self::cancelValidation($huntId);
        wp_redirect(add_query_arg('validation_annulee', '1', get_permalink($huntId)));
        exit;
    }

    public static function cancelValidation(int $huntId): void {
        (new HuntStatusUpdater())->synchronizePublication($huntId, 'a_venir');
        update_field('chasse_cache_statut', 'a_venir', $huntId);
        update_field('chasse_cache_statut_validation', 'correction', $huntId);
    }

    public static function refreshCta(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
        if (!wp_verify_nonce((string) ($_POST['nonce'] ?? ''), 'modifier_champ_enigme')) {
            wp_send_json_error('invalid_nonce', 403);
        }
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('post_invalide');
        }
        $huntId = self::resolveHuntId($riddleId);
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('chasse_invalide');
        }
        if (!is_callable(self::$ctaBuilder)) {
            wp_send_json_error('chasse_invalide');
        }

        wp_send_json_success(['html' => (string) call_user_func(self::$ctaBuilder, $huntId, $riddleId)]);
    }

    private static function isOrganizerAssociatedWithHunt(int $userId, int $huntId): bool {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        $associatedUsers = $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));

        return in_array($userId, $associatedUsers, true);
    }

    private static function resolveHuntId(int $riddleId): int {
        return (int) ((new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $riddleId)
        ) ?? 0);
    }
}
