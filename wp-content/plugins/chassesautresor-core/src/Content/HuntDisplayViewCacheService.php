<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Persist per-user hunt display data outside the theme view-model builder. */
final class HuntDisplayViewCacheService
{
    private const GROUP = 'chasse_affichage';

    public function get(int $huntId, int $userId): ?array
    {
        $key = $this->key($huntId);
        $cache = wp_cache_get($key, self::GROUP);
        if (!is_array($cache)) {
            $cache = get_transient($key);
        }

        return is_array($cache) && isset($cache[$userId]) && is_array($cache[$userId])
            ? $cache[$userId]
            : null;
    }

    public function put(int $huntId, int $userId, array $data): void
    {
        $key = $this->key($huntId);
        $cache = wp_cache_get($key, self::GROUP);
        if (!is_array($cache)) {
            $cache = get_transient($key);
            $cache = is_array($cache) ? $cache : [];
        }
        $cache[$userId] = $data;
        wp_cache_set($key, $cache, self::GROUP, HOUR_IN_SECONDS);
        set_transient($key, $cache, HOUR_IN_SECONDS);
    }

    private function key(int $huntId): string
    {
        return 'chasse_infos_affichage_v2_' . $huntId;
    }
}
