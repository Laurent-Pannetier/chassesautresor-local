<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Publish a solution while preserving its original publication dates.
 */
class SolutionPublicationService {
    public const AVAILABLE_STATE = 'EN_COURS';

    public static function makeAccessible(int $solutionId): void {
        if (get_post_type($solutionId) !== 'solution') {
            return;
        }

        update_field('solution_cache_etat_systeme', self::AVAILABLE_STATE, $solutionId);
        delete_post_meta($solutionId, 'solution_date_disponibilite');

        if (get_post_status($solutionId) === 'publish') {
            return;
        }

        $post = get_post($solutionId);
        if (!$post) {
            return;
        }

        wp_update_post([
            'ID' => $solutionId,
            'post_status' => 'publish',
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'edit_date' => true,
        ]);
    }
}
