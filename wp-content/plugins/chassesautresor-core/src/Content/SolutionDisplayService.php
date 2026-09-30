<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Determine whether the active solution for a hunt or riddle can be displayed.
 */
class SolutionDisplayService {
    public function canDisplay(int $targetId, string $targetType, int $huntId): bool {
        if (get_post_type($targetId) !== $targetType || get_post_type($huntId) !== 'chasse') {
            return false;
        }

        $queryArgs = (new SolutionQueryService())->getActiveSolutionQueryArgs(
            $targetId,
            $targetType
        );
        if ($queryArgs === []) {
            return false;
        }

        $solutions = get_posts($queryArgs);
        $solution = $solutions[0] ?? null;
        if (!$solution) {
            return false;
        }

        $availability = get_field('solution_disponibilite', $solution->ID) ?: 'fin_chasse';
        $publicationTime = get_field('solution_heure_publication', $solution->ID) ?: '00:00';
        $baseDate = get_field('date_de_decouverte', $huntId)
            ?: get_field('chasse_infos_date_fin', $huntId);

        return (new SolutionAvailabilityService())->isAvailable(
            (string) get_field('chasse_cache_statut', $huntId),
            (string) $availability,
            $baseDate ? strtotime((string) $baseDate) : null,
            (int) get_field('solution_decalage_jours', $solution->ID),
            (string) $publicationTime,
            (int) current_time('timestamp')
        );
    }
}
