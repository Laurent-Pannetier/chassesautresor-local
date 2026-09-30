<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Resolve the affected targets and permanently delete a hint.
 */
class HintDeletionService {
    private RelationshipService $relationshipService;

    public function __construct(?RelationshipService $relationshipService = null) {
        $this->relationshipService = $relationshipService ?? new RelationshipService();
    }

    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     * @param callable(int): int|null $resolveRiddleHuntId
     * @return array<string, mixed>|null
     */
    public function resolveContext(
        string $storedTargetType,
        $linkedHunt,
        $linkedRiddle,
        callable $resolveRiddleHuntId
    ): ?array {
        $targetType = $storedTargetType === 'enigme' ? 'enigme' : 'chasse';
        $targetId = $this->relationshipService->resolveHintTargetId(
            $targetType,
            $linkedHunt,
            $linkedRiddle
        );
        if ($targetId === null) {
            return null;
        }

        $normalizedLinkedHunt = $this->relationshipService->normalizeId($linkedHunt);
        $riddleHuntId = $normalizedLinkedHunt;
        if ($targetType === 'enigme' && $riddleHuntId === null) {
            $riddleHuntId = $resolveRiddleHuntId($targetId);
        }
        $huntId = $this->relationshipService->resolveTargetHuntId(
            $targetType,
            $normalizedLinkedHunt,
            $riddleHuntId
        );
        $reorderTargets = [['id' => $targetId, 'type' => $targetType]];
        if ($targetType === 'enigme' && $huntId !== null) {
            $reorderTargets[] = ['id' => $huntId, 'type' => 'chasse'];
        }

        return [
            'target_id' => $targetId,
            'target_type' => $targetType,
            'hunt_id' => $huntId,
            'reorder_targets' => $reorderTargets,
        ];
    }

    public function delete(
        int $hintId,
        ?callable $getPostType = null,
        ?callable $deletePost = null
    ): bool {
        $getPostType = $getPostType ?? 'get_post_type';
        $deletePost = $deletePost ?? 'wp_delete_post';

        return $getPostType($hintId) === 'indice'
            && $deletePost($hintId, true) !== false;
    }
}
