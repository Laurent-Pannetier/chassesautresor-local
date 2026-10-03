<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Messages\AccountMessageService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use Throwable;

/**
 * Apply an organizer decision to a pending manual answer.
 */
final class ManualAttemptReviewService
{
    private RiddleAttemptService $attempts;
    private HuntProgressService $progress;
    private AccountMessageService $messages;
    private $database;
    private ?RiddleRetryPolicyService $retryPolicy;

    public function __construct(
        RiddleAttemptService $attempts,
        HuntProgressService $progress,
        AccountMessageService $messages,
        $database = null,
        ?RiddleRetryPolicyService $retryPolicy = null
    ) {
        $this->attempts = $attempts;
        $this->progress = $progress;
        $this->messages = $messages;
        $this->database = $database;
        $this->retryPolicy = $retryPolicy;
    }

    public function process(string $uid, string $result, int $reviewerId, bool $administrator): bool
    {
        $transactional = $this->database !== null && $this->retryPolicy !== null;
        if ($transactional) {
            $this->database->query('START TRANSACTION');
        }

        try {
            $attempt = $this->attempts->processManualAttempt(
                $uid,
                $result,
                $reviewerId,
                $administrator,
                function (int $riddleId): array {
                    return $this->getOrganizerUserIds($riddleId);
                }
            );
            if ($attempt === null) {
                if ($transactional) {
                    $this->database->query('ROLLBACK');
                }
                return false;
            }

            $userId = (int) $attempt->user_id;
            $riddleId = (int) $attempt->enigme_id;
            $plan = $this->attempts->buildProcessingPlan($userId, $riddleId, $result, 0, false, true);
            if (
                $plan === null
                || ($this->retryPolicy !== null
                    && !$this->retryPolicy->renewAfterFailure($userId, $riddleId, $result, $uid))
            ) {
                if ($transactional) {
                    $this->database->query('ROLLBACK');
                }
                return false;
            }

            $outcome = $plan['outcome'];
            $this->progress->advanceRiddleStatus(
                $userId,
                $riddleId,
                (string) $outcome['user_status'],
                (string) current_time('mysql')
            );
            if ($transactional) {
                $this->database->query('COMMIT');
            }
        } catch (Throwable $exception) {
            if ($transactional) {
                $this->database->query('ROLLBACK');
            }
            throw $exception;
        }

        if ($outcome['resolved']) {
            do_action('enigme_resolue', $userId, $riddleId);
        }
        if ($outcome['notify']) {
            (new AnswerResultNotificationService())->notify($userId, $riddleId, $result);
        }

        $organizerUsers = $this->getOrganizerUserIds($riddleId);
        $notification = $this->attempts->buildManualNotificationPlan($userId, $organizerUsers, $result);
        foreach ($notification['persistent_recipient_ids'] as $recipientId) {
            $this->messages->removePersistent((int) $recipientId, 'tentative_' . $uid);
        }

        $link = '<a href="' . esc_url(get_permalink($riddleId)) . '">'
            . esc_html(get_the_title($riddleId)) . '</a>';
        $text = $notification['approved']
            ? sprintf(__('Votre demande de résolution de l’énigme %s a été validée.', 'chassesautresor-com'), $link)
            : sprintf(__('Votre demande de résolution de l’énigme %s a été invalidée.', 'chassesautresor-com'), $link);
        $this->messages->addFlash($userId, ['text' => $text, 'type' => $notification['flash_type']]);

        return true;
    }

    /** @return int[] */
    private function getOrganizerUserIds(int $riddleId): array
    {
        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId, false));
        $organizerId = $huntId !== null
            ? $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId))
            : null;

        return $organizerId !== null
            ? $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId))
            : [];
    }
}
