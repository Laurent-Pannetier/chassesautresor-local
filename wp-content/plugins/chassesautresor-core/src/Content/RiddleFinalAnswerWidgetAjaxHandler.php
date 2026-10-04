<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Progress\AnswerWidgetValidationService;
use ChassesAuTresor\Core\Progress\RiddleFinalAnswerWidgetPersistenceService;

/** AJAX transport for editing the enigme final automatic answer widget. */
final class RiddleFinalAnswerWidgetAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_charger_reponse_finale_enigme', [self::class, 'load']);
        $addAction('wp_ajax_enregistrer_reponse_finale_enigme', [self::class, 'save']);
    }

    public static function load(): void {
        check_ajax_referer('riddle_final_answer_widget', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        self::assertCanModify($riddleId);

        $values = (new RiddleFinalAnswerWidgetPersistenceService())->loadEditorValues($riddleId);
        wp_send_json_success($values);
    }

    public static function save(): void {
        check_ajax_referer('riddle_final_answer_widget', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        self::assertCanModify($riddleId);

        $mode = (string) get_field('enigme_mode_validation', $riddleId);
        if ($mode !== 'automatique') {
            wp_send_json_error([
                'message' => __('La réponse automatique n’est disponible qu’en validation automatique.', 'chassesautresor-com'),
            ]);
        }

        $widget = isset($_POST['widget']) ? sanitize_key(wp_unslash((string) $_POST['widget'])) : 'text';
        if ($widget === 'click') {
            wp_send_json_error([
                'message' => __('Mode de réponse invalide.', 'chassesautresor-com'),
            ]);
        }

        $configuration = (new AnswerWidgetValidationService())->validate([
            'widget' => $widget,
            'accepted_answers' => isset($_POST['accepted_answers'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['accepted_answers']))
                : '',
            'case_sensitive' => isset($_POST['case_sensitive']) ? 1 : 0,
            'variants' => isset($_POST['variants'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['variants']))
                : '',
            'direction_sequences' => isset($_POST['direction_sequences'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['direction_sequences']))
                : '',
            'color_sequences' => isset($_POST['color_sequences'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['color_sequences']))
                : '',
            'number_sequences' => isset($_POST['number_sequences'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['number_sequences']))
                : '',
            'safe_dial_sequences' => isset($_POST['safe_dial_sequences'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['safe_dial_sequences']))
                : '',
            'piano_sequences' => isset($_POST['piano_sequences'])
                ? sanitize_textarea_field(wp_unslash((string) $_POST['piano_sequences']))
                : '',
            'gps_coordinates' => isset($_POST['gps_coordinates'])
                ? sanitize_text_field(wp_unslash((string) $_POST['gps_coordinates']))
                : '',
            'gps_tolerance' => isset($_POST['gps_tolerance'])
                ? sanitize_text_field(wp_unslash((string) $_POST['gps_tolerance']))
                : '',
        ]);
        if (is_wp_error($configuration)) {
            wp_send_json_error(['message' => $configuration->get_error_message()]);
        }

        $persisted = (new RiddleFinalAnswerWidgetPersistenceService())->persist($riddleId, $configuration);
        if (is_wp_error($persisted)) {
            wp_send_json_error(['message' => $persisted->get_error_message()]);
        }

        do_action('chassesautresor_riddle_completeness_refresh_requested', $riddleId);

        $values = (new RiddleFinalAnswerWidgetPersistenceService())->loadEditorValues($riddleId);
        wp_send_json_success([
            'widget' => $values['widget'],
            'summary' => self::summaryLabel($values['widget']),
            'complete' => (new RiddleFinalAnswerWidgetPersistenceService())->isComplete($riddleId),
        ]);
    }

    private static function assertCanModify(int $riddleId): void {
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            wp_send_json_error(['message' => __('Énigme introuvable.', 'chassesautresor-com')]);
        }
        if (!current_user_can('edit_post', $riddleId)) {
            wp_send_json_error(['message' => __('Action non autorisée.', 'chassesautresor-com')]);
        }
    }

    private static function summaryLabel(string $widget): string {
        foreach ((new \ChassesAuTresor\Core\Progress\AnswerWidgetEditorViewService())->widgetsForTarget('enigme') as $definition) {
            if (($definition['type'] ?? '') === $widget) {
                return (string) $definition['label'];
            }
        }

        return __('Réponse texte', 'chassesautresor-com');
    }
}
