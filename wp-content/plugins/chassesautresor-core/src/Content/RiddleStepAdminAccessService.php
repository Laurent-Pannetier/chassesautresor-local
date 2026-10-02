<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Decide whether a user may open the native editor for a riddle step. */
final class RiddleStepAdminAccessService {
    public function canEdit(
        int $stepId,
        callable $getPostType,
        callable $getParentRiddleId,
        callable $canModifyRiddle
    ): bool {
        if ($stepId <= 0 || $getPostType($stepId) !== RiddleStepPostTypeRegistrar::POST_TYPE) {
            return false;
        }

        $riddleId = $this->normalizeId($getParentRiddleId($stepId));
        return $riddleId > 0
            && $getPostType($riddleId) === 'enigme'
            && (bool) $canModifyRiddle($riddleId);
    }

    /** @param mixed $value */
    private function normalizeId($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_object($value) && isset($value->ID) ? (int) $value->ID : (int) $value;
    }
}
