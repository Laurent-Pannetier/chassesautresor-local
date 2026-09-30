<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Apply solution management rules independently from WordPress responses.
 */
class SolutionManagementService {
    public function normalizePage(int $requestedPage, int $totalPages): int {
        $page = max(1, $requestedPage);

        return $totalPages > 0 ? min($page, $totalPages) : $page;
    }

    /**
     * @return array{
     *     has_solution_chasse:int,
     *     has_solution_enigme:int,
     *     has_enigmes:int,
     *     has_solutions:int,
     *     total_solutions:int
     * }
     */
    public function buildHuntStatus(
        bool $hasHuntSolution,
        bool $hasSelectedRiddleSolution,
        int $riddleCount,
        int $riddlesWithoutSolutionCount,
        int $totalSolutions
    ): array {
        $hasRiddles = $riddlesWithoutSolutionCount > 0;
        $hasRiddleSolution = $riddleCount > $riddlesWithoutSolutionCount;

        return [
            'has_solution_chasse' => $hasHuntSolution ? 1 : 0,
            'has_solution_enigme' => $hasSelectedRiddleSolution ? 1 : 0,
            'has_enigmes' => $hasRiddles ? 1 : 0,
            'has_solutions' => ($hasHuntSolution || $hasRiddleSolution) ? 1 : 0,
            'total_solutions' => max(0, $totalSolutions),
        ];
    }
}
