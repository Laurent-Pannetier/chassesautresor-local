<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Resolve the local file used to serve a protected riddle image. */
final class ProtectedImagePathService
{
    private const CACHE_GROUP = 'trouver_chemin_image';

    public function normalizeFormat(string $format): string
    {
        return strtolower($format) === 'webp' ? 'webp' : '';
    }

    /**
     * Keep the original file unless WebP was explicitly requested and the sibling exists.
     *
     * @return array{path: string, mime: string}
     */
    public function applyFormat(string $path, string $format): array
    {
        if ($this->normalizeFormat($format) === 'webp') {
            $webpPath = (string) preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $path);
            if ($webpPath !== $path && is_file($webpPath)) {
                return ['path' => $webpPath, 'mime' => 'image/webp'];
            }
        }

        return ['path' => $path, 'mime' => $this->mimeType($path)];
    }

    /**
     * @return array{path: string, mime: string}|null
     */
    public function find(int $imageId, string $size = 'full', string $format = ''): ?array
    {
        $format = $this->normalizeFormat($format);
        $cacheKey = sprintf('%d_%s_%s', $imageId, $size, $format === '' ? 'orig' : $format);
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP, false, $found);
        if ($found) {
            return is_array($cached) ? $cached : null;
        }

        $path = $this->resolvePath($imageId, $size);
        if ($path === null) {
            if ($size !== 'full') {
                return $this->cache($cacheKey, $this->find($imageId, 'full', $format));
            }

            return $this->cache($cacheKey, null);
        }

        $formatted = $this->applyFormat($path, $format);
        if ($format === 'webp' && $formatted['mime'] !== 'image/webp') {
            if ($size !== 'full') {
                return $this->cache($cacheKey, $this->find($imageId, 'full', $format));
            }

            return $this->cache($cacheKey, null);
        }

        return $this->cache($cacheKey, $formatted);
    }

    /**
     * Metadata URL can drift after a move into the protected folder.
     */
    private function resolvePath(int $imageId, string $size): ?string
    {
        $source = wp_get_attachment_image_src($imageId, $size === 'full' ? 'full' : $size);
        $url = is_array($source) ? ($source[0] ?? null) : null;
        $path = '';
        if (is_string($url) && $url !== '') {
            $uploads = wp_get_upload_dir();
            $path = str_replace((string) $uploads['baseurl'], (string) $uploads['basedir'], $url);
        }

        if ($path === '' || !is_file($path)) {
            $path = $this->attachedFallback($imageId, $size) ?? '';
        }

        return $path !== '' && is_file($path) ? $path : null;
    }

    private function attachedFallback(int $imageId, string $size): ?string
    {
        if (!function_exists('get_attached_file')) {
            return null;
        }

        $attached = get_attached_file($imageId);
        if (!is_string($attached) || $attached === '' || !is_file($attached)) {
            return null;
        }

        if ($size === 'full' || !function_exists('wp_get_attachment_metadata')) {
            return $attached;
        }

        $meta = wp_get_attachment_metadata($imageId);
        $sizeFile = is_array($meta) && isset($meta['sizes'][$size]['file'])
            ? (string) $meta['sizes'][$size]['file']
            : '';
        if ($sizeFile === '') {
            return $attached;
        }

        $candidate = rtrim(dirname($attached), '/\\') . '/' . basename($sizeFile);

        return is_file($candidate) ? $candidate : $attached;
    }

    /**
     * @param array{path: string, mime: string}|null $value
     * @return array{path: string, mime: string}|null
     */
    private function cache(string $key, ?array $value): ?array
    {
        wp_cache_set($key, $value, self::CACHE_GROUP);
        return $value;
    }

    private function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };
    }
}
