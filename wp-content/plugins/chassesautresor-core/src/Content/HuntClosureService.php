<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Complete a hunt and publish the solutions attached to it and its riddles.
 */
class HuntClosureService {
    /**
     * @param callable(string, mixed, int): mixed $updateField
     * @param callable(int): array<int, int|string> $findRiddles
     * @param callable(int): void $publishRiddleFile
     * @param callable(int, string): mixed $findSolution
     * @param callable(int): void $scheduleSolution
     * @param callable(int): void $completePlayers
     * @return array{handled:bool,error:?string}
     */
    public function apply(
        int $huntId,
        string $field,
        $value,
        callable $updateField,
        callable $findRiddles,
        callable $publishRiddleFile,
        callable $findSolution,
        callable $scheduleSolution,
        callable $completePlayers
    ): array {
        if ($field !== 'champs_caches.chasse_cache_statut' || $value !== 'termine') {
            return ['handled' => false, 'error' => null];
        }

        if ($updateField('chasse_cache_statut', 'termine', $huntId) === false) {
            return ['handled' => true, 'error' => 'echec_mise_a_jour'];
        }

        if ($updateField('chasse_cache_complet', 1, $huntId) === false) {
            return ['handled' => true, 'error' => 'echec_mise_a_jour'];
        }

        foreach ($findRiddles($huntId) as $riddleId) {
            $riddleId = (int) $riddleId;
            $publishRiddleFile($riddleId);
            $solution = $findSolution($riddleId, 'enigme');
            $solutionId = $this->solutionId($solution);
            if ($solutionId > 0) {
                $scheduleSolution($solutionId);
            }
        }

        $huntSolution = $findSolution($huntId, 'chasse');
        $huntSolutionId = $this->solutionId($huntSolution);
        if ($huntSolutionId > 0) {
            $scheduleSolution($huntSolutionId);
        }

        $completePlayers($huntId);

        return ['handled' => true, 'error' => null];
    }

    /**
     * @param mixed $solution
     */
    private function solutionId($solution): int {
        if (is_object($solution) && isset($solution->ID)) {
            return (int) $solution->ID;
        }

        if (is_array($solution) && isset($solution['ID'])) {
            return (int) $solution['ID'];
        }

        return 0;
    }
}
