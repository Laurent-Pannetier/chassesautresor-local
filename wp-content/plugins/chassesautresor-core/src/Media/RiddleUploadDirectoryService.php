<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Remove the private upload directory owned by a deleted riddle.
 */
class RiddleUploadDirectoryService {
    public function getDirectoryPath(int $riddleId, string $uploadsBaseDirectory): ?string {
        if ($riddleId <= 0 || $uploadsBaseDirectory === '') {
            return null;
        }

        return rtrim($uploadsBaseDirectory, '/\\') . '/_enigmes/enigme-' . $riddleId;
    }

    public function delete(int $riddleId, string $uploadsBaseDirectory): bool {
        $directory = $this->getDirectoryPath($riddleId, $uploadsBaseDirectory);
        if ($directory === null || !is_dir($directory)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                if (!rmdir($item->getPathname())) {
                    return false;
                }
            } elseif (!unlink($item->getPathname())) {
                return false;
            }
        }

        return rmdir($directory);
    }
}
