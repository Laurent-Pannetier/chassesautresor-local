<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Resolve the riddle associated with a protected image.
 */
class RiddleImageRepository
{
    private $wpdb;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function findRiddleId(int $imageId): ?int
    {
        $table = $this->wpdb->prefix . 'acf_enigme_visuel_image';
        if ($this->wpdb->get_var($this->wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            $riddleId = (int) $this->wpdb->get_var(
                $this->wpdb->prepare("SELECT post_id FROM {$table} WHERE value = %d LIMIT 1", $imageId)
            );
            if ($riddleId > 0) {
                return $riddleId;
            }
        }

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
}
