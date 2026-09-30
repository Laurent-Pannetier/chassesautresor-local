<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Mutate an ACF relationship without discarding its other related posts.
 */
class AcfRelationshipMutationService {
    /**
     * @param callable(int, string, bool): mixed $getMeta
     * @param callable(int, string, mixed): mixed $updateMeta
     */
    public function mutate(
        int $postId,
        string $field,
        int $relatedPostId,
        string $fieldKey,
        string $action,
        callable $getMeta,
        callable $updateMeta
    ): bool {
        if ($postId <= 0 || $field === '' || $relatedPostId <= 0) {
            return false;
        }

        $current = $getMeta($postId, $field, true);
        $current = is_array($current) ? array_values($current) : [];
        $position = array_search($relatedPostId, $current, false);

        if ($action === 'add') {
            if ($position !== false) {
                return false;
            }
            $current[] = $relatedPostId;
        } elseif ($action === 'remove') {
            if ($position === false) {
                return false;
            }
            unset($current[$position]);
            $current = array_values($current);
        } else {
            return false;
        }

        $updateMeta($postId, $field, $current);
        $updateMeta($postId, '_' . $field, $fieldKey);

        return true;
    }
}
