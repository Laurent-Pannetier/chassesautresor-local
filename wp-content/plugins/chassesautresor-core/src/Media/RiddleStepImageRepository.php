<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/** Resolve the intermediate step that owns an attachment. */
class RiddleStepImageRepository {
    private $wpdb;

    public function __construct($wpdb) {
        $this->wpdb = $wpdb;
    }

    public function findStepId(int $imageId): ?int {
        if ($imageId <= 0) {
            return null;
        }

        $stepId = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT posts.ID FROM {$this->wpdb->posts} posts "
                . "INNER JOIN {$this->wpdb->postmeta} image_meta ON image_meta.post_id = posts.ID "
                . "WHERE posts.post_type = 'enigme_etape' AND image_meta.meta_key = 'etape_image' "
                . 'AND image_meta.meta_value = %s LIMIT 1',
                (string) $imageId
            )
        );

        return $stepId > 0 ? $stepId : null;
    }
}
