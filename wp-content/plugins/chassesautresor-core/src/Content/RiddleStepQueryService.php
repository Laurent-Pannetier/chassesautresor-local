<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Query intermediate steps in their deterministic display order. */
final class RiddleStepQueryService {
    /** @return array<string, mixed> */
    public function getOrderedIdsQueryArgs(int $riddleId): array {
        if ($riddleId <= 0) {
            return [];
        }

        return [
            'post_type' => RiddleStepPostTypeRegistrar::POST_TYPE,
            'post_status' => ['publish', 'pending', 'draft', 'private'],
            'fields' => 'ids',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'etape_enigme_associee',
                    'value' => $riddleId,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
            ],
            'orderby' => [
                'menu_order' => 'ASC',
                'ID' => 'ASC',
            ],
        ];
    }

    /** @return int[] */
    public function findOrderedIds(int $riddleId, ?callable $getPosts = null): array {
        $queryArgs = $this->getOrderedIdsQueryArgs($riddleId);
        if ($queryArgs === []) {
            return [];
        }

        $getPosts = $getPosts ?? 'get_posts';
        return array_values(array_filter(array_map('intval', (array) $getPosts($queryArgs))));
    }
}
