<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Load the hints displayed in a player's riddle participation panel. */
final class RiddleParticipationService {
    /** @return array{riddle:int[],hunt:int[]} */
    public function hintIds(int $riddleId): array {
        $huntId = recuperer_id_chasse_associee($riddleId);

        return [
            'riddle' => $this->query('enigme', 'indice_enigme_linked', $riddleId),
            'hunt' => $huntId > 0 ? $this->query('chasse', 'indice_chasse_linked', $huntId) : [],
        ];
    }

    /** @return int[] */
    private function query(string $targetType, string $relationKey, int $targetId): array {
        if ($targetId <= 0) {
            return [];
        }

        $ids = get_posts([
            'post_type' => 'indice',
            'post_status' => ['publish', 'draft', 'future', 'pending'],
            'meta_query' => [
                [
                    'key' => 'indice_cible_type',
                    'value' => $targetType,
                    'compare' => '=',
                ],
                [
                    'key' => $relationKey,
                    'value' => $targetId,
                    'compare' => '=',
                ],
                [
                    'key' => 'indice_cache_etat_systeme',
                    'value' => ['accessible', 'programme'],
                    'compare' => 'IN',
                ],
            ],
            'orderby' => 'date',
            'order' => 'ASC',
            'fields' => 'ids',
            'no_found_rows' => true,
            'posts_per_page' => -1,
        ]);

        return array_values(array_filter(array_map('intval', (array) $ids)));
    }
}
