<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist editable solution fields and refresh their derived state.
 */
class SolutionMutationService {
    /**
     * @param array{availability:string,delay_days:int,publication_time:string} $schedule
     */
    public static function apply(
        int $solutionId,
        int $fileId,
        bool $fileWasSubmitted,
        string $explanation,
        array $schedule,
        bool $replaceExplanation
    ): void {
        if ($fileId > 0) {
            update_field('solution_fichier', $fileId, $solutionId);
        } elseif ($fileWasSubmitted) {
            delete_field('solution_fichier', $solutionId);
        }

        if ($replaceExplanation || $explanation !== '') {
            update_field('solution_explication', $explanation, $solutionId);
        }

        update_field('solution_disponibilite', $schedule['availability'], $solutionId);
        update_field('solution_decalage_jours', $schedule['delay_days'], $solutionId);
        update_field('solution_heure_publication', $schedule['publication_time'], $solutionId);

        SolutionCacheUpdater::update($solutionId);
        SolutionPublicationPlanner::plan($solutionId);
    }
}
