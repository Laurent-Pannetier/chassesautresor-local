<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Define the supported targets and initial state of a newly created hint.
 */
class HintCreationService
{
    public function isSupportedTargetType(string $targetType): bool
    {
        return in_array($targetType, ['chasse', 'enigme'], true);
    }

    public function hasConsistentRiddleTarget(string $targetType, int $targetId, int $linkedRiddleId): bool
    {
        if ($targetType !== 'enigme') {
            return true;
        }

        return $targetId > 0 && $linkedRiddleId === $targetId;
    }

    public function getCreationError(
        bool $supportedTargetType,
        bool $targetMatchesType,
        bool $isAuthenticated,
        bool $canModifyTarget,
        bool $hasHunt,
        bool $canModifyHunt
    ): ?string {
        if (!$supportedTargetType) {
            return 'type_invalide';
        }

        if (!$targetMatchesType) {
            return 'cible_invalide';
        }

        if (!$isAuthenticated) {
            return 'non_connecte';
        }

        if (!$canModifyTarget || !$hasHunt || !$canModifyHunt) {
            return 'permission_refusee';
        }

        return null;
    }

    /**
     * @return array{
     *     post_status:string,
     *     availability:string,
     *     availability_timestamp:int,
     *     points_cost:int,
     *     complete:bool,
     *     system_state:string
     * }
     */
    public function getInitialState(int $currentTimestamp, int $availabilityDelay): array
    {
        return [
            'post_status' => 'pending',
            'availability' => 'immediate',
            'availability_timestamp' => $currentTimestamp + max(0, $availabilityDelay),
            'points_cost' => 0,
            'complete' => false,
            'system_state' => 'desactive',
        ];
    }
}
