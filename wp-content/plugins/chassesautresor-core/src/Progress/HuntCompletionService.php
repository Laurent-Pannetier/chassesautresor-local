<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Decide whether resolving a riddle completes its hunt automatically.
 */
class HuntCompletionService
{
    private HuntProgressService $progress;
    private HuntRiddleClassifier $classifier;

    public function __construct(HuntProgressService $progress, HuntRiddleClassifier $classifier)
    {
        $this->progress = $progress;
        $this->classifier = $classifier;
    }

    /**
     * @return array{hunt_id:int,is_automatic:bool,has_riddles:bool,is_complete:bool}
     */
    public function evaluate(int $userId, int $riddleId): array
    {
        $huntId = $this->getHuntId($riddleId);

        if ($userId <= 0 || $huntId <= 0) {
            return $this->result($huntId, false, false, false);
        }

        $isAutomatic = $this->getEndMode($huntId) === 'automatique';

        if (!$isAutomatic) {
            return $this->result($huntId, false, false, false);
        }

        $riddleIds = $this->getRiddleIds($huntId);

        if ($riddleIds === []) {
            return $this->result($huntId, true, false, false);
        }

        $classified = $this->classifier->classify($riddleIds);
        $progress = $this->progress->calculate(
            $userId,
            $classified['validatable'],
            $classified['engagement_only']
        );

        return $this->result($huntId, true, true, $progress['is_complete']);
    }

    protected function getHuntId(int $riddleId): int
    {
        $temporaryHuntId = (int) get_option('chasse_associee_temp');
        if ($temporaryHuntId > 0) {
            delete_option('chasse_associee_temp');
            return $temporaryHuntId;
        }

        return $this->normalizeId(get_field('enigme_chasse_associee', $riddleId));
    }

    protected function getEndMode(int $huntId): string
    {
        return (string) (get_field('chasse_mode_fin', $huntId) ?: 'automatique');
    }

    /** @return int[] */
    protected function getRiddleIds(int $huntId): array
    {
        $values = get_field('chasse_cache_enigmes', $huntId);
        if (!is_array($values)) {
            return [];
        }

        $riddleIds = [];
        foreach ($values as $value) {
            $riddleId = $this->normalizeId($value);
            if ($riddleId > 0 && get_post_type($riddleId) === 'enigme') {
                $riddleIds[$riddleId] = $riddleId;
            }
        }

        return array_values($riddleIds);
    }

    /** @param mixed $value */
    private function normalizeId($value): int
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_object($value) && isset($value->ID)) {
            return (int) $value->ID;
        }

        return (int) $value;
    }

    /**
     * @return array{hunt_id:int,is_automatic:bool,has_riddles:bool,is_complete:bool}
     */
    private function result(int $huntId, bool $automatic, bool $hasRiddles, bool $complete): array
    {
        return [
            'hunt_id' => $huntId,
            'is_automatic' => $automatic,
            'has_riddles' => $hasRiddles,
            'is_complete' => $complete,
        ];
    }
}
