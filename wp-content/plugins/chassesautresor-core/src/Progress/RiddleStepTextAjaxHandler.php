<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Evaluate a text response for the current intermediate step. */
final class RiddleStepTextAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_soumettre_reponse_etape', [self::class, 'submit']);
    }

    public static function submit(): void {
        check_ajax_referer('riddle_step_answer', 'nonce');
        $userId = (int) get_current_user_id();
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $stepId = isset($_POST['etape_id']) ? (int) $_POST['etape_id'] : 0;
        $answer = isset($_POST['reponse'])
            ? sanitize_text_field(wp_unslash((string) $_POST['reponse']))
            : '';
        if (
            $userId <= 0
            || $answer === ''
            || get_post_type($riddleId) !== 'enigme'
            || !function_exists('utilisateur_peut_voir_enigme')
            || !utilisateur_peut_voir_enigme($riddleId, $userId)
            || utilisateur_peut_modifier_post($riddleId)
            || get_post_type($stepId) !== RiddleStepPostTypeRegistrar::POST_TYPE
            || (int) get_field('etape_enigme_associee', $stepId) !== $riddleId
            || (string) get_field('etape_reponse_widget', $stepId) !== 'text'
        ) {
            wp_send_json_error(['message' => __('Réponse invalide.', 'chassesautresor-com')]);
        }

        global $wpdb;
        $lock = new RiddleStepSubmissionLock($wpdb);
        if (!$lock->acquire($userId, $riddleId)) {
            wp_send_json_error(['message' => __('Traitement déjà en cours.', 'chassesautresor-com')]);
        }
        $progress = CoreServiceFactory::riddleStepProgress($wpdb);
        $attempts = CoreServiceFactory::riddleAttempts($wpdb);
        $orderedIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
        $state = $progress->getState($userId, $riddleId, $orderedIds);
        if ($state['current_step_id'] !== $stepId) {
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Cette étape n’est pas disponible.', 'chassesautresor-com')]);
        }

        CoreServiceFactory::huntProgress($wpdb)->advanceRiddleStatus(
            $userId,
            $riddleId,
            'en_cours',
            (string) current_time('mysql')
        );

        $max = (int) get_field('enigme_tentative_max', $riddleId);
        $failureCount = $attempts->countFailuresTodayForUser($userId, $riddleId);
        if ($max > 0 && $failureCount >= $max) {
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Limite quotidienne atteinte.', 'chassesautresor-com')]);
        }

        $rawAnswers = (string) get_field('etape_reponses_texte', $stepId);
        $acceptedAnswers = array_values(array_filter(array_map('trim', preg_split('/\R/', $rawAnswers) ?: [])));
        $variants = [];
        $rawVariants = (string) get_field('etape_reponses_variantes', $stepId);
        foreach (preg_split('/\R/', $rawVariants) ?: [] as $index => $line) {
            [$text, $message] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
            if ($text !== '' && $message !== '') {
                $variants[$index + 1] = [
                    'texte' => $text,
                    'message' => $message,
                    'casse' => (bool) get_field('etape_reponse_casse', $stepId),
                ];
            }
        }
        $evaluation = (new RiddleAnswerEvaluationService())->evaluate(
            $answer,
            $acceptedAnswers,
            (bool) get_field('etape_reponse_casse', $stepId),
            $variants
        );
        $uid = wp_generate_uuid4();
        $wpdb->query('START TRANSACTION');
        $created = $attempts->createForStep(
            $uid,
            $userId,
            $riddleId,
            $stepId,
            $answer,
            $evaluation['resultat'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        if (!$created) {
            $wpdb->query('ROLLBACK');
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Impossible d’enregistrer la tentative.', 'chassesautresor-com')]);
        }

        if ($evaluation['resultat'] === 'bon') {
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
                wp_send_json_error(['message' => __('Cette étape n’est plus disponible.', 'chassesautresor-com')]);
            }
        }

        $wpdb->query('COMMIT');
        $lock->release($userId, $riddleId);

        wp_send_json_success([
            'resultat' => $evaluation['resultat'],
            'compteur' => $attempts->countFailuresTodayForUser($userId, $riddleId),
            'final_answer_unlocked' => $state['final_answer_unlocked'] ?? false,
            'current_step_id' => $state['current_step_id'] ?? null,
            'message' => $evaluation['message'],
            'response_html' => $evaluation['resultat'] === 'bon'
                ? self::renderResponse($riddleId, $userId)
                : '',
        ]);
    }

    private static function renderResponse(int $riddleId, int $userId): string {
        ob_start();
        get_template_part(
            'template-parts/enigme/partials/enigme-partial-bloc-reponse',
            null,
            ['post_id' => $riddleId, 'user_id' => $userId]
        );

        return trim((string) ob_get_clean());
    }
}
