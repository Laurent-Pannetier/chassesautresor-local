<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\HintAccessPolicy;
use DateTimeZone;

/**
 * Load a player's hint gates: consulted prerequisites and the relative delay.
 */
final class HintPlayerGateService {
    private HintAccessPolicy $policy;
    private HintUnlockRepository $unlocks;
    private RiddleEngagementRepository $riddleEngagements;
    private HuntEngagementRepository $huntEngagements;

    public function __construct(
        HintAccessPolicy $policy,
        HintUnlockRepository $unlocks,
        RiddleEngagementRepository $riddleEngagements,
        HuntEngagementRepository $huntEngagements
    ) {
        $this->policy = $policy;
        $this->unlocks = $unlocks;
        $this->riddleEngagements = $riddleEngagements;
        $this->huntEngagements = $huntEngagements;
    }

    /**
     * @param int[] $hintIds
     * @return array<int, array<string, mixed>>
     */
    public function describe(int $userId, int $riddleId, int $huntId, array $hintIds, int $now): array {
        $hintIds = $this->policy->normalizeIds($hintIds);
        $configs = [];
        $prerequisiteIds = [];
        foreach ($hintIds as $hintId) {
            $configs[$hintId] = $this->configuration($hintId);
            $prerequisiteIds = array_merge($prerequisiteIds, $configs[$hintId]['prerequisites']);
        }

        $consultedAt = $this->consultedAt($userId, $prerequisiteIds);
        $riddleAt = $riddleId > 0
            ? $this->localTimestamp($this->riddleEngagements->findEngagedAt($userId, $riddleId))
            : null;
        $huntAt = $huntId > 0
            ? $this->localTimestamp($this->huntEngagements->findEngagedAt($userId, $huntId))
            : null;

        $gates = [];
        foreach ($hintIds as $hintId) {
            $config = $configs[$hintId];
            $engagementAt = $config['target'] === 'enigme' ? $riddleAt : $huntAt;
            $gate = $this->policy->evaluate(
                $now,
                $config['prerequisites'],
                $config['delay_minutes'],
                $config['anchor'],
                $engagementAt,
                $consultedAt
            );
            $gate['target'] = $config['target'];
            $gates[$hintId] = $gate;
        }

        return $gates;
    }

    public function canConsult(int $userId, int $hintId, int $now): bool {
        if ($userId <= 0 || $hintId <= 0 || !$this->isPublished($hintId, $now)) {
            return false;
        }

        $target = $this->targetType((string) get_field('indice_cible_type', $hintId));
        $riddleId = $target === 'enigme' ? $this->firstId(get_field('indice_enigme_linked', $hintId)) : 0;
        $huntId = $this->firstId(get_field('indice_chasse_linked', $hintId));
        $gates = $this->describe($userId, $riddleId, $huntId, [$hintId], $now);

        return ($gates[$hintId]['open'] ?? false) === true;
    }

    /** @return array{prerequisites:int[],delay_minutes:int,anchor:string,target:string} */
    private function configuration(int $hintId): array {
        $target = $this->targetType((string) get_field('indice_cible_type', $hintId));

        return [
            'prerequisites' => $this->policy->normalizeIds((array) get_field('indice_acces_pre_requis', $hintId)),
            'delay_minutes' => max(0, (int) get_field('indice_delai_minutes', $hintId)),
            'anchor' => (string) (get_field('indice_delai_ancrage', $hintId) ?: HintAccessPolicy::ANCHOR_NONE),
            'target' => $target,
        ];
    }

    /**
     * @param int[] $hintIds
     * @return array<int, int>
     */
    private function consultedAt(int $userId, array $hintIds): array {
        $stamps = [];
        foreach ($this->unlocks->findUnlockTimestamps($userId, $hintIds) as $hintId => $mysql) {
            $stamp = $this->utcTimestamp($mysql);
            if ($stamp !== null) {
                $stamps[(int) $hintId] = $stamp;
            }
        }

        return $stamps;
    }

    private function isPublished(int $hintId, int $now): bool {
        $state = (string) (get_field('indice_cache_etat_systeme', $hintId) ?: '');
        if ($state === 'accessible') {
            return true;
        }
        if ($state !== 'programme') {
            return false;
        }

        $availableAt = $this->localTimestamp((string) get_field('indice_date_disponibilite', $hintId));

        return $availableAt !== null && $availableAt <= $now;
    }

    private function targetType(string $target): string {
        return $target === 'enigme' ? 'enigme' : 'chasse';
    }

    /** @param mixed $value */
    private function firstId($value): int {
        $ids = $this->policy->normalizeIds(is_array($value) ? $value : [$value]);

        return $ids[0] ?? 0;
    }

    private function localTimestamp(?string $mysql): ?int {
        $timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');

        return $this->timestamp($mysql, $timezone);
    }

    private function utcTimestamp(?string $mysql): ?int {
        return $this->timestamp($mysql, new DateTimeZone('UTC'));
    }

    private function timestamp(?string $mysql, DateTimeZone $timezone): ?int {
        if ($mysql === null || trim($mysql) === '') {
            return null;
        }

        $date = date_create_from_format('Y-m-d H:i:s', $mysql, $timezone);

        return $date === false ? null : $date->getTimestamp();
    }
}
