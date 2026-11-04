<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Build the static file URL of a riddle image. Attachment ids stay in content.
 *
 * @phpstan-type Resolver callable(int, string, string): string
 */
final class RiddleImagePublicUrlService
{
    /** @param (callable(int, string, string): string)|null $resolve */
    public function __construct(private $resolve = null)
    {
    }

    public function url(int $imageId, string $size = 'full', string $format = ''): string
    {
        if ($imageId <= 0) {
            return '';
        }

        $resolve = $this->resolve ?? [$this, 'resolveFromDisk'];
        $url = $resolve($imageId, $size !== '' ? $size : 'full', strtolower($format));

        return is_string($url) ? $url : '';
    }

    private function resolveFromDisk(int $imageId, string $size, string $format): string
    {
        $info = (new ProtectedImagePathService())->find($imageId, $size, $format);
        if ($info === null || !function_exists('wp_get_upload_dir')) {
            return '';
        }

        $uploads = wp_get_upload_dir();
        $basedir = rtrim(str_replace('\\', '/', (string) ($uploads['basedir'] ?? '')), '/');
        $baseurl = rtrim((string) ($uploads['baseurl'] ?? ''), '/');
        $path = str_replace('\\', '/', (string) ($info['path'] ?? ''));
        if ($basedir === '' || $baseurl === '' || !str_starts_with($path, $basedir . '/')) {
            return '';
        }

        return $baseurl . substr($path, strlen($basedir));
    }
}
