<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Deliver a protected riddle image via LiteSpeed internal redirect when available.
 */
final class ProtectedRiddleImageDeliveryService
{
    public function isLiteSpeedServer(?string $serverSoftware = null): bool
    {
        $software = $serverSoftware ?? (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');

        return stripos($software, 'LiteSpeed') !== false;
    }

    public function absolutePathToUri(
        string $absolutePath,
        string $uploadsBasedir,
        string $uploadsBaseurl
    ): ?string {
        $basedir = rtrim(str_replace('\\', '/', $uploadsBasedir), '/');
        $path = str_replace('\\', '/', $absolutePath);
        if ($basedir === '' || !str_starts_with($path, $basedir . '/')) {
            return null;
        }

        $url = rtrim($uploadsBaseurl, '/') . substr($path, strlen($basedir));
        $uri = parse_url($url, PHP_URL_PATH);
        if (!is_string($uri) || $uri === '' || !str_starts_with($uri, '/')) {
            return null;
        }

        return $uri;
    }

    /**
     * Resolve a file and refuse anything that is not a real file inside uploads.
     */
    public function confineToUploads(string $absolutePath, ?string $uploadsBasedir = null): ?string
    {
        $basedir = $uploadsBasedir ?? (string) (wp_get_upload_dir()['basedir'] ?? '');
        $basedir = rtrim(str_replace('\\', '/', $basedir), '/');
        if ($basedir === '') {
            return null;
        }

        $realBase = realpath($basedir);
        $realPath = realpath($absolutePath);
        if ($realBase === false || $realPath === false || !is_file($realPath)) {
            return null;
        }

        $realBase = rtrim(str_replace('\\', '/', $realBase), '/');
        $realPath = str_replace('\\', '/', $realPath);
        if ($realPath !== $realBase && !str_starts_with($realPath, $realBase . '/')) {
            return null;
        }

        return $realPath;
    }

    /**
     * Attempt LiteSpeed internal redirect. Returns true when the response should end.
     */
    public function tryLiteSpeedSend(string $absolutePath, string $mime, ?string $serverSoftware = null): bool
    {
        if (!$this->isLiteSpeedServer($serverSoftware)) {
            return false;
        }

        $uploads = wp_get_upload_dir();
        $uri = $this->absolutePathToUri(
            $absolutePath,
            (string) ($uploads['basedir'] ?? ''),
            (string) ($uploads['baseurl'] ?? '')
        );
        if ($uri === null) {
            return false;
        }

        header('Content-Type: ' . $mime);
        header('X-LiteSpeed-Location: ' . $uri);

        return true;
    }
}
