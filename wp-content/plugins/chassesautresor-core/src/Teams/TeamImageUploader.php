<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Teams;

/** Stores an optional team portrait without requiring the upload_files capability. */
final class TeamImageUploader
{
    public const MAX_BYTES = 2097152;

    /** @var array<string, string> */
    private const MIMES = [
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * @param array<string, mixed>|null $file
     * @return array{id: ?int, error: bool}
     */
    public function store(?array $file): array
    {
        if ($file === null || !isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['id' => null, 'error' => false];
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['id' => null, 'error' => true];
        }

        $size = (int) ($file['size'] ?? 0);
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($size <= 0 || $size > self::MAX_BYTES || $tmp === '' || !is_uploaded_file($tmp)) {
            return ['id' => null, 'error' => true];
        }
        if (!function_exists('wp_check_filetype_and_ext') || !function_exists('wp_upload_bits')) {
            return ['id' => null, 'error' => true];
        }

        $name = function_exists('sanitize_file_name')
            ? sanitize_file_name((string) ($file['name'] ?? 'equipe'))
            : 'equipe';
        $checked = wp_check_filetype_and_ext($tmp, $name, self::MIMES);
        $type = is_array($checked) ? (string) ($checked['type'] ?? '') : '';
        if (!in_array($type, self::MIMES, true)) {
            return ['id' => null, 'error' => true];
        }

        $contents = file_get_contents($tmp);
        if (!is_string($contents) || $contents === '') {
            return ['id' => null, 'error' => true];
        }

        $this->loadMedia();
        $upload = wp_upload_bits($name, null, $contents);
        if (!is_array($upload) || !empty($upload['error']) || empty($upload['file'])) {
            return ['id' => null, 'error' => true];
        }

        $title = preg_replace('/\.[^.]+$/', '', $name);
        $attachmentId = wp_insert_attachment([
            'post_mime_type' => $type,
            'post_title' => is_string($title) && $title !== '' ? $title : 'equipe',
            'post_content' => '',
            'post_status' => 'inherit',
        ], (string) $upload['file']);
        if (!is_numeric($attachmentId) || (int) $attachmentId <= 0) {
            return ['id' => null, 'error' => true];
        }

        $attachmentId = (int) $attachmentId;
        if (function_exists('wp_generate_attachment_metadata')) {
            $meta = wp_generate_attachment_metadata($attachmentId, (string) $upload['file']);
            if (is_array($meta) && function_exists('wp_update_attachment_metadata')) {
                wp_update_attachment_metadata($attachmentId, $meta);
            }
        }

        return ['id' => $attachmentId, 'error' => false];
    }

    private function loadMedia(): void
    {
        if (!defined('ABSPATH')) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
}
