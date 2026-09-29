<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Select riddles that may be used as prerequisites for another riddle.
 */
class RiddlePrerequisiteService
{
    public const ERROR_UNAUTHENTICATED = 'unauthenticated';
    public const ERROR_INVALID_RIDDLE = 'invalid_riddle';
    public const ERROR_FORBIDDEN = 'forbidden';
    public const ERROR_MISSING_PREREQUISITES = 'missing_prerequisites';

    /**
     * @param array<int, string> $validationModes Validation mode indexed by riddle ID.
     * @return int[]
     */
    public function getEligibleIds(int $currentRiddleId, array $validationModes): array
    {
        $eligibleIds = [];

        foreach ($validationModes as $riddleId => $validationMode) {
            $riddleId = (int) $riddleId;

            if ($riddleId === $currentRiddleId) {
                continue;
            }

            if (in_array($validationMode, ['manuelle', 'automatique'], true)) {
                $eligibleIds[] = $riddleId;
            }
        }

        return $eligibleIds;
    }

    public function getConditionUpdateError(
        bool $isAuthenticated,
        bool $isRiddle,
        bool $isAuthor,
        array $prerequisiteIds
    ): ?string {
        if (!$isAuthenticated) {
            return self::ERROR_UNAUTHENTICATED;
        }

        if (!$isRiddle) {
            return self::ERROR_INVALID_RIDDLE;
        }

        if (!$isAuthor) {
            return self::ERROR_FORBIDDEN;
        }

        if (array_filter($prerequisiteIds) === []) {
            return self::ERROR_MISSING_PREREQUISITES;
        }

        return null;
    }
}
