<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Apply a validated ordering to every step attached to a riddle. */
final class RiddleStepOrderingApplicationService {
    private RiddleStepQueryService $queryService;
    private RiddleStepOrderingService $orderingService;

    public function __construct(
        ?RiddleStepQueryService $queryService = null,
        ?RiddleStepOrderingService $orderingService = null
    ) {
        $this->queryService = $queryService ?? new RiddleStepQueryService();
        $this->orderingService = $orderingService ?? new RiddleStepOrderingService();
    }

    /** @param mixed[] $submittedIds */
    public function reorder(
        int $riddleId,
        array $submittedIds,
        ?callable $getPosts = null,
        ?callable $updatePost = null
    ): bool {
        $existingIds = $this->queryService->findOrderedIds($riddleId, $getPosts);
        $updates = $this->orderingService->buildUpdatePlan($existingIds, $submittedIds);
        if ($existingIds === []) {
            return array_values(array_filter(array_map('intval', $submittedIds))) === [];
        }
        if ($updates === [] && $existingIds !== []) {
            return false;
        }

        $updatePost = $updatePost ?? 'wp_update_post';
        foreach ($updates as $update) {
            $result = $updatePost([
                'ID' => $update['id'],
                'menu_order' => $update['menu_order'],
            ], true);
            if (is_wp_error($result)) {
                return false;
            }
        }

        return true;
    }
}
