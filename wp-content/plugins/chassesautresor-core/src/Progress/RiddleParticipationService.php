<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleRelationshipService;
use ChassesAuTresor\Core\Points\PointsService;

/** Load the hints displayed in a player's riddle participation panel. */
final class RiddleParticipationService {
    private ?HintUnlockService $hintUnlockService;
    private ?PointsService $pointsService;
    private ?RiddleAttemptService $attemptService;

    public function __construct(
        ?HintUnlockService $hintUnlockService = null,
        ?PointsService $pointsService = null,
        ?RiddleAttemptService $attemptService = null
    ) {
        $this->hintUnlockService = $hintUnlockService;
        $this->pointsService = $pointsService;
        $this->attemptService = $attemptService;
    }

    /** @return array<string,int|string|bool> */
    public function participationInfo(int $riddleId, int $userId, bool $solved): array {
        $mode = $this->validationMode(get_field('enigme_mode_validation', $riddleId));
        $cost = $mode === 'aucune' ? 0 : (int) get_field('enigme_tentative_cout_points', $riddleId);
        $showAttempts = $mode === 'automatique' && !$solved;
        $showInfo = $mode !== 'aucune' && !$solved && ($cost > 0 || $showAttempts);

        return [
            'validation_mode' => $mode,
            'cost' => $cost,
            'balance' => $cost > 0 ? $this->pointsService()->getBalance($userId) : 0,
            'show_attempts' => $showAttempts,
            'show_info' => $showInfo,
            'attempts_used' => $showAttempts
                ? $this->attemptService()->countTodayForUser($userId, $riddleId)
                : 0,
            'attempts_max' => $showAttempts ? (int) get_field('enigme_tentative_max', $riddleId) : 0,
        ];
    }

    /** @return array{riddle:int[],hunt:int[]} */
    public function hintIds(int $riddleId): array {
        $huntId = (new RiddleRelationshipService())->resolveHuntId(
            get_field('enigme_chasse_associee', $riddleId)
        );

        return [
            'riddle' => $this->query('enigme', 'indice_enigme_linked', $riddleId),
            'hunt' => $huntId > 0 ? $this->query('chasse', 'indice_chasse_linked', $huntId) : [],
        ];
    }

    /** @return array{riddle:array<int,array<string,mixed>>,hunt:array<int,array<string,mixed>>} */
    public function hints(int $riddleId, int $userId): array {
        $groups = $this->hintIds($riddleId);
        $hintIds = array_values(array_unique(array_merge($groups['riddle'], $groups['hunt'])));

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
            'title' => $this->title($hintId),
            'available_at' => $this->timestamp(get_field('indice_date_disponibilite', $hintId)),
        ];
    }

    private function title(int $hintId): string {
        $post = get_post($hintId);
        if (!$post) {
            return '';
        }

        $title = (string) $post->post_title;
        $default = defined('TITRE_DEFAUT_INDICE') ? TITRE_DEFAUT_INDICE : 'Nouvel indice';
        $prefix = defined('INDICE_DEFAULT_PREFIX') ? INDICE_DEFAULT_PREFIX : 'clue-';
        $isGenerated = $title === ''
            || $title === $default
            || ($prefix !== '' && strpos($title, $prefix) === 0);
        if (!$isGenerated) {
            return $title;
        }

        return sprintf(
            __('Indice #%d', 'chassesautresor-com'),
            (int) get_post_meta($hintId, 'indice_rank', true)
        );
    }

    private function hintUnlockService(): HintUnlockService {
        if ($this->hintUnlockService === null) {
            global $wpdb;
            $this->hintUnlockService = \ChassesAuTresor\Core\Support\CoreServiceFactory::hintUnlock($wpdb);
        }

        return $this->hintUnlockService;
    }

    private function pointsService(): PointsService {
        if ($this->pointsService === null) {
            global $wpdb;
            $this->pointsService = \ChassesAuTresor\Core\Support\CoreServiceFactory::points($wpdb);
        }

        return $this->pointsService;
    }

    private function attemptService(): RiddleAttemptService {
        if ($this->attemptService === null) {
            global $wpdb;
            $this->attemptService = \ChassesAuTresor\Core\Support\CoreServiceFactory::riddleAttempts($wpdb);
        }

        return $this->attemptService;
    }

    private function validationMode($value): string {
        if (is_array($value)) {
            $value = $value['value'] ?? '';
        }

        $mode = strtolower(trim((string) $value));

        return $mode === '' || strpos($mode, 'aucune') === 0 || $mode === 'none' ? 'aucune' : $mode;
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

        $posts = get_posts([
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
            'no_found_rows' => true,
            'posts_per_page' => -1,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ]);

        return array_values(array_filter(array_map(
            static fn ($post): int => is_object($post) ? (int) $post->ID : (int) $post,
            (array) $posts
        )));
    }
}
