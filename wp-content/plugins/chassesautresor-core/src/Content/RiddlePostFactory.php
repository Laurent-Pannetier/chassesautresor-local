<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Create and initialize a riddle post after its request has been validated.
 */
class RiddlePostFactory {
    /**
     * @return int|\WP_Error
     */
    public function create(
        int $huntId,
        int $organizerId,
        int $authorId,
        string $initialTitle,
        string $unlockDate
    ) {
        $riddleId = wp_insert_post([
            'post_type' => 'enigme',
            'post_status' => 'pending',
            'post_title' => $initialTitle,
            'post_author' => $authorId,
        ]);

        if (is_wp_error($riddleId)) {
            return $riddleId;
        }

        if (get_option('chasse_associee_temp')) {
            delete_option('chasse_associee_temp');
        }

        foreach ($this->getInitialFields($huntId, $organizerId, $unlockDate) as $field => $value) {
            update_field($field, $value, $riddleId);
        }

        return $riddleId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getInitialFields(int $huntId, int $organizerId, string $unlockDate): array {
        return [
            'enigme_chasse_associee' => $huntId,
            'enigme_organisateur_associe' => $organizerId,
            'enigme_tentative_cout_points' => 0,
            'enigme_tentative_delai_secondes' => 0,
            'enigme_reponse_casse' => true,
            'enigme_acces_condition' => 'immediat',
            'enigme_acces_pre_requis' => [],
            'enigme_mode_validation' => 'automatique',
            'enigme_acces_date' => $unlockDate,
        ];
    }
}
