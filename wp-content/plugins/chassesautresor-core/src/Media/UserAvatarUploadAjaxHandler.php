<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Validate and persist custom account avatar uploads and removals. */
class UserAvatarUploadAjaxHandler {
    private const MAX_SIZE = 2097152;
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_upload_user_avatar', [self::class, 'handle']);
        $addAction('wp_ajax_remove_user_avatar', [self::class, 'remove']);
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
        $previousUrl = (string) get_user_meta($userId, 'user_avatar', true);
        $newUrl = (string) ($upload['url'] ?? '');
        update_user_meta($userId, 'user_avatar', $newUrl);
        if ($previousUrl !== '' && $previousUrl !== $newUrl) {
            self::deleteAvatarFileByUrl($previousUrl);
        }

        wp_send_json_success([
            'message' => __('Image mise à jour avec succès.', 'chassesautresor-com'),
            'new_avatar_url' => esc_url_raw($newUrl),
            'is_default' => false,
        ]);
    }

    public static function remove(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Vous devez être connecté.', 'chassesautresor-com')]);
        }

        check_ajax_referer('remove_user_avatar', 'nonce');

        $userId = (int) get_current_user_id();
        self::deleteAvatarFileByUrl((string) get_user_meta($userId, 'user_avatar', true));
        delete_user_meta($userId, 'user_avatar');

        wp_send_json_success([
            'message' => __('Photo de profil retirée.', 'chassesautresor-com'),
            'new_avatar_url' => esc_url_raw(UserAvatarHookHandler::defaultAvatarUrl()),
            'is_default' => true,
        ]);
    }

    /**
     * Relative uploads path, or null when the URL is outside that directory.
     */
    public static function uploadRelativePath(string $url, string $baseUrl): ?string {
        $baseUrl = rtrim($baseUrl, '/');
        $cleanUrl = preg_replace('/[?#].*$/', '', $url);
        if (!is_string($cleanUrl) || $baseUrl === '' || !str_starts_with($cleanUrl, $baseUrl . '/')) {
            return null;
        }

        $relative = rawurldecode(substr($cleanUrl, strlen($baseUrl)));
        $relative = str_replace('\\', '/', $relative);
        if ($relative === '' || !str_starts_with($relative, '/')) {
            return null;
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '..') {
                return null;
            }
        }

        return $relative;
    }

    private static function deleteAvatarFileByUrl(string $url): void {
        if ($url === '' || !function_exists('wp_upload_dir') || !function_exists('wp_delete_file')) {
            return;
        }

        $uploads = wp_upload_dir();
        if (!is_array($uploads)) {
            return;
        }

        $baseUrl = rtrim((string) ($uploads['baseurl'] ?? ''), '/');
        $baseDir = rtrim((string) ($uploads['basedir'] ?? ''), '/');
        $relative = self::uploadRelativePath($url, $baseUrl);
        if ($relative === null || $baseDir === '') {
            return;
        }

        $path = $baseDir . $relative;
        $realBase = realpath($baseDir);
        $realFile = realpath($path);
        if ($realBase === false || $realFile === false || !is_file($realFile)) {
            return;
        }

        $realBase = rtrim(str_replace('\\', '/', $realBase), '/');
        $realFile = str_replace('\\', '/', $realFile);
        if (!str_starts_with($realFile, $realBase . '/')) {
            return;
        }

        wp_delete_file($realFile);
    }
}
