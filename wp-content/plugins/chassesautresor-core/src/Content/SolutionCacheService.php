<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build cached completion, system state and publication transitions for solutions.
 */
class SolutionCacheService
{
    /**
     * @return array{complete:int,state:string,publication_status:?string}
     */
    public function buildUpdate(bool $hasContent, ?int $targetId, string $currentPublicationStatus): array
    {
        if ($targetId === null || $targetId <= 0) {
            return $this->result(false, 'INVALIDE', $currentPublicationStatus);
        }

        if (!$hasContent) {
            return $this->result(false, 'DESACTIVE', $currentPublicationStatus);
        }

        return $this->result(true, 'EN_COURS', $currentPublicationStatus);
    }

    /**
     * @return array{complete:int,state:string,publication_status:?string}
     */
    private function result(bool $complete, string $state, string $currentPublicationStatus): array
    {
        $publicationStatus = null;
        if ($complete && $currentPublicationStatus !== 'publish') {
            $publicationStatus = 'publish';
        } elseif (!$complete && $currentPublicationStatus === 'publish') {
            $publicationStatus = 'pending';
        }

        return [
            'complete' => $complete ? 1 : 0,
            'state' => $state,
            'publication_status' => $publicationStatus,
        ];
    }
}
