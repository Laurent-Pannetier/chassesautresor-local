<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Resolve and persist the hunt relationship required by a hint.
 */
class HintRelationshipService {
    private RelationshipService $relationshipService;

    public function __construct(?RelationshipService $relationshipService = null) {
        $this->relationshipService = $relationshipService ?? new RelationshipService();
    }

    /**
     * @param mixed $linkedRiddle
     * @param mixed $requestedHunt
     * @param callable(int): mixed $resolveRiddleHunt
     */
    public function resolveLinkedHuntId(
        string $targetType,
        $linkedRiddle,
        $requestedHunt,
        callable $resolveRiddleHunt
    ): ?int {
        if ($targetType === 'chasse') {
            return $this->relationshipService->normalizeId($requestedHunt);
        }

        if ($targetType !== 'enigme') {
            return null;
        }

        $riddleId = $this->relationshipService->normalizeId($linkedRiddle);
        if ($riddleId === null) {
            return null;
        }

        return $this->relationshipService->normalizeId($resolveRiddleHunt($riddleId));
    }

    /**
     * @param callable(string, int, int): mixed $updateField
     */
    public function persistLinkedHunt(int $hintId, ?int $huntId, callable $updateField): bool {
        if ($huntId === null) {
            return false;
        }

        return $updateField('indice_chasse_linked', $huntId, $hintId) !== false;
    }

    /**
     * @param mixed $huntValue
     */
    public function normalizeHuntId($huntValue): ?int {
        return $this->relationshipService->normalizeId($huntValue);
    }
}
