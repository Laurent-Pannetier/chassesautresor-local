<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Select riddles that may be used as prerequisites for another riddle.
 */
class RiddlePrerequisiteService
{
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
}
