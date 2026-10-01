<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * AJAX adapter for temporary riddle image protection controls.
 */
class RiddleImageProtectionAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_desactiver_htaccess_enigme', [self::class, 'disable'], 10, 1);
        $addAction('wp_ajax_reactiver_htaccess_immediat_enigme', [self::class, 'restore'], 10, 1);
        $addAction('wp_ajax_get_expiration_htaccess_enigme', [self::class, 'expiration'], 10, 1);
        $addAction('wp_ajax_verrouillage_termine_enigme', [self::class, 'finish'], 10, 1);
    }

    public static function disable(): void {
        $riddleId = self::guard();
        $result = (new RiddleImageProtectionService())->disable($riddleId);
        if (!$result['success']) {
            wp_send_json_error($result['message']);
        }
        wp_send_json_success($result['message']);
    }

    public static function restore(): void {
        $riddleId = self::guard();
        do_action('chassesautresor_reinject_riddle_image_protection', $riddleId);
        (new RiddleImageProtectionService())->restore($riddleId, false);
        wp_send_json_success('Protection restaurée immédiatement');
    }

    public static function expiration(): void {
        $riddleId = self::guard();
        $expiration = (new RiddleImageProtectionService())->getExpiration($riddleId);
        if ($expiration['active']) {
            wp_send_json_success(['timestamp' => $expiration['timestamp']]);
        }
        wp_send_json_error($expiration['expired'] ? 'Délai expiré' : 'Aucune désactivation active');
    }

    public static function finish(): void {
        $riddleId = self::guard();
        (new RiddleImageProtectionService())->restore($riddleId, false);
        wp_send_json_success('Verrouillage terminé et protection rétablie');
    }

    private static function guard(): int {
        check_ajax_referer('modifier_champ_enigme', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Non autorisé');
        }
        $riddleId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error('ID invalide');
        }
        if (!\utilisateur_peut_modifier_post($riddleId)) {
            wp_send_json_error('Droits insuffisants');
        }

        return $riddleId;
    }
}
