<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Apply deterministic ranks and generated titles to an ordered hint list.
 */
class HintOrderingUpdater {
    private HintOrderingService $orderingService;
    private RelationshipService $relationshipService;

    public function __construct(
        ?HintOrderingService $orderingService = null,
        ?RelationshipService $relationshipService = null
    ) {
        $this->orderingService = $orderingService ?? new HintOrderingService();
        $this->relationshipService = $relationshipService ?? new RelationshipService();
    }

    /**
     * @param array<int, mixed> $hintIds
     * @param callable(int): string $getTitle
     * @param callable(int): mixed $getLinkedHunt
     * @param callable(int): string $buildPlaceholder
     * @param callable(array<string, mixed>): mixed $updatePost
     * @param callable(int, string, int): mixed $updateRank
     */
    public function apply(
        array $hintIds,
        string $targetType,
        int $targetId,
        string $defaultTitle,
        string $generatedPrefix,
        callable $getTitle,
        callable $getLinkedHunt,
        callable $buildPlaceholder,
        callable $updatePost,
        callable $updateRank
    ): int {
        $hints = [];
        foreach ($hintIds as $hintId) {
            $hintId = (int) $hintId;
            $hints[] = [
                'id' => $hintId,
                'title' => $getTitle($hintId),
                'hunt_id' => $this->relationshipService->normalizeId($getLinkedHunt($hintId)),
            ];
        }

        $updates = $this->orderingService->buildUpdatePlan(
            $hints,
            $targetType,
            $targetId,
            $defaultTitle,
            $generatedPrefix
        );
        foreach ($updates as $update) {
            if ($update['regenerate_title']) {
                $updatePost([
                    'ID' => $update['id'],
                    'post_title' => $buildPlaceholder($update['hunt_id'] ?? 0),
                ]);
            }

            $updateRank($update['id'], 'indice_rank', $update['rank']);
        }

        return count($updates);
    }

    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     * @param callable(int): mixed $resolveRiddleHunt
     * @return array<int, array{type:string,id:int}>
     */
    public function resolveAffectedTargets(
        string $storedTargetType,
        $linkedHunt,
        $linkedRiddle,
        callable $resolveRiddleHunt
    ): array {
        $targetType = $storedTargetType === 'enigme' ? 'enigme' : 'chasse';
        $targetId = $this->relationshipService->resolveHintTargetId(
            $targetType,
            $linkedHunt,
            $linkedRiddle
        );
        $huntId = $this->relationshipService->normalizeId($linkedHunt);

        if ($targetType === 'enigme' && $huntId === null && $targetId !== null) {
            $huntId = $this->relationshipService->normalizeId($resolveRiddleHunt($targetId));
        }

        return $this->orderingService->getAffectedTargets($targetType, $targetId, $huntId);
    }
}
