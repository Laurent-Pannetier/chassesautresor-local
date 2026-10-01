<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Relationships\HuntRiddleQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Read, calculate and persist riddle system states.
 */
class RiddleSystemStateUpdater {
    public function refresh(int $riddleId, bool $persist = true, ?string $huntStatus = null): string {
        if (get_post_type($riddleId) !== 'enigme') {
            return 'cache_invalide';
        }
        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $hasValidHunt = $huntId !== null && get_post_type($huntId) === 'chasse';
        $condition = (string) (get_field('enigme_acces_condition', $riddleId) ?? 'immediat');
        $state = (new RiddleSystemStateService())->calculate(
            $hasValidHunt,
            $hasValidHunt ? (string) ($huntStatus ?? get_field('chasse_cache_statut', $huntId)) : '',
            $condition,
            $condition === 'date_programmee'
                ? $this->timestamp(get_field('enigme_acces_date', $riddleId))
                : null,
            (string) get_field('enigme_mode_validation', $riddleId),
            (new RiddleAnswerService())->get($riddleId) !== [],
            (int) current_time('timestamp')
        );
        if ($persist && get_field('enigme_cache_etat_systeme', $riddleId) !== $state) {
            update_field('enigme_cache_etat_systeme', $state, $riddleId);
        }

        return $state;
    }

    public function refreshHunt(int $huntId, ?string $huntStatus = null): int {
        if (get_post_type($huntId) !== 'chasse') {
            return 0;
        }
        $riddleIds = get_posts((new HuntRiddleQueryService())->getRiddleIdsQueryArgs($huntId));
        foreach ($riddleIds as $riddleId) {
            $this->refresh((int) $riddleId, true, $huntStatus);
        }
        return count($riddleIds);
    }

    private function timestamp($value): ?int {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        if (!is_scalar($value) || trim((string) $value) === '') {
            return null;
        }
        $timestamp = strtotime(str_replace('/', '-', (string) $value));
        return $timestamp === false ? null : $timestamp;
    }
}
