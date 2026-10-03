<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use Throwable;

/** Evaluate a text response for the current intermediate step. */
final class RiddleStepTextAjaxHandler {
    private const SUPPORTED_WIDGETS = ['text', 'directions', 'colors', 'numbers', 'safe_dial'];

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
        $riddlePostType = (string) get_post_type($riddleId);
        $stepPostType = (string) get_post_type($stepId);
        $accessFunctionAvailable = function_exists('utilisateur_peut_voir_enigme');
        $canViewRiddle = $userId > 0
            && $riddlePostType === 'enigme'
            && $accessFunctionAvailable
            && utilisateur_peut_voir_enigme($riddleId, $userId);
        $canModifyRiddle = $riddlePostType === 'enigme' && utilisateur_peut_modifier_post($riddleId);
        $configuration = (new AnswerWidgetConfigurationService())->forStep($stepId);
        $requestPolicy = new RiddleStepSubmissionRequestPolicy();
        $requestError = $requestPolicy->validate(
            $userId,
            $riddleId,
            $stepId,
            $answer,
            true,
            $riddlePostType,
            $accessFunctionAvailable,
            $canViewRiddle,
            $canModifyRiddle,
            $stepPostType,
            (int) get_field('etape_enigme_associee', $stepId),
            (string) ($configuration['type'] ?? ''),
            self::SUPPORTED_WIDGETS
        );
        if ($requestError !== null) {
            wp_send_json_error(['message' => __('Réponse invalide.', 'chassesautresor-com')]);
        }

        global $wpdb;
        $attempts = CoreServiceFactory::riddleAttempts($wpdb);
        $lock = new RiddleSubmissionLock($wpdb);
        if (!$lock->acquire($userId, $riddleId)) {
            wp_send_json_error(['message' => __('Traitement déjà en cours.', 'chassesautresor-com')]);
        }
        $retryPolicy = CoreServiceFactory::riddleRetry($wpdb);
        $retryState = $retryPolicy->getState($userId, $riddleId);
        if ($retryState['blocked']) {
            $lock->release($userId, $riddleId);
            wp_send_json_error($retryState);
        }
        $submission = null;
        $evaluation = null;
        $unexpectedFailure = false;
        try {
            $orderedIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
            $evaluation = (new AnswerWidgetRegistry())->evaluate($answer, $configuration);
            $uid = wp_generate_uuid4();
            $submission = (new RiddleStepSubmissionService(
                $wpdb,
                $attempts,
                CoreServiceFactory::riddleStepProgress($wpdb),
                $retryPolicy
            ))->submit(
                $userId,
                $riddleId,
                $stepId,
                $orderedIds,
                $answer,
                $evaluation['resultat'],
                (string) current_time('mysql'),
                $uid,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            if ($submission['status'] === 'success') {
                CoreServiceFactory::huntProgress($wpdb)->advanceRiddleStatus(
                    $userId,
                    $riddleId,
                    'en_cours',
                    (string) current_time('mysql')
                );
            }
        } catch (Throwable $exception) {
            $unexpectedFailure = true;
        } finally {
            $lock->release($userId, $riddleId);
        }
        if ($unexpectedFailure || !is_array($submission) || !is_array($evaluation)) {
            wp_send_json_error(['message' => __('Impossible d’enregistrer la tentative.', 'chassesautresor-com')]);
        }
        if ($submission['status'] !== 'success') {
            $message = in_array($submission['status'], ['attempt_failed', 'retry_failed'], true)
                ? __('Impossible d’enregistrer la tentative.', 'chassesautresor-com')
                : __('Cette étape n’est plus disponible.', 'chassesautresor-com');
            wp_send_json_error(['message' => $message]);
        }
        $state = $submission['state'];

        wp_send_json_success([
            'resultat' => $evaluation['resultat'],
            'compteur' => $attempts->countFailuresTodayForUser($userId, $riddleId),
            'final_answer_unlocked' => $state['final_answer_unlocked'] ?? false,
            'current_step_id' => $state['current_step_id'] ?? null,
            'message' => $evaluation['message'],
            'response_html' => $evaluation['resultat'] === 'bon'
                ? self::renderResponse($riddleId, $userId)
                : '',
            'retry' => $retryPolicy->getState($userId, $riddleId),
        ]);
    }

    public static function supportsWidgetType(string $type): bool {
        return in_array($type, self::SUPPORTED_WIDGETS, true);
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
