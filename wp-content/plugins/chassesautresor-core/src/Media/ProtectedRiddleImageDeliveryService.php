<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Keep inline thumbnail reads inside the uploads directory.
 *
 * Riddle images themselves are static files. This service no longer proxies
 * them and does not send a private cache header.
 */
final class ProtectedRiddleImageDeliveryService
{
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
}
