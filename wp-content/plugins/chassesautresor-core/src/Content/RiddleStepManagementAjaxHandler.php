<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX transport for editing, deleting and ordering intermediate steps. */
final class RiddleStepManagementAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_charger_etape_enigme', [self::class, 'load']);
        $addAction('wp_ajax_enregistrer_etape_enigme', [self::class, 'save']);
        $addAction('wp_ajax_supprimer_etape_enigme', [self::class, 'delete']);
        $addAction('wp_ajax_reordonner_etapes_enigme', [self::class, 'reorder']);
    }

    public static function load(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        self::assertCanModify($riddleId);
        if (!self::belongsToRiddle($stepId, $riddleId)) {
            wp_send_json_error(['message' => __('Étape introuvable.', 'chassesautresor-com')]);
        }

        $imageId = (int) get_field('etape_image', $stepId);
        wp_send_json_success([
            'step_id' => $stepId,
            'title' => get_the_title($stepId),
            'content' => (string) get_field('etape_contenu', $stepId),
            'image_id' => $imageId,
            'image_url' => $imageId > 0 ? (string) wp_get_attachment_image_url($imageId, 'medium') : '',
            'widget' => (string) (get_field('etape_reponse_widget', $stepId) ?: 'click'),
            'button_label' => (string) (get_field('etape_reponse_bouton', $stepId)
                ?: __('Continuer', 'chassesautresor-com')),
            'accepted_answers' => (string) get_field('etape_reponses_texte', $stepId),
            'case_sensitive' => (bool) get_field('etape_reponse_casse', $stepId),
            'variants' => (string) get_field('etape_reponses_variantes', $stepId),
            'direction_sequences' => (string) get_field('etape_directions_sequences', $stepId),
            'color_sequences' => (string) get_field('etape_color_sequences', $stepId),
            'number_sequences' => (string) get_field('etape_number_sequences', $stepId),
            'safe_dial_sequences' => (string) get_field('etape_safe_dial_sequences', $stepId),
        ]);
    }

    public static function save(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        self::assertCanModify($riddleId);

        $title = isset($_POST['titre'])
            ? sanitize_text_field(wp_unslash((string) $_POST['titre']))
            : '';
        $content = isset($_POST['contenu']) ? wp_kses_post(wp_unslash((string) $_POST['contenu'])) : '';
        $imageId = isset($_POST['image_id']) ? (int) $_POST['image_id'] : 0;
        $widget = isset($_POST['widget']) ? sanitize_key(wp_unslash((string) $_POST['widget'])) : '';
        $buttonLabel = isset($_POST['button_label'])
            ? sanitize_text_field(wp_unslash((string) $_POST['button_label']))
            : '';
        $acceptedAnswers = isset($_POST['accepted_answers'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['accepted_answers']))
            : '';
        $caseSensitive = isset($_POST['case_sensitive']) ? 1 : 0;
        $variants = isset($_POST['variants'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['variants']))
            : '';
        $directionSequences = isset($_POST['direction_sequences'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['direction_sequences']))
            : '';
        $colorSequences = isset($_POST['color_sequences'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['color_sequences']))
            : '';
        $numberSequences = isset($_POST['number_sequences'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['number_sequences']))
            : '';
        $safeDialSequences = isset($_POST['safe_dial_sequences'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['safe_dial_sequences']))
            : '';
        if ($imageId > 0 && get_post_type($imageId) !== 'attachment') {
            wp_send_json_error(['message' => __('Image invalide.', 'chassesautresor-com')]);
        }

        $created = false;
        if ($stepId === 0) {
            self::assertStructureEditable($riddleId);
            $position = count((new RiddleStepQueryService())->findOrderedIds($riddleId));
            $stepId = (new RiddleStepCreationService())->create(
                $riddleId,
                (int) get_current_user_id(),
                $title,
                $position
            );
            if (is_wp_error($stepId)) {
                wp_send_json_error(['message' => $stepId->get_error_message()]);
            }
            $stepId = (int) $stepId;
            $created = true;
        } elseif (!self::belongsToRiddle($stepId, $riddleId)) {
            wp_send_json_error(['message' => __('Étape introuvable.', 'chassesautresor-com')]);
        }

        $storedWidget = (string) (get_field('etape_reponse_widget', $stepId) ?: 'click');
        $widgetType = $widget !== '' ? $widget : $storedWidget;
        $requiresContent = in_array($widgetType, ['click', 'text'], true);
        $result = (new RiddleStepContentService())->save(
            $stepId,
            $title,
            $content,
            $imageId,
            $requiresContent
        );
        if (is_wp_error($result)) {
            if ($created) {
                wp_delete_post($stepId, true);
            }
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        if (
            $widget !== ''
            || $buttonLabel !== ''
            || $acceptedAnswers !== ''
            || $directionSequences !== ''
            || $colorSequences !== ''
            || $numberSequences !== ''
            || $safeDialSequences !== ''
        ) {
            self::assertStructureEditable($riddleId);
            $answers = array_filter(array_map('trim', preg_split('/\R/', $acceptedAnswers) ?: []));
            if (
                !in_array($widget, ['click', 'text', 'directions', 'colors', 'numbers', 'safe_dial'], true)
                || ($widget === 'click' && $buttonLabel === '')
                || ($widget === 'text' && $answers === [])
                || ($widget === 'directions' && trim($directionSequences) === '')
                || ($widget === 'colors' && trim($colorSequences) === '')
                || ($widget === 'numbers' && trim($numberSequences) === '')
                || ($widget === 'safe_dial' && trim($safeDialSequences) === '')
            ) {
                wp_send_json_error(['message' => __('Mode de réponse invalide.', 'chassesautresor-com')]);
            }
            update_field('etape_reponse_widget', $widget, $stepId);
            update_field('etape_reponse_bouton', $buttonLabel, $stepId);
            update_field('etape_reponses_texte', implode("\n", $answers), $stepId);
            update_field('etape_reponse_casse', $caseSensitive, $stepId);
            update_field('etape_reponses_variantes', $variants, $stepId);
            update_field('etape_directions_sequences', $directionSequences, $stepId);
            update_field('etape_color_sequences', $colorSequences, $stepId);
            update_field('etape_number_sequences', $numberSequences, $stepId);
            update_field('etape_safe_dial_sequences', $safeDialSequences, $stepId);
        }

        wp_send_json_success([
            'step_id' => $stepId,
            'title' => get_the_title($stepId),
        ]);
    }

    public static function delete(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        self::assertCanModify($riddleId);
        self::assertStructureEditable($riddleId);

        if (!self::belongsToRiddle($stepId, $riddleId) || wp_delete_post($stepId, true) === false) {
            wp_send_json_error('suppression_impossible');
        }

        wp_send_json_success(['step_id' => $stepId]);
    }

    public static function reorder(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepIds = isset($_POST['etape_ids']) ? (array) wp_unslash($_POST['etape_ids']) : [];
        self::assertCanModify($riddleId);
        self::assertStructureEditable($riddleId);

        if (!(new RiddleStepOrderingApplicationService())->reorder($riddleId, $stepIds)) {
            wp_send_json_error('ordre_invalide');
        }

        wp_send_json_success();
    }

    private static function assertCanModify(int $riddleId): void {
        $allowed = is_user_logged_in()
            && $riddleId > 0
            && get_post_type($riddleId) === 'enigme'
            && function_exists('utilisateur_peut_modifier_post')
            && utilisateur_peut_modifier_post($riddleId);
        if (!$allowed) {
            wp_send_json_error('permission_refusee');
        }
    }

    private static function belongsToRiddle(int $stepId, int $riddleId): bool {
        return $stepId > 0
            && get_post_type($stepId) === RiddleStepPostTypeRegistrar::POST_TYPE
            && (int) get_field('etape_enigme_associee', $stepId) === $riddleId;
    }

    private static function assertStructureEditable(int $riddleId): void {
        global $wpdb;

        $locked = (new RiddleStepStructureLockService())->isLocked(
            $riddleId,
            static fn (string $field, int $postId) => get_field($field, $postId),
            static fn (int $id): bool => CoreServiceFactory::riddleStepProgress($wpdb)
                ->hasProgressForRiddle($id)
        );
        if ($locked) {
            wp_send_json_error([
                'message' => __(
                    'La structure des étapes est figée pour cette énigme.',
                    'chassesautresor-com'
                ),
            ]);
        }
    }
}
