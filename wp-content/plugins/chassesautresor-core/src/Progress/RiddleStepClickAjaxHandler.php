<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Complete the current click-confirmation step without consuming a failure. */
final class RiddleStepClickAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_confirmer_etape_enigme', [self::class, 'submit']);
    }

    public static function submit(): void {
        check_ajax_referer('riddle_step_click', 'nonce');
        $userId = (int) get_current_user_id();
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        if (
            $userId <= 0
            || get_post_type($riddleId) !== 'enigme'
            || !function_exists('utilisateur_peut_voir_enigme')
            || !utilisateur_peut_voir_enigme($riddleId, $userId)
            || utilisateur_peut_modifier_post($riddleId)
        ) {
            wp_send_json_error(['message' => __('Accès refusé.', 'chassesautresor-com')]);
        }
        if (
            get_post_type($stepId) !== RiddleStepPostTypeRegistrar::POST_TYPE
            || (int) get_field('etape_enigme_associee', $stepId) !== $riddleId
            || (string) (get_field('etape_reponse_widget', $stepId) ?: 'click') !== 'click'
        ) {
            wp_send_json_error(['message' => __('Étape invalide.', 'chassesautresor-com')]);
        }

        global $wpdb;
        $lock = new RiddleStepSubmissionLock($wpdb);
        if (!$lock->acquire($userId, $riddleId)) {
            wp_send_json_error(['message' => __('Traitement déjà en cours.', 'chassesautresor-com')]);
        }
        $progress = CoreServiceFactory::riddleStepProgress($wpdb);
        $orderedIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
        $uid = wp_generate_uuid4();
        $wpdb->query('START TRANSACTION');
        $state = $progress->completeCurrentStep(
            $userId,
            $riddleId,
            $stepId,
            $orderedIds,
            (string) current_time('mysql'),
            $uid
        );
        if ($state === null) {
            $wpdb->query('ROLLBACK');
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Cette étape n’est pas disponible.', 'chassesautresor-com')]);
        }

        CoreServiceFactory::huntProgress($wpdb)->advanceRiddleStatus(
            $userId,
            $riddleId,
            'en_cours',
            (string) current_time('mysql')
        );

        $created = CoreServiceFactory::riddleAttempts($wpdb)->createForStep(
            $uid,
            $userId,
            $riddleId,
            $stepId,
            'click',
            'bon',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        if (!$created) {
            $wpdb->query('ROLLBACK');
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Impossible d’enregistrer la progression.', 'chassesautresor-com')]);
        }
        $wpdb->query('COMMIT');
        $lock->release($userId, $riddleId);
        wp_send_json_success([
            'final_answer_unlocked' => $state['final_answer_unlocked'],
            'current_step_id' => $state['current_step_id'],
        ]);
    }
}
