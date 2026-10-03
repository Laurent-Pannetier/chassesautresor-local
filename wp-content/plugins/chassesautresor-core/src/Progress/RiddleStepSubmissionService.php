<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use Throwable;

/** Persist one step interaction and its optional progression as a single transaction. */
final class RiddleStepSubmissionService {
    private $database;
    private RiddleAttemptService $attempts;
    private RiddleStepProgressService $progress;
    private ?RiddleRetryPolicyService $retryPolicy;

    public function __construct(
        $database,
        RiddleAttemptService $attempts,
        RiddleStepProgressService $progress,
        ?RiddleRetryPolicyService $retryPolicy = null
    ) {
        $this->database = $database;
        $this->attempts = $attempts;
        $this->progress = $progress;
        $this->retryPolicy = $retryPolicy;
    }

    /**
     * @param int[] $orderedStepIds
     * @return array{status:string,state:?array}
     */
    public function submit(
        int $userId,
        int $riddleId,
        int $stepId,
        array $orderedStepIds,
        string $interaction,
        string $result,
        string $submittedAt,
        string $uid,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        $state = $this->progress->getState($userId, $riddleId, $orderedStepIds);
        if ($state['current_step_id'] !== $stepId) {
            return ['status' => 'unavailable', 'state' => null];
        }

        $this->database->query('START TRANSACTION');
        try {
            $created = $this->attempts->createForStep(
                $uid,
                $userId,
                $riddleId,
                $stepId,
                $interaction,
                $result,
                $ipAddress,
                $userAgent
            );
            if (!$created) {
                $this->database->query('ROLLBACK');
                return ['status' => 'attempt_failed', 'state' => null];
            }

            if (
                $this->retryPolicy !== null
                && !$this->retryPolicy->renewAfterFailure($userId, $riddleId, $result, $uid)
            ) {
                $this->database->query('ROLLBACK');
                return ['status' => 'retry_failed', 'state' => null];
            }

            if ($result === 'bon') {
                $state = $this->progress->completeCurrentStep(
                    $userId,
                    $riddleId,
                    $stepId,
                    $orderedStepIds,
                    $submittedAt,
                    $uid
                );
                if ($state === null) {
                    $this->database->query('ROLLBACK');
                    return ['status' => 'unavailable', 'state' => null];
                }
            }

            $this->database->query('COMMIT');
            return ['status' => 'success', 'state' => $state];
        } catch (Throwable $exception) {
            $this->database->query('ROLLBACK');
            throw $exception;
        }
    }
}
