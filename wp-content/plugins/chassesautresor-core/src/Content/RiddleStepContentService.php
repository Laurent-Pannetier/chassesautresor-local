<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Validate and persist the non-answer content of an intermediate step. */
final class RiddleStepContentService {
    /** @return true|\WP_Error */
    public function save(
        int $stepId,
        string $title,
        string $content,
        int $imageId,
        ?callable $updatePost = null,
        ?callable $updateField = null
    ) {
        $title = trim($title);
        $hasContent = trim(wp_strip_all_tags($content)) !== '';
        if ($title === '') {
            return new \WP_Error('missing_title', __('Le nom de l’étape est obligatoire.', 'chassesautresor-com'));
        }
        if (!$hasContent && $imageId <= 0) {
            return new \WP_Error(
                'missing_content',
                __('Ajoutez un texte ou une image à l’étape.', 'chassesautresor-com')
            );
        }

        $updatePost = $updatePost ?? 'wp_update_post';
        $updateField = $updateField ?? 'update_field';
        $result = $updatePost([
            'ID' => $stepId,
            'post_title' => $title,
            'post_status' => 'publish',
        ], true);
        if (is_wp_error($result)) {
            return $result;
        }

        $updateField('etape_contenu', $content, $stepId);
        $updateField('etape_image', $imageId > 0 ? $imageId : false, $stepId);

        return true;
    }
}
