<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Redirect direct hint views to their linked hunt or riddle.
 */
class HintRedirectHandler {
    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     */
    public static function resolveTargetId(
        string $targetType,
        $linkedHunt,
        $linkedRiddle
    ): ?int {
        return (new RelationshipService())->resolveHintTargetId(
            $targetType,
            $linkedHunt,
            $linkedRiddle
        );
    }

    public static function redirectIfViewingHint(): void {
        if (!is_singular('indice')) {
            return;
        }

        $hintId = get_the_ID();
        $targetId = self::resolveTargetId(
            (string) get_field('indice_cible_type', $hintId),
            get_field('indice_chasse_linked', $hintId),
            get_field('indice_enigme_linked', $hintId)
        );

        if ($targetId === null) {
            return;
        }

        wp_safe_redirect(get_permalink($targetId));
        exit;
    }
}
