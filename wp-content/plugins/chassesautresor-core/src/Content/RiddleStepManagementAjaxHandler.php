<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/** AJAX transport for creating, deleting and ordering intermediate steps. */
final class RiddleStepManagementAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_creer_etape_enigme', [self::class, 'create']);
        $addAction('wp_ajax_supprimer_etape_enigme', [self::class, 'delete']);
        $addAction('wp_ajax_reordonner_etapes_enigme', [self::class, 'reorder']);
    }

    public static function create(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        self::assertCanModify($riddleId);

        $title = isset($_POST['titre'])
            ? sanitize_text_field(wp_unslash((string) $_POST['titre']))
            : '';
        $position = count((new RiddleStepQueryService())->findOrderedIds($riddleId));
        $stepId = (new RiddleStepCreationService())->create(
            $riddleId,
            (int) get_current_user_id(),
            $title,
            $position
        );
        if (is_wp_error($stepId)) {
            wp_send_json_error('creation_impossible');
        }

        wp_send_json_success(['step_id' => (int) $stepId]);
    }

    public static function delete(): void {
        check_ajax_referer('riddle_step_management', 'nonce');
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        self::assertCanModify($riddleId);

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
}
