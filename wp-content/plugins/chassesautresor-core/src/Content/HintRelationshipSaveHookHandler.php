<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Complete the required hunt relationship when ACF saves a hint.
 */
class HintRelationshipSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handle'], 20, 1);
    }

    /** @param int|string $postId */
    public static function handle($postId): void {
        if (!is_numeric($postId) || get_post_type((int) $postId) !== 'indice') {
            return;
        }
        $hintId = (int) $postId;
        if (wp_is_post_revision($hintId) || wp_is_post_autosave($hintId)) {
            return;
        }

        $service = new HintRelationshipService(new RelationshipService());
        if ($service->normalizeHuntId(get_field('indice_chasse_linked', $hintId)) !== null) {
            return;
        }

        $huntId = $service->resolveLinkedHuntId(
            (string) get_field('indice_cible_type', $hintId),
            get_field('indice_enigme_linked', $hintId),
            isset($_GET['chasse_id']) ? (int) $_GET['chasse_id'] : null,
            static fn (int $riddleId) => get_field('enigme_chasse_associee', $riddleId)
        );
        $service->persistLinkedHunt($hintId, $huntId, 'update_field');
    }
}
