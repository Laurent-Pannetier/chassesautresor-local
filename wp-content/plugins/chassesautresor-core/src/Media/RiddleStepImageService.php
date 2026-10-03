<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Authorize an intermediate-step image against the player's current progression. */
final class RiddleStepImageService {
    private RiddleStepImageRepository $repository;

    public function __construct(RiddleStepImageRepository $repository) {
        $this->repository = $repository;
    }

    /** @return array{step_id:int,riddle_id:int}|null */
    public function findContext(int $imageId, ?callable $getField = null): ?array {
        $stepId = $this->repository->findStepId($imageId);
        if ($stepId === null) {
            return null;
        }

        $getField = $getField ?? 'get_field';
        $riddleId = (int) $getField('etape_enigme_associee', $stepId);
        return $riddleId > 0 ? ['step_id' => $stepId, 'riddle_id' => $riddleId] : null;
    }

    public function canView(int $imageId, int $userId): bool {
        $context = $this->findContext($imageId);
        if ($context === null || $userId <= 0) {
            return false;
        }

        $riddleId = $context['riddle_id'];
        $canModify = function_exists('utilisateur_peut_modifier_post') && utilisateur_peut_modifier_post($riddleId);
        $canViewRiddle = (new ProtectedRiddleAssetService())->canViewRiddle($riddleId, $userId);

        global $wpdb;
        $orderedIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
        $state = CoreServiceFactory::riddleStepProgress($wpdb)->getState($userId, $riddleId, $orderedIds);
        return $this->canViewContext($context, $canViewRiddle, $state['visible_step_ids'], $canModify);
    }

    /** @param array{step_id:int,riddle_id:int} $context @param int[] $visibleStepIds */
    public function canViewContext(
        array $context,
        bool $canViewRiddle,
        array $visibleStepIds,
        bool $canModify
    ): bool {
        return $canModify || ($canViewRiddle && in_array($context['step_id'], $visibleStepIds, true));
    }
}
