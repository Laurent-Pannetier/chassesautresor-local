<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build the cached state and publication transition for a hint.
 */
class HintCacheService
{
    private HintStatusService $statusService;

    public function __construct(?HintStatusService $statusService = null)
    {
        $this->statusService = $statusService ?? new HintStatusService();
    }

    /**
     * @return array{complete:int, state:string, publication_status:?string}
     */
    public function buildUpdate(
        bool $hasContent,
        bool $hasImage,
        string $availability,
        ?int $availabilityTimestamp,
        int $currentTimestamp,
        string $currentPublicationStatus
    ): array {
        $status = $this->statusService->resolve(
            $hasContent,
            $hasImage,
            $availability,
            $availabilityTimestamp,
            $currentTimestamp
        );

        return [
            'complete' => $status['complete'] ? 1 : 0,
            'state' => $status['state'],
            'publication_status' => $this->statusService->resolvePublicationStatus(
                $status['complete'],
                $status['state'],
                $currentPublicationStatus
            ),
        ];
    }
}
