<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * WordPress/ACF filter adapter for riddle relationship fields.
 */
class RiddleRelationshipFilterHandler {
    public static function register(callable $addFilter): void {
        $addFilter(
            'acf/load_field/name=chasse_associee',
            [self::class, 'prefillHuntField'],
            10,
            1
        );
        $addFilter(
            'acf/fields/relationship/query',
            [self::class, 'limitPrerequisiteChoices'],
            10,
            3
        );
        $addFilter(
            'acf/load_field/name=enigme_acces_condition',
            [self::class, 'limitAccessConditionChoices'],
            10,
            1
        );
    }

    public static function prefillHuntField(array $field): array {
        global $post;

        if (!$post || get_post_type($post->ID) !== 'enigme') {
            return $field;
        }

        if (get_post_meta($post->ID, 'chasse_associee', true)) {
            return $field;
        }

        $huntId = self::huntIdForRiddle((int) $post->ID);
        if ($huntId > 0) {
            $field['value'] = $huntId;
        }

        return $field;
    }

    public static function limitPrerequisiteChoices(array $args, array $field, $postId): array {
        if (($field['name'] ?? '') !== 'pre_requis') {
            return $args;
        }

        $postId = (int) $postId;
        $huntId = self::huntIdForRiddle($postId);
        if ($huntId === 0) {
            return $args;
        }

        $cachedRiddles = get_field(RiddleCacheMutationService::FIELD_NAME, $huntId);
        $relationships = new RelationshipService();
        $riddleIds = array_values(array_filter(
            $relationships->normalizeIds(is_array($cachedRiddles) ? $cachedRiddles : []),
            static fn (int $riddleId): bool => get_post_type($riddleId) === 'enigme'
        ));
        $args['post__in'] = (new RiddleRelationshipService())->getSelectableRiddleIds(
            $riddleIds,
            $postId
        );

        return $args;
    }

    public static function limitAccessConditionChoices(array $field): array {
        global $post;

        if (!$post || get_post_type($post->ID) !== 'enigme') {
            return $field;
        }

        $huntId = self::huntIdForRiddle((int) $post->ID);
        if ($huntId === 0 || self::eligiblePrerequisiteIds((int) $post->ID, $huntId) === []) {
            unset($field['choices']['pre_requis']);
        }

        return $field;
    }

    /**
     * @return int[]
     */
    private static function eligiblePrerequisiteIds(int $riddleId, int $huntId): array {
        $cachedRiddles = get_field(RiddleCacheMutationService::FIELD_NAME, $huntId);
        $validationModes = [];
        $relationships = new RelationshipService();

        foreach ($relationships->normalizeIds(is_array($cachedRiddles) ? $cachedRiddles : []) as $candidateId) {
            if (get_post_type($candidateId) === 'enigme') {
                $validationModes[$candidateId] = (string) get_field('enigme_mode_validation', $candidateId);
            }
        }

        return (new RiddlePrerequisiteService())->getEligibleIds($riddleId, $validationModes);
    }

    private static function huntIdForRiddle(int $riddleId): int {
        return (new RiddleRelationshipService())->resolveHuntId(
            get_field('enigme_chasse_associee', $riddleId)
        );
    }
}
