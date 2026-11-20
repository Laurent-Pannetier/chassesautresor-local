<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Finish cutting riddle strips the page only described.
 *
 * The browser sends attachment ids. A file is cut only when it lives under
 * uploads/_enigmes and the viewer is allowed to see that image.
 */
final class RiddleReadingBandPreparation
{
    public const ACTION = 'cta_prepare_riddle_bands';

    public const MAX_IMAGES = 12;

    /**
     * @param list<mixed> $imageIds
     * @param callable(int): bool $allowed
     * @param callable(int): string $absoluteFile
     * @param callable(string): array<int, array<string, mixed>> $ensure
     * @return list<int>
     */
    public function prepare(
        array $imageIds,
        callable $allowed,
        callable $absoluteFile,
        callable $ensure
    ): array {
        $done = [];
        $seen = [];
        foreach ($imageIds as $imageId) {
            if (count($done) >= self::MAX_IMAGES) {
                break;
            }
            $imageId = (int) $imageId;
            if ($imageId <= 0 || isset($seen[$imageId]) || !$allowed($imageId)) {
                continue;
            }
            $seen[$imageId] = true;
            $file = str_replace('\\', '/', (string) $absoluteFile($imageId));
            if ($file === '' || !is_file($file) || !str_contains($file, '/_enigmes/')) {
                continue;
            }
            $bands = $ensure($file);
            if (!$this->finished($bands)) {
                continue;
            }
            $done[] = $imageId;
        }

        return $done;
    }

    /**
     * @param array<int, mixed> $bands
     */
    private function finished(array $bands): bool
    {
        if ($bands === []) {
            return false;
        }
        foreach ($bands as $band) {
            if (!is_array($band) || ($band['ready'] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }
}
