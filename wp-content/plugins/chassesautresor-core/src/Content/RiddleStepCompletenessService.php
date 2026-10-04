<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\AnswerWidgetValidationService;

/** Verify that every configured intermediate step is publishable. */
final class RiddleStepCompletenessService {
    /** @param int[] $stepIds */
    public function areStepsComplete(
        array $stepIds,
        ?callable $getTitle = null,
        ?callable $getField = null
    ): bool {
        $getTitle = $getTitle ?? 'get_the_title';
        $getField = $getField ?? 'get_field';
        foreach (array_values(array_unique(array_filter(array_map('intval', $stepIds)))) as $stepId) {
            if (!$this->isStepComplete($stepId, $getTitle, $getField)) {
                return false;
            }
        }

        return true;
    }

    public function isStepComplete(int $stepId, callable $getTitle, callable $getField): bool {
        if ($stepId <= 0) {
            return false;
        }

        $widget = (string) ($getField('etape_reponse_widget', $stepId) ?: 'click');
        $configuration = (new AnswerWidgetValidationService())->validate([
            'widget' => $widget,
            'button_label' => (string) $getField('etape_reponse_bouton', $stepId),
            'accepted_answers' => (string) $getField('etape_reponses_texte', $stepId),
            'case_sensitive' => (bool) $getField('etape_reponse_casse', $stepId),
            'variants' => (string) $getField('etape_reponses_variantes', $stepId),
            'direction_sequences' => (string) $getField('etape_directions_sequences', $stepId),
            'color_sequences' => (string) $getField('etape_color_sequences', $stepId),
            'number_sequences' => (string) $getField('etape_number_sequences', $stepId),
            'safe_dial_sequences' => (string) $getField('etape_safe_dial_sequences', $stepId),
            'piano_sequences' => (string) $getField('etape_piano_sequences', $stepId),
            'gps_coordinates' => (string) $getField('etape_gps_coordinates', $stepId),
            'gps_tolerance' => (string) $getField('etape_gps_tolerance', $stepId),
        ]);
        if (is_wp_error($configuration)) {
            return false;
        }

        $requiresContent = in_array($widget, ['click', 'text'], true);
        $content = (new RiddleStepContentService())->validate(
            (string) $getTitle($stepId),
            (string) $getField('etape_contenu', $stepId),
            (int) $getField('etape_image', $stepId),
            $requiresContent
        );

        return !is_wp_error($content);
    }
}
