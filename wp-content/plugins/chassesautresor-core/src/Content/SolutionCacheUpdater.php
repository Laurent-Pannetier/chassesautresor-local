<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Synchronize the cached state and publication status of a solution.
 */
class SolutionCacheUpdater {
    public static function update(int $solutionId): void {
        if (get_post_type($solutionId) !== 'solution') {
            return;
        }

        if (wp_is_post_revision($solutionId) || wp_is_post_autosave($solutionId)) {
            return;
        }

        $targetType = (string) get_field('solution_cible_type', $solutionId);
        $targetId = (new RelationshipService())->resolveTargetId(
            $targetType,
            get_field('solution_chasse_linked', $solutionId),
            get_field('solution_enigme_linked', $solutionId)
        );
        $hasContent = trim((string) get_field('solution_explication', $solutionId)) !== ''
            || !empty(get_field('solution_fichier', $solutionId));
        $cacheUpdate = (new SolutionCacheService())->buildUpdate(
            $hasContent,
            $targetId,
            (string) get_post_status($solutionId)
        );

        update_field('solution_cache_complet', $cacheUpdate['complete'], $solutionId);
        update_field('solution_cache_etat_systeme', $cacheUpdate['state'], $solutionId);

        if ($cacheUpdate['publication_status'] === null) {
            return;
        }

        $post = get_post($solutionId);
        if (!$post) {
            return;
        }

        wp_update_post([
            'ID' => $solutionId,
            'post_status' => $cacheUpdate['publication_status'],
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'edit_date' => true,
        ]);
    }
}
