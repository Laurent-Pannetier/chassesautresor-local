<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build the query used to rank hints attached to a hunt or riddle.
 */
class HintQueryService
{
    /**
     * Build the query used by the hint management table.
     *
     * A hunt view includes hints attached directly to the hunt and hints attached
     * to one of its riddles. A riddle view only includes its own hints.
     *
     * @param array<int, mixed> $riddleIds Riddles belonging to the hunt.
     * @return array<string, mixed>
     */
    public function getManagementTableQueryArgs(
        int $targetId,
        string $targetType,
        array $riddleIds = [],
        int $page = 1,
        int $perPage = 5,
        bool $idsOnly = false
    ): array {
        $metaQuery = $this->getManagementTableMetaQuery($targetId, $targetType, $riddleIds);
        if ($metaQuery === []) {
            return [];
        }

        $args = [
            'post_type' => 'indice',
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

    /**
     * @return array<string, mixed>
     */
    public function getRankedHintIdsQueryArgs(int $targetId, string $targetType, bool $ordered = false): array
    {
        if ($targetId <= 0 || !in_array($targetType, ['chasse', 'enigme'], true)) {
            return [];
        }

        $metaQuery = [];
        if ($targetType === 'enigme') {
            $metaQuery[] = [
                'key' => 'indice_cible_type',
                'value' => 'enigme',
                'compare' => '=',
            ];
        }

        $metaQuery[] = [
            'key' => $targetType === 'chasse' ? 'indice_chasse_linked' : 'indice_enigme_linked',
            'value' => $targetId,
            'compare' => '=',
        ];
        $metaQuery[] = [
            'key' => 'indice_cache_etat_systeme',
            'value' => ['programme', 'accessible', 'desactive'],
            'compare' => 'IN',
        ];

        $args = [
            'post_type' => 'indice',
            'post_status' => ['publish', 'pending', 'draft', 'private', 'future'],
            'meta_query' => $metaQuery,
            'fields' => 'ids',
            'no_found_rows' => true,
            'posts_per_page' => -1,
        ];

        if ($ordered) {
            $args['orderby'] = 'date';
            $args['order'] = 'ASC';
        }

        return $args;
    }

    /**
     * Build the query for programmed hints whose availability date has elapsed.
     *
     * @return array<string, mixed>
     */
    public function getDueProgrammedHintIdsQueryArgs(string $currentDate): array
    {
        if ($currentDate === '') {
            return [];
        }

        return [
            'post_type' => 'indice',
            'post_status' => ['publish', 'pending', 'draft', 'private', 'future'],
            'meta_query' => [
                [
                    'key' => 'indice_cache_etat_systeme',
                    'value' => 'programme',
                ],
                [
                    'key' => 'indice_date_disponibilite',
                    'value' => $currentDate,
                    'compare' => '<=',
                    'type' => 'DATETIME',
                ],
            ],
            'fields' => 'ids',
            'no_found_rows' => true,
            'posts_per_page' => -1,
        ];
    }

    /**
     * @param array<int, mixed> $riddleIds
     * @return array<int|string, mixed>
     */
    private function getManagementTableMetaQuery(int $targetId, string $targetType, array $riddleIds): array
    {
        if ($targetId <= 0 || !in_array($targetType, ['chasse', 'enigme'], true)) {
            return [];
        }

        if ($targetType === 'enigme') {
            return [
                [
                    'key' => 'indice_cible_type',
                    'value' => 'enigme',
                ],
                [
                    'key' => 'indice_enigme_linked',
                    'value' => $targetId,
                ],
            ];
        }

        $metaQuery = [
            'relation' => 'OR',
            [
                'relation' => 'AND',
                [
                    'key' => 'indice_cible_type',
                    'value' => 'chasse',
                ],
                [
                    'key' => 'indice_chasse_linked',
                    'value' => $targetId,
                ],
            ],
        ];
        $riddleIds = array_values(array_unique(array_filter(array_map('intval', $riddleIds))));

        if ($riddleIds !== []) {
            $metaQuery[] = [
                'relation' => 'AND',
                [
                    'key' => 'indice_cible_type',
                    'value' => 'enigme',
                ],
                [
                    'key' => 'indice_enigme_linked',
                    'value' => $riddleIds,
                    'compare' => 'IN',
                ],
            ];
        }

        return $metaQuery;
    }
}
