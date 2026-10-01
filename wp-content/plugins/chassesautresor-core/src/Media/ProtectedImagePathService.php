<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Resolve the local file used to serve a protected riddle image. */
final class ProtectedImagePathService
{
    private const CACHE_GROUP = 'trouver_chemin_image';

    /**
     * @return array{path: string, mime: string}|null
     */
    public function find(int $imageId, string $size = 'full'): ?array
    {
        $cacheKey = sprintf('%d_%s', $imageId, $size);
        $cached = wp_cache_get($cacheKey, self::CACHE_GROUP, false, $found);
        if ($found) {
            return is_array($cached) ? $cached : null;
        }

        $source = wp_get_attachment_image_src($imageId, $size === 'full' ? 'full' : $size);
        $url = is_array($source) ? ($source[0] ?? null) : null;
        if (!is_string($url) || $url === '') {
            return $this->cache($cacheKey, null);
        }

        $uploads = wp_get_upload_dir();
        $path = str_replace((string) $uploads['baseurl'], (string) $uploads['basedir'], $url);
        $webpPath = (string) preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $path);
        if ($webpPath !== $path && is_file($webpPath)) {
            return $this->cache($cacheKey, ['path' => $webpPath, 'mime' => 'image/webp']);
        }

        if (is_file($path)) {
            return $this->cache($cacheKey, [
                'path' => $path,
                'mime' => $this->mimeType($path),
            ]);
        }

        if ($size !== 'full') {
            return $this->cache($cacheKey, $this->find($imageId));
        }

        return $this->cache($cacheKey, null);
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
