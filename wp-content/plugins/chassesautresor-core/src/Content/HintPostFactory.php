<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Create and initialize a hint post after its request has been validated.
 */
class HintPostFactory {
    /**
     * @return int|\WP_Error
     */
    public function create(
        int $targetId,
        string $targetType,
        int $huntId,
        int $authorId,
        int $rank,
        string $initialTitle,
        int $currentTimestamp,
        int $availabilityDelay
    ) {
        $initialState = (new HintCreationService())->getInitialState(
            $currentTimestamp,
            $availabilityDelay
        );
        $hintId = wp_insert_post([
            'post_type' => 'indice',
            'post_status' => $initialState['post_status'],
            'post_title' => $initialTitle,
            'post_author' => $authorId,
        ]);

        if (is_wp_error($hintId)) {
            return $hintId;
        }

        update_post_meta($hintId, 'indice_rank', $rank);
        $availabilityDate = wp_date('Y-m-d H:i:s', $initialState['availability_timestamp']);
        foreach (
            $this->getInitialFields($targetId, $targetType, $huntId, $initialState, $availabilityDate)
            as $field => $value
        ) {
            update_field($field, $value, $hintId);
        }

        return $hintId;
    }

    /**
     * @param array<string, mixed> $initialState
     * @return array<string, mixed>
     */
    public function getInitialFields(
        int $targetId,
        string $targetType,
        int $huntId,
        array $initialState,
        string $availabilityDate
    ): array {
        $fields = [
            'indice_cible_type' => $targetType,
            'indice_chasse_linked' => $huntId,
            'indice_disponibilite' => $initialState['availability'],
            'indice_date_disponibilite' => $availabilityDate,
            'indice_cout_points' => $initialState['points_cost'],
            'indice_cache_complet' => $initialState['complete'],
            'indice_cache_etat_systeme' => $initialState['system_state'],
        ];

        if ($targetType === 'enigme') {
            $fields['indice_enigme_linked'] = $targetId;
        }

        return $fields;
    }
}
