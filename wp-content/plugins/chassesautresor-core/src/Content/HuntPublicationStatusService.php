<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Map a hunt validation status to its WordPress publication status.
 */
class HuntPublicationStatusService
{
    public function resolve(string $validationStatus): string
    {
        if ($validationStatus === 'valide') {
            return 'publish';
        }

        if ($validationStatus === 'banni') {
            return 'draft';
        }

        return 'pending';
    }
}
