<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build canonical queries for solutions attached to hunts and riddles.
 */
class SolutionQueryService
{
    /** @return array<string, mixed> */
    public function getExistingSolutionIdsQueryArgs(int $targetId, string $targetType): array
    {
        $metaQuery = $this->getTargetMetaQuery($targetId, $targetType, []);
        if ($metaQuery === []) {
            return [];
        }

        return [
            'post_type' => 'solution',
            'post_status' => ['publish', 'pending', 'draft', 'private', 'future'],
            'meta_query' => $metaQuery,
            'fields' => 'ids',
            'no_found_rows' => true,
            'posts_per_page' => 1,
        ];
    }

    /**
     * @param int[] $riddleIds
     * @return array<string, mixed>
     */
    public function getManagementQueryArgs(
        int $targetId,
        string $targetType,
        array $riddleIds = [],
        int $page = 1,
        int $perPage = 5,
        bool $idsOnly = false
    ): array {
        $metaQuery = $this->getTargetMetaQuery($targetId, $targetType, $riddleIds);
        if ($metaQuery === []) {
            return [];
        }

        $args = [
            'post_type' => 'solution',
            'post_status' => ['publish', 'pending', 'draft'],
            'meta_query' => $metaQuery,
        ];
        if ($idsOnly) {
            $args['fields'] = 'ids';
            $args['nopaging'] = true;
            return $args;
        }

        $args['orderby'] = 'date';
        $args['order'] = 'DESC';
        $args['posts_per_page'] = max(1, $perPage);
        $args['paged'] = max(1, $page);
        return $args;
    }

    /** @param int[] $riddleIds */
    private function getTargetMetaQuery(int $targetId, string $targetType, array $riddleIds): array
    {
        if ($targetId <= 0 || !in_array($targetType, ['chasse', 'enigme'], true)) {
            return [];
        }

        if ($targetType === 'enigme') {
            return [
                ['key' => 'solution_cible_type', 'value' => 'enigme'],
                ['key' => 'solution_enigme_linked', 'value' => $targetId],
            ];
        }

        $query = [
            'relation' => 'OR',
            [
                'relation' => 'AND',
                ['key' => 'solution_cible_type', 'value' => 'chasse'],
                ['key' => 'solution_chasse_linked', 'value' => $targetId],
            ],
        ];
        $riddleIds = array_values(array_unique(array_filter(array_map('intval', $riddleIds))));
        if ($riddleIds !== []) {
            $query[] = [
                'relation' => 'AND',
                ['key' => 'solution_cible_type', 'value' => 'enigme'],
                ['key' => 'solution_enigme_linked', 'value' => $riddleIds, 'compare' => 'IN'],
            ];
        }
        return $query;
    }
}
