<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Load the hints displayed in a player's riddle participation panel. */
final class RiddleParticipationService {
    private ?HintUnlockService $hintUnlockService;

    public function __construct(?HintUnlockService $hintUnlockService = null) {
        $this->hintUnlockService = $hintUnlockService;
    }

    /** @return array{riddle:int[],hunt:int[]} */
    public function hintIds(int $riddleId): array {
        $huntId = recuperer_id_chasse_associee($riddleId);

        return [
            'riddle' => $this->query('enigme', 'indice_enigme_linked', $riddleId),
            'hunt' => $huntId > 0 ? $this->query('chasse', 'indice_chasse_linked', $huntId) : [],
        ];
    }

    /** @return array{riddle:array<int,array<string,mixed>>,hunt:array<int,array<string,mixed>>} */
    public function hints(int $riddleId, int $userId): array {
        $groups = $this->hintIds($riddleId);
        $hintIds = array_values(array_unique(array_merge($groups['riddle'], $groups['hunt'])));

        if ($hintIds !== []) {
            _prime_post_caches($hintIds, false, false);
            update_meta_cache('post', $hintIds);
        }

        $unlockedIds = array_fill_keys($this->hintUnlockService()->unlockedHintIds($userId, $hintIds), true);

        return [
            'riddle' => array_map(fn (int $hintId): array => $this->hint($hintId, $unlockedIds), $groups['riddle']),
            'hunt' => array_map(fn (int $hintId): array => $this->hint($hintId, $unlockedIds), $groups['hunt']),
        ];
    }

    /** @return array{id:int,cost:int,state:string,unlocked:bool,title:string,available_at:int|false} */
    private function hint(int $hintId, array $unlockedIds): array {
        return [
            'id' => $hintId,
            'cost' => (int) get_field('indice_cout_points', $hintId),
            'state' => (string) (get_field('indice_cache_etat_systeme', $hintId) ?: ''),
            'unlocked' => isset($unlockedIds[$hintId]),
            'title' => get_indice_title($hintId),
            'available_at' => $this->timestamp(get_field('indice_date_disponibilite', $hintId)),
        ];
    }

    private function hintUnlockService(): HintUnlockService {
        if ($this->hintUnlockService === null) {
            global $wpdb;
            $this->hintUnlockService = \ChassesAuTresor\Core\Support\CoreServiceFactory::hintUnlock($wpdb);
        }

        return $this->hintUnlockService;
    }

    /** @return int|false */
    private function timestamp($value) {
        if (!$value) {
            return false;
        }

        $formats = ['Y-m-d H:i:s', 'd/m/Y H:i', 'Y-m-d\TH:i:s', 'd/m/Y g:i a', 'd/m/Y g:i A', 'Y-m-d g:i a'];
        foreach ($formats as $format) {
            $date = date_create_from_format($format, (string) $value, wp_timezone());
            if ($date !== false) {
                return $date->getTimestamp();
            }
        }

        return false;
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
