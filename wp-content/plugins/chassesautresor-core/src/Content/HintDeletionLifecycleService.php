<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Preserve and restore hint reordering targets across WordPress deletion hooks.
 */
class HintDeletionLifecycleService {
    private HintDeletionService $deletionService;

    public function __construct(?HintDeletionService $deletionService = null) {
        $this->deletionService = $deletionService ?? new HintDeletionService();
    }

    /**
     * @param mixed $linkedHunt
     * @param mixed $linkedRiddle
     * @param callable(int): int $resolveRiddleHuntId
     * @return array{reorder_targets:array<int, array{id:int,type:string}>}|null
     */
    public function capture(
        string $postType,
        string $targetType,
        $linkedHunt,
        $linkedRiddle,
        callable $resolveRiddleHuntId
    ): ?array {
        if ($postType !== 'indice') {
            return null;
        }

        $context = $this->deletionService->resolveContext(
            $targetType,
            $linkedHunt,
            $linkedRiddle,
            $resolveRiddleHuntId
        );

        return $context === null
            ? null
            : ['reorder_targets' => $context['reorder_targets']];
    }

    /**
     * @param mixed $context
     * @return array<int, array{id:int,type:string}>
     */
    public function restoreTargets($context): array {
        if (!is_array($context) || !isset($context['reorder_targets']) || !is_array($context['reorder_targets'])) {
            return [];
        }

        $targets = [];
        foreach ($context['reorder_targets'] as $target) {
            if (!is_array($target)) {
                continue;
            }

            $id = isset($target['id']) ? (int) $target['id'] : 0;
            $type = ($target['type'] ?? '') === 'enigme' ? 'enigme' : 'chasse';
            if ($id > 0) {
                $targets[] = ['id' => $id, 'type' => $type];
            }
        }

        return $targets;
    }
}
