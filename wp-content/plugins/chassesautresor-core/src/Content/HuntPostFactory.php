<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Create and initialize a hunt after its creation request has been authorized.
 */
class HuntPostFactory {
    /** @return int|\WP_Error */
    public function create(
        int $authorId,
        int $organizerId,
        string $initialTitle,
        int $placeholderImageId,
        string $startDate,
        string $endDate
    ) {
        $huntId = wp_insert_post([
            'post_type' => 'chasse',
            'post_status' => 'pending',
            'post_title' => $initialTitle,
            'post_author' => $authorId,
        ]);

        if (is_wp_error($huntId)) {
            return $huntId;
        }

        $initialFields = $this->getInitialFields($organizerId, $placeholderImageId, $startDate, $endDate);
        foreach ($initialFields as $field => $value) {
            update_field($field, $value, $huntId);
        }
        update_post_meta($huntId, 'chasse_infos_date_debut_differee', 0);

        return $huntId;
    }

    /** @return array<string, mixed> */
    public function getInitialFields(
        int $organizerId,
        int $placeholderImageId,
        string $startDate,
        string $endDate
    ): array {
        return [
            'chasse_principale_image' => $placeholderImageId,
            'chasse_infos_date_debut' => $startDate,
            'chasse_infos_date_fin' => $endDate,
            'chasse_infos_duree_illimitee' => false,
            'chasse_infos_cout_points' => 0,
            'chasse_cache_statut' => 'revision',
            'chasse_cache_statut_validation' => 'creation',
            'chasse_cache_organisateur' => [$organizerId],
        ];
    }
}
