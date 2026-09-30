<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Build deterministic updates when hints attached to a target are reordered.
 */
class HintOrderingService
{
    private HintTitleService $titleService;

    public function __construct(?HintTitleService $titleService = null)
    {
        $this->titleService = $titleService ?? new HintTitleService();
    }

    /**
     * @param array<int, array{id:int, title:string, hunt_id:?int}> $hints
     * @return array<int, array{id:int, rank:int, regenerate_title:bool, hunt_id:?int}>
     */
    public function buildUpdatePlan(
        array $hints,
        string $targetType,
        int $targetId,
        string $defaultTitle,
        string $generatedPrefix
    ): array {
        if ($targetId <= 0 || !in_array($targetType, ['chasse', 'enigme'], true)) {
            return [];
        }

        $updates = [];
        $rank = 1;

        foreach ($hints as $hint) {
            $hintId = (int) ($hint['id'] ?? 0);
            if ($hintId <= 0) {
                continue;
            }

            $huntId = isset($hint['hunt_id']) && (int) $hint['hunt_id'] > 0
                ? (int) $hint['hunt_id']
                : null;

            if ($huntId === null && $targetType === 'chasse') {
                $huntId = $targetId;
            }

            $updates[] = [
                'id' => $hintId,
                'rank' => $rank,
                'regenerate_title' => $this->titleService->shouldRegenerate(
                    (string) ($hint['title'] ?? ''),
                    $defaultTitle,
                    $generatedPrefix
                ),
                'hunt_id' => $huntId,
            ];
            ++$rank;
        }

        return $updates;
    }

    /**
     * List the target whose hints changed and the parent hunt that must also be reordered.
     *
     * @return array<int, array{type:string, id:int}>
     */
    public function getAffectedTargets(string $targetType, ?int $targetId, ?int $huntId): array
    {
        if ($targetType === 'chasse' && $targetId !== null && $targetId > 0) {
            return [['type' => 'chasse', 'id' => $targetId]];
        }

        if ($targetType !== 'enigme') {
            return [];
        }

        $targets = [];
        if ($targetId !== null && $targetId > 0) {
            $targets[] = ['type' => 'enigme', 'id' => $targetId];
        }

        if ($huntId !== null && $huntId > 0) {
            $targets[] = ['type' => 'chasse', 'id' => $huntId];
        }

        return $targets;
    }
}
