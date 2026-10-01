<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Secure AJAX transport for hunt status refreshes and badge reads.
 */
class HuntStatusAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_forcer_recalcul_statut_chasse', [self::class, 'recalculate'], 10, 1);
        $addAction('wp_ajax_recuperer_statut_chasse', [self::class, 'getStatus'], 10, 1);
    }

    public static function recalculate(): void {
        $huntId = self::guardRequest();
        do_action('chassesautresor_hunt_status_refresh_requested', $huntId);
        wp_send_json_success('statut_recalcule');
    }

    public static function getStatus(): void {
        $huntId = self::guardRequest();
        $status = get_field('chasse_cache_statut', $huntId);
        if (!is_string($status) || $status === '') {
            wp_send_json_error('statut_indisponible');
        }
        $validation = get_field('chasse_cache_statut_validation', $huntId);
        $badge = (new HuntStatusBadgeService())->build(
            $status,
            is_string($validation) ? $validation : null
        );

        wp_send_json_success([
            'statut' => (string) ($badge['statut'] ?? $status),
            'statut_label' => (string) ($badge['label'] ?? $status),
            'statut_icon' => (string) ($badge['icon_html'] ?? ''),
            'statut_tooltip' => (string) ($badge['label'] ?? $status),
        ]);
    }

    private static function guardRequest(): int {
        check_ajax_referer('hunt_management', 'nonce');
        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }
        $huntId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide');
        }
        if (!\utilisateur_peut_modifier_post($huntId)) {
            wp_send_json_error('acces_refuse');
        }

        return $huntId;
    }
}
