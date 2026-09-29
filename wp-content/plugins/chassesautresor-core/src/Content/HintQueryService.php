<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build the query used to rank hints attached to a hunt or riddle.
 */
class HintQueryService
{
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
}
