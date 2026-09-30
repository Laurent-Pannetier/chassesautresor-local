<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/**
 * Resolve and permanently delete solutions through a normalized target context.
 */
class SolutionDeletionService {
    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     * @return array{id:int,type:string}|null
     */
    public function resolveTarget(string $storedTargetType, $linkedHunt, $linkedRiddle): ?array {
        $targetType = $storedTargetType === 'enigme' ? 'enigme' : 'chasse';
        $targetId = (new RelationshipService())->resolveTargetId(
            $targetType,
            $linkedHunt,
            $linkedRiddle
        );

        if ($targetId === null) {
            return null;
        }

        return ['id' => $targetId, 'type' => $targetType];
    }

    public function delete(int $solutionId): bool {
        return get_post_type($solutionId) === 'solution'
            && wp_delete_post($solutionId, true) !== false;
    }
}
