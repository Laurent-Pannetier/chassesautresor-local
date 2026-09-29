<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Compare the cached riddle relationships of a hunt with their source data.
 */
class HuntRiddleCacheService
{
    /**
     * @param int[] $expectedIds
     * @param int[] $cachedIds
     * @return array{synced:bool,expected:int[],cached:int[],correction:bool}
     */
    public function compare(array $expectedIds, array $cachedIds, bool $correct): array
    {
        $expectedIds = $this->normalizeIds($expectedIds);
        $cachedIds = $this->normalizeIds($cachedIds);
        $synced = empty(array_diff($expectedIds, $cachedIds))
            && empty(array_diff($cachedIds, $expectedIds));

        return [
            'synced' => $synced,
            'expected' => $expectedIds,
            'cached' => $cachedIds,
            'correction' => $correct && !$synced,
        ];
    }

    /**
     * @param int[] $cachedIds
     * @param array<int, int|null> $relatedHuntIds Related hunt IDs indexed by riddle ID.
     * @return array{synced:bool,cached:int[],invalid:int[],corrected:int[],correction:bool}
     */
    public function validate(int $huntId, array $cachedIds, array $relatedHuntIds, bool $correct): array
    {
        $cachedIds = $this->normalizeIds($cachedIds);
        $invalidIds = [];

        foreach ($cachedIds as $riddleId) {
            if (($relatedHuntIds[$riddleId] ?? null) !== $huntId) {
                $invalidIds[] = $riddleId;
            }
        }

        $synced = $invalidIds === [];

        return [
            'synced' => $synced,
            'cached' => $cachedIds,
            'invalid' => $invalidIds,
            'corrected' => array_values(array_diff($cachedIds, $invalidIds)),
            'correction' => $correct && !$synced,
        ];
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        $normalized = array_map('intval', $ids);
        $normalized = array_filter($normalized, static function (int $id): bool {
            return $id > 0;
        });

        return array_values(array_unique($normalized));
    }
}
