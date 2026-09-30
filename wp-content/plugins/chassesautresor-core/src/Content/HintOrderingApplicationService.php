<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Apply canonical titles and ranks to all hints attached to a target.
 */
class HintOrderingApplicationService {
    private static bool $processing = false;

    public function applyTarget(int $targetId, string $targetType): void {
        $queryArgs = (new HintQueryService())->getRankedHintIdsQueryArgs($targetId, $targetType, true);
        if ($queryArgs === [] || self::$processing) {
            return;
        }

        self::$processing = true;
        try {
            (new HintOrderingUpdater(
                new HintOrderingService(new HintTitleService()),
                new RelationshipService()
            ))->apply(
                get_posts($queryArgs),
                $targetType,
                $targetId,
                defined('TITRE_DEFAUT_INDICE') ? TITRE_DEFAUT_INDICE : '',
                defined('INDICE_DEFAULT_PREFIX') ? INDICE_DEFAULT_PREFIX : '',
                static fn (int $hintId): string => (string) get_post_field('post_title', $hintId),
                static fn (int $hintId) => get_field('indice_chasse_linked', $hintId),
                static fn (int $huntId): string => self::buildPlaceholder($huntId),
                static fn (array $postData) => wp_update_post($postData),
                static fn (int $hintId, string $key, int $rank) => update_post_meta($hintId, $key, $rank)
            );
        } finally {
            self::$processing = false;
        }
    }

    private static function buildPlaceholder(int $huntId): string {
        $slug = (string) get_post_field('post_name', $huntId);
        $generatedSlug = '';
        if ($slug === '') {
            $huntTitle = (string) get_post_field('post_title', $huntId);
            $generatedSlug = $huntTitle !== '' ? sanitize_title($huntTitle) : '';
        }

        return (new HintTitleService())->buildPlaceholder(
            defined('INDICE_DEFAULT_PREFIX') ? INDICE_DEFAULT_PREFIX : 'clue-',
            $slug,
            $generatedSlug
        );
    }
}
