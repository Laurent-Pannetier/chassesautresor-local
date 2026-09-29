<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Evaluate whether a user can access a hunt or riddle solution.
 */
class SolutionAccessService
{
    public function canViewRiddleSolution(
        bool $isAdministrator,
        bool $hasAssociatedHunt,
        bool $isHuntFinished,
        bool $isEngagedInRiddle,
        bool $isAssociatedOrganizer,
        string $riddleStatus
    ): bool {
        if ($isAdministrator) {
            return true;
        }

        if (!$hasAssociatedHunt) {
            return false;
        }

        return $isAssociatedOrganizer
            || ($isHuntFinished && $isEngagedInRiddle)
            || in_array($riddleStatus, ['resolue', 'terminee'], true);
    }

    public function canViewHuntSolution(
        bool $isAuthenticated,
        bool $isAdministrator,
        bool $isAssociatedOrganizer,
        bool $isEngagedInHunt
    ): bool {
        return $isAuthenticated
            && ($isAdministrator || $isAssociatedOrganizer || $isEngagedInHunt);
    }
}
