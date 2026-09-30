<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Validate and schedule legacy PDF solution files attached to riddles.
 */
class RiddleSolutionFilePolicyService {
    public const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public function getUploadError(int $fileSize, string $extension, string $mimeType): ?string {
        if ($fileSize > self::MAX_FILE_SIZE) {
            return 'file_too_large';
        }

        if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
            return 'invalid_file_type';
        }

        return null;
    }

    public function getPublicationTimestamp(
        string $mode,
        ?int $delayDays,
        ?string $publicationTime,
        int $currentTimestamp
    ): ?int {
        if (!in_array($mode, ['fin_de_chasse', 'delai_fin_chasse', 'date_fin_chasse'], true)) {
            return null;
        }

        if ($delayDays === null || $publicationTime === null) {
            return null;
        }

        $timestamp = strtotime(
            sprintf('+%d days %s', $delayDays, $publicationTime),
            $currentTimestamp
        );
        if ($timestamp === false) {
            return null;
        }

        return max($timestamp, $currentTimestamp + 5);
    }
}
