<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Coordinate hint ordering after WordPress lifecycle changes.
 */
class HintOrderingLifecycleHookHandler {
    public static function register(callable $addAction): void {
        $addAction('save_post_indice', [self::class, 'handleSaved'], 20, 1);
        $addAction('trashed_post', [self::class, 'requestForHint'], 10, 1);
    }

    public static function handleSaved(int $hintId): void {
        if (wp_is_post_revision($hintId) || wp_is_post_autosave($hintId)) {
            return;
        }

        self::requestForHint($hintId);
    }

    public static function requestForHint(int $hintId): void {
        $updater = new HintOrderingUpdater(
            new HintOrderingService(new HintTitleService()),
            new RelationshipService()
        );
        $targets = $updater->resolveAffectedTargets(
            (string) get_field('indice_cible_type', $hintId),
            get_field('indice_chasse_linked', $hintId),
            get_field('indice_enigme_linked', $hintId),
            static fn (int $riddleId): ?int => (new RelationshipService())->normalizeId(
                get_field('enigme_chasse_associee', $riddleId)
            )
        );
        foreach ($targets as $target) {
            (new HintOrderingApplicationService())->applyTarget($target['id'], $target['type']);
        }
    }
}
