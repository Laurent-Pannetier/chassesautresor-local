<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Resolve the riddle associated with a protected image.
 */
class RiddleImageRepository
{
    public const CACHE_GROUP = 'cta_riddle_image';

    private const LOOKUP_TTL = 900;

    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public static function forgetImage(int $imageId): void
    {
        if ($imageId > 0 && function_exists('wp_cache_delete')) {
            wp_cache_delete('riddle_' . $imageId, self::CACHE_GROUP);
        }
    }

    public function findRiddleId(int $imageId): ?int
    {
        if ($imageId <= 0) {
            return null;
        }

        $cached = $this->recall($imageId);
        if ($cached !== false) {
            return $cached > 0 ? $cached : null;
        }

        $riddleId = $this->queryAcfTable($imageId) ?? $this->queryPostMeta($imageId);
        $this->remember($imageId, $riddleId ?? 0);

        return $riddleId;
    }

    private function queryAcfTable(int $imageId): ?int
    {
        $table = $this->wpdb->prefix . 'acf_enigme_visuel_image';
        $previousError = (string) ($this->wpdb->last_error ?? '');
        $restoreErrors = true;
        if (method_exists($this->wpdb, 'suppress_errors')) {
            $restoreErrors = $this->wpdb->suppress_errors(true);
        }

        $riddleId = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT post_id FROM {$table} WHERE value = %d LIMIT 1",
                $imageId
            )
        );
        $error = (string) ($this->wpdb->last_error ?? '');
        if (method_exists($this->wpdb, 'suppress_errors')) {
            $this->wpdb->suppress_errors($restoreErrors);
        }
        $this->wpdb->last_error = $previousError;

        if ($error !== '') {
            return null;
        }

        return $riddleId > 0 ? $riddleId : null;
    }

    private function queryPostMeta(int $imageId): ?int
    {
        $serializedId = '%:"' . $this->wpdb->esc_like((string) $imageId) . '";%';
        $riddleId = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT post_id FROM {$this->wpdb->postmeta} "
                . "WHERE meta_key = 'enigme_visuel_image' AND meta_value LIKE %s LIMIT 1",
                $serializedId
            )
        );

        return $riddleId > 0 ? $riddleId : null;
    }

    /** @return int|false Cached id (0 when the image has no riddle), or false on a miss. */
    private function recall(int $imageId): int|false
    {
        if (!function_exists('wp_cache_get')) {
            return false;
        }

        $cached = wp_cache_get('riddle_' . $imageId, self::CACHE_GROUP);
        if ($cached === false || $cached === null) {
            return false;
        }

        return (int) $cached;
    }

    private function remember(int $imageId, int $riddleId): void
    {
        if (function_exists('wp_cache_set')) {
            wp_cache_set('riddle_' . $imageId, $riddleId, self::CACHE_GROUP, self::LOOKUP_TTL);
        }
    }
}
