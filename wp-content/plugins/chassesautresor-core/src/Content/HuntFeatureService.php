<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Aggregate solution and hint availability for a hunt and its riddles.
 */
class HuntFeatureService
{
    /**
     * @param int[] $riddleIds
     * @param callable(int):bool $riddleHasSolution
     * @param callable(int):bool $riddleHasHints
     * @return array{has_solutions:bool,has_indices:bool}
     */
    public function summarize(
        bool $huntHasSolution,
        bool $huntHasHints,
        array $riddleIds,
        callable $riddleHasSolution,
        callable $riddleHasHints
    ): array {
        $hasSolutions = $huntHasSolution;
        $hasHints = $huntHasHints;

        foreach ($riddleIds as $riddleId) {
            $riddleId = (int) $riddleId;
            if ($riddleId <= 0) {
                continue;
            }

            if (!$hasSolutions && $riddleHasSolution($riddleId)) {
                $hasSolutions = true;
            }

            if (!$hasHints && $riddleHasHints($riddleId)) {
                $hasHints = true;
            }

            if ($hasSolutions && $hasHints) {
                break;
            }
        }

        return [
            'has_solutions' => $hasSolutions,
            'has_indices' => $hasHints,
        ];
    }
}
