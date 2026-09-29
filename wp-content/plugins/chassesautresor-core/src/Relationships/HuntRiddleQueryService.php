<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Build the WordPress query rules for riddles related to a hunt.
 */
class HuntRiddleQueryService
{
    /**
     * @return array<string, mixed>
     */
    public function getVisibleRiddlesQueryArgs(int $huntId): array
    {
        if ($huntId <= 0) {
            return [];
        }

        return [
            'post_type' => 'enigme',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending'],
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'enigme_chasse_associee',
                    'value' => $huntId,
                    'compare' => '=',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'enigme_cache_statut_validation',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key' => 'enigme_cache_statut_validation',
                        'value' => 'banni',
                        'compare' => '!=',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getRiddleIdsQueryArgs(int $huntId): array
    {
        if ($huntId <= 0) {
            return [];
        }

        return [
            'post_type' => 'enigme',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending', 'draft'],
            'meta_query' => [
                [
                    'key' => 'enigme_chasse_associee',
                    'value' => $huntId,
                    'compare' => '=',
                ],
            ],
        ];
    }

    /**
     * Build the canonical query used to synchronize the hunt's riddle cache.
     *
     * @return array<string, mixed>
     */
    public function getSynchronizedRiddleIdsQueryArgs(int $huntId): array
    {
        if ($huntId <= 0) {
            return [];
        }

        return [
            'post_type' => 'enigme',
            'post_status' => ['draft', 'pending', 'publish'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'enigme_chasse_associee',
                    'value' => $huntId,
                    'compare' => 'LIKE',
                ],
            ],
        ];
    }
}
