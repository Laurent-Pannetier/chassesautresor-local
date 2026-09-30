<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Create and initialize a solution post after its request has been validated.
 */
class SolutionPostFactory {
    /**
     * @return int|\WP_Error
     */
    public function create(
        int $targetId,
        string $targetType,
        int $huntId,
        int $authorId,
        string $initialTitle,
        string $generatedTitleFormat,
        string $disabledState
    ) {
        $creationService = new SolutionCreationService();
        $initialState = $creationService->getInitialState($disabledState);
        $solutionId = wp_insert_post([
            'post_type' => 'solution',
            'post_status' => $initialState['post_status'],
            'post_title' => $initialTitle,
            'post_author' => $authorId,
        ]);

        if (is_wp_error($solutionId)) {
            return $solutionId;
        }

        wp_update_post([
            'ID' => $solutionId,
            'post_title' => $creationService->getGeneratedTitle(
                $generatedTitleFormat,
                (string) get_the_title($targetId)
            ),
        ]);
        update_field('solution_cible_type', $targetType, $solutionId);
        update_field('solution_chasse_linked', $huntId, $solutionId);
        if ($targetType === 'enigme') {
            update_field('solution_enigme_linked', $targetId, $solutionId);
        }
        update_field('solution_disponibilite', $initialState['availability'], $solutionId);
        update_field('solution_decalage_jours', $initialState['delay_days'], $solutionId);
        update_field('solution_heure_publication', $initialState['publication_time'], $solutionId);
        update_field('solution_cache_etat_systeme', $initialState['system_state'], $solutionId);

        return $solutionId;
    }
}
