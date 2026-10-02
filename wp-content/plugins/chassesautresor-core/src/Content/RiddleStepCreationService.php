<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Create an incomplete intermediate step attached to a riddle. */
final class RiddleStepCreationService {
    /** @return int|\WP_Error */
    public function create(
        int $riddleId,
        int $authorId,
        string $title,
        int $position,
        ?callable $getPostType = null,
        ?callable $insertPost = null,
        ?callable $updateField = null
    ) {
        $getPostType = $getPostType ?? 'get_post_type';
        $insertPost = $insertPost ?? 'wp_insert_post';
        $updateField = $updateField ?? 'update_field';

        if ($riddleId <= 0 || $authorId <= 0 || $getPostType($riddleId) !== 'enigme') {
            return new \WP_Error('invalid_riddle', __('Énigme invalide.', 'chassesautresor-com'));
        }

        $title = trim($title);
        if ($title === '') {
            $title = __('Nouvelle étape', 'chassesautresor-com');
        }

        $stepId = $insertPost([
            'post_type' => RiddleStepPostTypeRegistrar::POST_TYPE,
            'post_status' => 'draft',
            'post_title' => $title,
            'post_author' => $authorId,
            'menu_order' => max(0, $position),
        ]);
        if (is_wp_error($stepId)) {
            return $stepId;
        }

        $stepId = (int) $stepId;
        $updateField('etape_enigme_associee', $riddleId, $stepId);

        return $stepId;
    }
}
