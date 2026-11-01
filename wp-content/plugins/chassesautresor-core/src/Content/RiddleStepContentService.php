<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** Validate and persist the non-answer content of an intermediate step. */
final class RiddleStepContentService {
    /**
     * @param int[] $imageIds
     * @return true|\WP_Error
     */
    public function validate(string $title, string $content, array $imageIds, bool $requiresContent = true) {
        $title = trim($title);
        $hasContent = trim(wp_strip_all_tags($content)) !== '';
        $imageIds = RiddleStepGallery::normalize($imageIds);
        if ($title === '') {
            return new \WP_Error('missing_title', __('Le nom de l’étape est obligatoire.', 'chassesautresor-com'));
        }
        if ($requiresContent && !$hasContent && $imageIds === []) {
            return new \WP_Error(
                'missing_content',
                __('Ajoutez un texte ou au moins une page à l’étape.', 'chassesautresor-com')
            );
        }

        return true;
    }

    /**
     * @param int[] $imageIds
     * @return true|\WP_Error
     */
    public function save(
        int $stepId,
        string $title,
        string $content,
        array $imageIds,
        bool $requiresContent = true,
        ?callable $updatePost = null,
        ?callable $updateField = null
    ) {
        $imageIds = RiddleStepGallery::normalize($imageIds);
        $validation = $this->validate($title, $content, $imageIds, $requiresContent);
        if (is_wp_error($validation)) {
            return $validation;
        }
        $title = trim($title);

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
        $updateField('etape_image', $imageIds === [] ? false : $imageIds, $stepId);

        return true;
    }
}
