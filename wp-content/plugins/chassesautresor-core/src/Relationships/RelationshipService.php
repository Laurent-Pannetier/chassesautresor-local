<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Normalize relationship values independently from their WordPress storage format.
 */
class RelationshipService
{
    /**
     * @param mixed $value
     */
    public function normalizeId($value): ?int
    {
        if (is_array($value)) {
            $value = array_key_exists('ID', $value) ? $value['ID'] : reset($value);

            return $this->normalizeId($value);
        }

        if (is_object($value) && isset($value->ID)) {
            $value = $value->ID;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $relationshipId = (int) $value;

        return $relationshipId > 0 ? $relationshipId : null;
    }

    /**
     * @param mixed[] $values
     * @return int[]
     */
    public function normalizeIds(array $values): array
    {
        $ids = [];

        foreach ($values as $value) {
            $relationshipId = $this->normalizeId($value);
            if ($relationshipId !== null) {
                $ids[] = $relationshipId;
            }
        }

        return $ids;
    }

    /**
     * Resolve the hunt affected by content targeting either a hunt or a riddle.
     *
     * @param mixed $directHunt
     * @param mixed $riddleHunt
     */
    public function resolveTargetHuntId(string $targetType, $directHunt, $riddleHunt): ?int
    {
        if ($targetType === 'chasse') {
            return $this->normalizeId($directHunt);
        }

        if ($targetType === 'enigme') {
            return $this->normalizeId($riddleHunt);
        }

        return null;
    }

    /**
     * Resolve the content directly targeted by a hint.
     *
     * @param mixed $hunt
     * @param mixed $riddle
     */
    public function resolveHintTargetId(string $targetType, $hunt, $riddle): ?int
    {
        return $this->resolveTargetId($targetType, $hunt, $riddle);
    }

    /**
     * Resolve content directly targeting either a hunt or a riddle.
     *
     * @param mixed $hunt
     * @param mixed $riddle
     */
    public function resolveTargetId(string $targetType, $hunt, $riddle): ?int
    {
        if ($targetType === 'chasse') {
            return $this->normalizeId($hunt);
        }

        if ($targetType === 'enigme') {
            return $this->normalizeId($riddle);
        }

        return null;
    }
}
