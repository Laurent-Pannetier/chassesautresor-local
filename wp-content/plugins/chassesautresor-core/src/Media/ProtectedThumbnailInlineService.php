<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Embed a small riddle thumbnail in HTML already rendered for this viewer.
 *
 * A signed /voir-image-enigme URL is a bearer link: the proxy still checks the
 * signature and the current viewer's access on every request. These bytes have
 * no URL of their own. The caller must already have decided that this viewer
 * may see the enigma. A file above the thumbnail cap stays on the proxy.
 */
final class ProtectedThumbnailInlineService
{
    public const MAX_BYTES = 65536;

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @param callable(int,string,string):(?array{path:string,mime:string}) $locate */
    public function __construct(
        private $locate,
        private ProtectedRiddleImageDeliveryService $delivery,
        private int $maxBytes = self::MAX_BYTES
    ) {
    }

    public function html(int $imageId, string $alt = ''): string
    {
        if ($imageId <= 0 || $this->maxBytes <= 0) {
            return '';
        }

        $seen = [];
        foreach (['webp', ''] as $format) {
            $info = ($this->locate)($imageId, 'thumbnail', $format);
            if (!is_array($info)) {
                continue;
            }
            $path = (string) ($info['path'] ?? '');
            if ($path === '' || isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;
            $html = $this->renderFile($path, $alt);
            if ($html !== '') {
                return $html;
            }
        }

        return '';
    }

    private function renderFile(string $path, string $alt): string
    {
        $uploads = function_exists('wp_get_upload_dir') ? wp_get_upload_dir() : [];
        $basedir = is_array($uploads) ? (string) ($uploads['basedir'] ?? '') : '';
        $safe = $this->delivery->confineToUploads($path, $basedir);
        if ($safe === null || !is_file($safe)) {
            return '';
        }

        $size = filesize($safe);
        if ($size === false || $size <= 0 || $size > $this->maxBytes) {
            return '';
        }

        $bytes = file_get_contents($safe);
        if (!is_string($bytes) || $bytes === '') {
            return '';
        }

        $info = @getimagesizefromstring($bytes);
        if (!is_array($info)) {
            return '';
        }
        $mime = (string) ($info['mime'] ?? '');
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return '';
        }

        $src = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $html = '<img src="' . esc_attr($src) . '" alt="' . esc_attr($alt) . '"';
        if ($width > 0 && $height > 0) {
            $html .= ' width="' . $width . '" height="' . $height . '"';
        }

        return $html . ' loading="eager" decoding="sync">';
    }
}
