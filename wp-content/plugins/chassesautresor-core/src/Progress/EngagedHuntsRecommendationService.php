<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Select public hunts for the engaged-hunts empty state. */
final class EngagedHuntsRecommendationService {
    /** @return int[] */
    public function find(int $limit = 3): array {
        $limit = max(0, $limit);
        if ($limit === 0) {
            return [];
        }

        $baseMetaQuery = [
            'relation' => 'AND',
            [
                'key' => 'chasse_cache_statut',
                'value' => ['a_venir', 'en_cours', 'payante'],
                'compare' => 'IN',
            ],
            [
                'key' => 'chasse_cache_statut_validation',
                'value' => 'valide',
            ],
        ];
        $ids = $this->query('ca_recommended_hunts_recent_query_args', [
            'posts_per_page' => min(2, $limit),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $baseMetaQuery,
        ]);

        if (count($ids) < $limit) {
            $activeMetaQuery = $baseMetaQuery;
            $activeMetaQuery[0]['value'] = ['en_cours', 'payante'];
            $ids = $this->append($ids, $this->query('ca_recommended_hunts_popular_query_args', [
                'posts_per_page' => 1,
                'meta_key' => 'ca_total_engagements',
                'orderby' => 'meta_value_num',
                'order' => 'DESC',
                'meta_query' => $activeMetaQuery,
                'post__not_in' => $ids,
            ]));
        }

        if (count($ids) < $limit) {
            $ids = $this->append($ids, $this->query('ca_recommended_hunts_fallback_query_args', [
                'posts_per_page' => $limit - count($ids),
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => $baseMetaQuery,
                'post__not_in' => $ids,
            ]));
        }

        if (count($ids) < $limit) {
            $ids = $this->append($ids, $this->query('ca_recommended_hunts_completed_query_args', [
                'posts_per_page' => $limit - count($ids),
                'orderby' => 'date',
                'order' => 'DESC',
                'meta_query' => [
                    ['key' => 'chasse_cache_statut', 'value' => 'termine'],
                    ['key' => 'chasse_cache_statut_validation', 'value' => 'valide'],
                ],
                'post__not_in' => $ids,
            ]));
        }

        $ids = apply_filters('ca_recommended_hunts_empty_state_ids', array_slice($ids, 0, $limit));

        return array_slice($this->append([], (array) $ids), 0, $limit);
    }

    /** @param array<string, mixed> $specificArgs
     *  @return int[]
     */
    private function query(string $filter, array $specificArgs): array {
        $args = array_merge([
            'post_type' => 'chasse',
            'post_status' => 'publish',
            'no_found_rows' => true,
            'fields' => 'ids',
            'suppress_filters' => false,
        ], $specificArgs);

        return $this->append([], (array) get_posts(apply_filters($filter, $args)));
    }

    /** @param int[] $current
     *  @param mixed[] $additional
     *  @return int[]
     */
    private function append(array $current, array $additional): array {
        $additional = array_filter(array_map('intval', $additional));

        return array_values(array_unique(array_merge($current, $additional)));
    }
}
