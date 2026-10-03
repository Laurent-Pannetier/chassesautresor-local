<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Content\RiddleStepPostTypeRegistrar;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use Throwable;

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
        $riddlePostType = (string) get_post_type($riddleId);
        $stepPostType = (string) get_post_type($stepId);
        $accessFunctionAvailable = function_exists('utilisateur_peut_voir_enigme');
        $canViewRiddle = $userId > 0
            && $riddlePostType === 'enigme'
            && $accessFunctionAvailable
            && utilisateur_peut_voir_enigme($riddleId, $userId);
        $canModifyRiddle = $riddlePostType === 'enigme' && utilisateur_peut_modifier_post($riddleId);
        $configuration = (new AnswerWidgetConfigurationService())->forStep($stepId);
        $requestError = (new RiddleStepSubmissionRequestPolicy())->validate(
            $userId,
            $riddleId,
            $stepId,
            '',
            false,
            $riddlePostType,
            $accessFunctionAvailable,
            $canViewRiddle,
            $canModifyRiddle,
            $stepPostType,
            (int) get_field('etape_enigme_associee', $stepId),
            (string) ($configuration['type'] ?? ''),
            ['click']
        );
        if ($requestError === RiddleStepSubmissionRequestPolicy::ACCESS_DENIED) {
            wp_send_json_error(['message' => __('Accès refusé.', 'chassesautresor-com')]);
        }
        if ($requestError !== null) {
            wp_send_json_error(['message' => __('Étape invalide.', 'chassesautresor-com')]);
        }

        global $wpdb;
        $lock = new RiddleStepSubmissionLock($wpdb);
        if (!$lock->acquire($userId, $riddleId)) {
            wp_send_json_error(['message' => __('Traitement déjà en cours.', 'chassesautresor-com')]);
        }
        $orderedIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
        $uid = wp_generate_uuid4();
        try {
            $submission = (new RiddleStepSubmissionService(
                $wpdb,
                CoreServiceFactory::riddleAttempts($wpdb),
                CoreServiceFactory::riddleStepProgress($wpdb)
            ))->submit(
                $userId,
                $riddleId,
                $stepId,
                $orderedIds,
                'click',
                'bon',
                (string) current_time('mysql'),
                $uid,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
        } catch (Throwable $exception) {
            $lock->release($userId, $riddleId);
            wp_send_json_error(['message' => __('Impossible d’enregistrer la progression.', 'chassesautresor-com')]);
        }
        if ($submission['status'] !== 'success') {
            $lock->release($userId, $riddleId);
            $message = $submission['status'] === 'attempt_failed'
                ? __('Impossible d’enregistrer la progression.', 'chassesautresor-com')
                : __('Cette étape n’est pas disponible.', 'chassesautresor-com');
            wp_send_json_error(['message' => $message]);
        }
        $state = $submission['state'];
        CoreServiceFactory::huntProgress($wpdb)->advanceRiddleStatus(
            $userId,
            $riddleId,
            'en_cours',
            (string) current_time('mysql')
        );
        $lock->release($userId, $riddleId);
        wp_send_json_success([
            'final_answer_unlocked' => $state['final_answer_unlocked'],
            'current_step_id' => $state['current_step_id'],
            'response_html' => self::renderResponse($riddleId, $userId),
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
