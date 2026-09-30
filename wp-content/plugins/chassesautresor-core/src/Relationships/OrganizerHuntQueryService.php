<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Build query rules for hunts belonging to an organizer.
 */
class OrganizerHuntQueryService
{
    /**
     * @return array<string, mixed>
     */
    public function getExistingHuntQueryArgs(int $organizerId, bool $pendingOnly = false): array
    {
        if ($organizerId <= 0) {
            return [];
        }

        return [
            'post_type' => 'chasse',
            'posts_per_page' => 1,
            'post_status' => $pendingOnly ? 'pending' : ['publish', 'pending'],
            'meta_query' => [
                'relation' => 'AND',
                $this->getOrganizerMetaQuery($organizerId),
                [
                    'key' => 'chasse_cache_statut_validation',
                    'value' => 'banni',
                    'compare' => '!=',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHuntIdsQueryArgs(int $organizerId): array
    {
        if ($organizerId <= 0) {
            return [];
        }

        return [
            'post_type' => 'chasse',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'pending'],
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [$this->getOrganizerMetaQuery($organizerId)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPublishedHuntCountQueryArgs(int $organizerId): array
    {
        if ($organizerId <= 0) {
            return [];
        }

        return [
            'post_type' => 'chasse',
            'posts_per_page' => 1,
            'post_status' => 'publish',
            'fields' => 'ids',
            'no_found_rows' => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'meta_query' => [$this->getOrganizerMetaQuery($organizerId)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getNavigationHuntsQueryArgs(int $organizerId): array
    {
        if ($organizerId <= 0) {
            return [];
        }

        return [
            'post_type' => 'chasse',
            'post_status' => ['publish', 'pending'],
            'numberposts' => -1,
            'meta_query' => [
                'relation' => 'AND',
                $this->getOrganizerMetaQuery($organizerId),
                [
                    'key' => 'chasse_cache_statut_validation',
                    'value' => 'banni',
                    'compare' => '!=',
                ],
            ],
        ];
    }

    /**
     * @return array{key:string,value:string,compare:string}
     */
    private function getOrganizerMetaQuery(int $organizerId): array
    {
        return [
            'key' => 'chasse_cache_organisateur',
            'value' => '"' . $organizerId . '"',
            'compare' => 'LIKE',
        ];
    }
}
