<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Trophies;

/** Choose the trophies a staff member sees and who holds each one. */
final class TrophyWallCatalog
{
    /**
     * @param TrophyDefinition[] $trophies
     * @param array<int, array<string, mixed>> $awards
     * @param int[] $administratorIds
     * @param array<int, string> $userNames
     * @param array<int, string> $teamNames
     * @param int[] $organizerHuntIds
     * @return array<int, array<string, mixed>>
     */
    public function cards(
        array $trophies,
        array $awards,
        array $administratorIds,
        array $userNames,
        array $teamNames,
        bool $isAdmin,
        array $organizerHuntIds
    ): array {
        $administratorIds = $this->positiveIds($administratorIds);
        $organizerHuntIds = $this->positiveIds($organizerHuntIds);
        $cards = [];
        foreach ($trophies as $trophy) {
            if (!$trophy instanceof TrophyDefinition || !$trophy->active) {
                continue;
            }
            if (!$this->visible($trophy, $isAdmin, $organizerHuntIds)) {
                continue;
            }
            $holders = $this->holders($trophy, $awards, $administratorIds, $userNames, $teamNames);
            $cards[] = [
                'id' => $trophy->id,
                'title' => $trophy->title,
                'description' => $trophy->description,
                'image_id' => $trophy->imageId,
                'hunt_id' => $trophy->huntId,
                'count' => count($holders),
                'holders' => $holders,
            ];
        }

        return $cards;
    }

    /** @param int[] $organizerHuntIds */
    private function visible(TrophyDefinition $trophy, bool $isAdmin, array $organizerHuntIds): bool
    {
        if ($isAdmin) {
            return true;
        }

        return $trophy->huntId > 0 && in_array($trophy->huntId, $organizerHuntIds, true);
    }

    /**
     * @param array<int, array<string, mixed>> $awards
     * @param int[] $administratorIds
     * @param array<int, string> $userNames
     * @param array<int, string> $teamNames
     * @return string[]
     */
    private function holders(
        TrophyDefinition $trophy,
        array $awards,
        array $administratorIds,
        array $userNames,
        array $teamNames
    ): array {
        $names = [];
        $seen = [];
        foreach ($awards as $award) {
            if ((int) ($award['trophy_id'] ?? 0) !== $trophy->id) {
                continue;
            }
            $recipientId = (int) ($award['recipient_id'] ?? 0);
            if ($recipientId <= 0) {
                continue;
            }
            $type = (string) ($award['recipient_type'] ?? '');
            if ($trophy->scope === 'team') {
                $actorId = (int) ($award['actor_user_id'] ?? 0);
                if ($type !== 'team' || in_array($actorId, $administratorIds, true)) {
                    continue;
                }
                $key = 'team:' . $recipientId;
                $name = trim((string) ($teamNames[$recipientId] ?? ''));
            } else {
                if ($type !== 'user' || in_array($recipientId, $administratorIds, true)) {
                    continue;
                }
                $key = 'user:' . $recipientId;
                $name = trim((string) ($userNames[$recipientId] ?? ''));
            }
            if ($name === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $names[] = $name;
        }
        usort(
            $names,
            static fn (string $left, string $right): int => strcasecmp($left, $right)
        );

        return $names;
    }

    /**
     * @param int[] $ids
     * @return int[]
     */
    private function positiveIds(array $ids): array
    {
        $values = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $values[$id] = $id;
            }
        }

        return array_values($values);
    }
}
