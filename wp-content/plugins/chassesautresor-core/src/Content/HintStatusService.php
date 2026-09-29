<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Resolve the completeness, availability and publication state of a hint.
 */
class HintStatusService
{
    /**
     * @return array{complete:bool,state:string}
     */
    public function resolve(
        bool $hasContent,
        bool $hasImage,
        string $availability,
        ?int $availabilityTimestamp,
        int $currentTimestamp
    ): array {
        if (!$hasContent && !$hasImage) {
            return ['complete' => false, 'state' => 'desactive'];
        }

        if ($availability !== 'differe') {
            return ['complete' => true, 'state' => 'accessible'];
        }

        if ($availabilityTimestamp === null) {
            return ['complete' => false, 'state' => 'desactive'];
        }

        return [
            'complete' => true,
            'state' => $availabilityTimestamp > $currentTimestamp ? 'programme' : 'accessible',
        ];
    }

    public function resolvePublicationStatus(bool $complete, string $state, string $currentStatus): ?string
    {
        if ($complete && $state === 'accessible') {
            return $currentStatus === 'publish' ? null : 'publish';
        }

        return $currentStatus === 'publish' ? 'pending' : null;
    }
}
