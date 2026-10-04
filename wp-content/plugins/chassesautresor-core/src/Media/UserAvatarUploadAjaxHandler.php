<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Validate and persist custom account avatar uploads. */
class UserAvatarUploadAjaxHandler {
    private const MAX_SIZE = 2097152;
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_upload_user_avatar', [self::class, 'handle']);
    }

    public static function handle(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Vous devez être connecté.', 'chassesautresor-com')]);
        }

        check_ajax_referer('upload_user_avatar', 'nonce');

        if (!isset($_FILES['avatar']) || !is_array($_FILES['avatar'])) {
            wp_send_json_error(['message' => __('Aucun fichier reçu.', 'chassesautresor-com')]);
        }

        $file = $_FILES['avatar'];
        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            wp_send_json_error(['message' => __('Taille dépassée : 2 Mo max.', 'chassesautresor-com')]);
        }
        if (!in_array((string) ($file['type'] ?? ''), self::ALLOWED_TYPES, true)) {
            wp_send_json_error([
                'message' => __('Format non autorisé. Formats autorisés : JPG, PNG, GIF, WEBP.', 'chassesautresor-com'),
            ]);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($file, ['test_form' => false]);
        if (!$upload || isset($upload['error'])) {
            $error = is_array($upload) ? (string) ($upload['error'] ?? __('Inconnue', 'chassesautresor-com')) : '';
            wp_send_json_error([
                'message' => __('Erreur lors du téléversement : ', 'chassesautresor-com') . $error,
            ]);
        }

        $userId = (int) get_current_user_id();
        update_user_meta($userId, 'user_avatar', $upload['url']);
        wp_send_json_success([
            'message' => __('Image mise à jour avec succès.', 'chassesautresor-com'),
            'new_avatar_url' => esc_url(get_user_meta($userId, 'user_avatar', true)),
        ]);
    }
}
