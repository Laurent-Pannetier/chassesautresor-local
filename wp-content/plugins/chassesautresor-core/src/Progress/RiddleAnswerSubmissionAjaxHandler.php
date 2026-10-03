<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\RiddleStepQueryService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;
use RuntimeException;
use Throwable;

/**
 * Own the AJAX transport and orchestration for manual and automatic answers.
 */
class RiddleAnswerSubmissionAjaxHandler {
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_soumettre_reponse_manuelle', [self::class, 'submitManual']);
        $addAction('wp_ajax_nopriv_soumettre_reponse_manuelle', [self::class, 'submitManual']);
        $addAction('wp_ajax_soumettre_reponse_automatique', [self::class, 'submitAutomatic']);
        $addAction('wp_ajax_nopriv_soumettre_reponse_automatique', [self::class, 'submitAutomatic']);
    }

    public static function submitManual(): void
    {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $answer = isset($_POST['reponse_manuelle'])
            ? sanitize_textarea_field(wp_unslash((string) $_POST['reponse_manuelle']))
            : '';
        $nonce = isset($_POST['reponse_manuelle_nonce']) ? (string) $_POST['reponse_manuelle_nonce'] : '';
        $userId = (int) get_current_user_id();
        $error = self::validate($userId, $riddleId, $answer, $nonce, 'reponse_manuelle_nonce', false);
        if ($error !== null) {
            wp_send_json_error($error);
        }

        $lock = self::acquireLock($riddleId, $userId);
        $retryPolicy = CoreServiceFactory::riddleRetry(self::database());
        $retryState = $retryPolicy->getState($userId, $riddleId);
        if ($retryState['blocked']) {
            self::releaseLock($lock, $riddleId, $userId);
            wp_send_json_error($retryState);
        }
        try {
            global $wpdb;
            $points = CoreServiceFactory::points($wpdb);
            $attempts = CoreServiceFactory::riddleAttempts($wpdb);
            $cost = (int) get_field('enigme_tentative_cout_points', $riddleId);
            if ($cost > 0) {
                $reason = sprintf(
                    __("Tentative de réponse pour l'énigme #%d", 'chassesautresor-com'),
                    $riddleId
                );
                $points->deduct($userId, $cost, $reason, 'tentative', $riddleId);
            }

            $uid = self::createAttempt($attempts, $userId, $riddleId, $answer, 'attente', $cost);
            $attemptId = $attempts->getLastCreatedId();
            CoreServiceFactory::huntProgress($wpdb)->advanceRiddleStatus(
                $userId,
                $riddleId,
                'soumis',
                (string) current_time('mysql'),
                true
            );
            $link = '<a href="' . esc_url(get_permalink($riddleId)) . '">'
                . esc_html(get_the_title($riddleId)) . '</a>';
            CoreServiceFactory::accountMessages($wpdb)->addPersistent(
                $userId,
                'tentative_' . $uid,
                ['text' => $link, 'type' => 'info', 'dismissible' => false]
            );
            (new ManualAnswerNotificationService())->notify($userId, $riddleId, $answer, $uid);
            $timestamp = current_time('timestamp');
        } catch (Throwable $exception) {
            self::releaseLock($lock, $riddleId, $userId);
            cat_debug('Erreur tentative : ' . $exception->getMessage());
            wp_send_json_error('erreur_interne');
        }
        self::releaseLock($lock, $riddleId, $userId);

        wp_send_json_success([
            'uid' => $uid,
            'id' => $attemptId,
            'date' => wp_date('d/m/Y', $timestamp),
            'time' => wp_date('H:i', $timestamp),
            'points' => $points->getBalance($userId),
        ]);
    }

    public static function submitAutomatic(): void
    {
        $riddleId = isset($_POST['enigme_id']) ? (int) $_POST['enigme_id'] : 0;
        $answer = isset($_POST['reponse']) ? sanitize_text_field(wp_unslash((string) $_POST['reponse'])) : '';
        $nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';
        $userId = (int) get_current_user_id();
        $error = self::validate($userId, $riddleId, $answer, $nonce, 'reponse_auto_nonce', true);
        if ($error !== null) {
            wp_send_json_error($error);
        }

        $configuration = (new AnswerWidgetConfigurationService())->forRiddle($riddleId);
        $evaluation = (new AnswerWidgetRegistry())->evaluate($answer, $configuration);

        $lock = self::acquireLock($riddleId, $userId);
        $retryPolicy = CoreServiceFactory::riddleRetry(self::database());
        $retryState = $retryPolicy->getState($userId, $riddleId);
        if ($retryState['blocked']) {
            self::releaseLock($lock, $riddleId, $userId);
            wp_send_json_error($retryState);
        }
        try {
            self::database()->query('START TRANSACTION');
            $uid = self::processAttempt(
                $userId,
                $riddleId,
                $answer,
                $evaluation['resultat']
            );
            if (!$retryPolicy->renewAfterFailure($userId, $riddleId, $evaluation['resultat'], $uid)) {
                throw new RuntimeException('Unable to persist the retry delay.');
            }
            self::database()->query('COMMIT');
        } catch (Throwable $exception) {
            self::database()->query('ROLLBACK');
            self::releaseLock($lock, $riddleId, $userId);
            cat_debug('Erreur tentative : ' . $exception->getMessage());
            wp_send_json_error('erreur_interne');
        }
        self::releaseLock($lock, $riddleId, $userId);

        wp_send_json_success([
            'resultat' => $evaluation['resultat'],
            'message' => $evaluation['message'],
            'uid' => $uid,
            'points' => self::points()->getBalance($userId),
            'retry' => $retryPolicy->getState($userId, $riddleId),
        ]);
    }

    private static function validate(
        int $userId,
        int $riddleId,
        string $answer,
        string $nonce,
        string $nonceAction,
        bool $automatic
    ): ?string {
        $loggedIn = is_user_logged_in();
        $postType = $riddleId > 0 ? (string) get_post_type($riddleId) : '';
        $nonceValid = $nonce !== '' && wp_verify_nonce($nonce, $nonceAction) !== false;
        $state = $riddleId > 0 ? (string) get_field('enigme_cache_etat_systeme', $riddleId) : '';
        $status = $userId > 0 && $riddleId > 0
            ? (string) (CoreServiceFactory::huntProgress(self::database())->getRiddleStatus($userId, $riddleId) ?? '')
            : '';
        $finalAnswerUnlocked = true;
        if ($userId > 0 && $riddleId > 0) {
            $stepIds = (new RiddleStepQueryService())->findOrderedIds($riddleId);
            if ($stepIds !== []) {
                $finalAnswerUnlocked = CoreServiceFactory::riddleStepProgress(self::database())
                    ->getState($userId, $riddleId, $stepIds)['final_answer_unlocked'];
            }
        }

        return (new RiddleAnswerSubmissionPolicy())->validate(
            $loggedIn,
            $nonceValid,
            $riddleId,
            $postType,
            $answer,
            $state,
            $status,
            0,
            0,
            (int) get_field('enigme_tentative_cout_points', $riddleId),
            $userId > 0 ? self::points()->getBalance($userId) : 0,
            $automatic,
            $finalAnswerUnlocked
        );
    }

    private static function processAttempt(int $userId, int $riddleId, string $answer, string $result): string
    {
        $attempts = self::attempts();
        $plan = $attempts->buildProcessingPlan(
            $userId,
            $riddleId,
            $result,
            (int) get_field('enigme_tentative_cout_points', $riddleId),
            true,
            false
        );
        if ($plan === null) {
            return '';
        }

        $charge = (int) $plan['charge'];
        if ($charge > 0) {
            self::points()->deduct(
                $userId,
                $charge,
                sprintf(__("Tentative de réponse pour l'énigme #%d", 'chassesautresor-com'), $riddleId),
                'tentative',
                $riddleId
            );
        }

        $uid = self::createAttempt($attempts, $userId, $riddleId, $answer, $result, $charge);
        $outcome = $plan['outcome'];
        CoreServiceFactory::huntProgress(self::database())->advanceRiddleStatus(
            $userId,
            $riddleId,
            (string) $outcome['user_status'],
            (string) current_time('mysql')
        );
        if ($outcome['resolved']) {
            do_action('enigme_resolue', $userId, $riddleId);
        }

        return $uid;
    }

    private static function createAttempt(
        RiddleAttemptService $attempts,
        int $userId,
        int $riddleId,
        string $answer,
        string $result,
        int $spentPoints
    ): string {
        $uid = function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : uniqid('tent_', true);
        $created = $attempts->create(
            $uid,
            $userId,
            $riddleId,
            $answer,
            $result,
            $spentPoints,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        );
        if ($created) {
            do_action('enigme_tentative_created', $riddleId);
        } else {
            throw new RuntimeException('Unable to persist the attempt.');
        }

        return $uid;
    }

    private static function attempts(): RiddleAttemptService
    {
        return CoreServiceFactory::riddleAttempts(self::database());
    }

    private static function points(): \ChassesAuTresor\Core\Points\PointsService
    {
        return CoreServiceFactory::points(self::database());
    }

    private static function database(): object
    {
        global $wpdb;
        return $wpdb;
    }

    private static function acquireLock(int $riddleId, int $userId): RiddleSubmissionLock
    {
        $lock = new RiddleSubmissionLock(self::database());
        if (!$lock->acquire($userId, $riddleId)) {
            wp_send_json_error('doublon');
        }

        return $lock;
    }

    private static function releaseLock(RiddleSubmissionLock $lock, int $riddleId, int $userId): void
    {
        $lock->release($userId, $riddleId);
    }
}
