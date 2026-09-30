<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Redirect direct solution views to their linked hunt or riddle.
 */
class SolutionRedirectHandler {
    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     */
    public static function resolveTargetId(
        string $targetType,
        $linkedHunt,
        $linkedRiddle
    ): ?int {
        return (new RelationshipService())->resolveTargetId(
            $targetType,
            $linkedHunt,
            $linkedRiddle
        );
    }

    public static function redirectIfViewingSolution(): void {
        if (!is_singular('solution')) {
            return;
        }

        $solutionId = get_the_ID();
        $targetId = self::resolveTargetId(
            (string) get_field('solution_cible_type', $solutionId),
            get_field('solution_chasse_linked', $solutionId),
            get_field('solution_enigme_linked', $solutionId)
        );

        if ($targetId === null) {
            return;
        }

        wp_safe_redirect(get_permalink($targetId));
        exit;
    }
}
