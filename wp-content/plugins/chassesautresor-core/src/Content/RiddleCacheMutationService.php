<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Persist the cached riddle relationship stored on a hunt.
 */
class RiddleCacheMutationService {
    public const FIELD_NAME = 'chasse_cache_enigmes';
    public const FIELD_KEY = 'field_67b740025aae0';

    /**
     * @param callable(int, string): mixed $readMeta
     * @param callable(int, string, mixed): mixed $updateMeta
     */
    public function mutate(
        int $huntId,
        int $riddleId,
        string $action,
        callable $readMeta,
        callable $updateMeta
    ): bool {
        if ($huntId <= 0 || $riddleId <= 0 || !in_array($action, ['add', 'remove'], true)) {
            return false;
        }

        $current = $readMeta($huntId, self::FIELD_NAME);
        $current = is_array($current) ? $current : [];
        $containsRiddle = in_array($riddleId, array_map('intval', $current), true);

        if (($action === 'add' && $containsRiddle) || ($action === 'remove' && !$containsRiddle)) {
            return false;
        }

        if ($action === 'add') {
            $current[] = $riddleId;
        } else {
            $current = array_diff($current, [$riddleId]);
        }

        $updateMeta($huntId, self::FIELD_NAME, $current);
        $updateMeta($huntId, '_' . self::FIELD_NAME, self::FIELD_KEY);

        return true;
    }
}
