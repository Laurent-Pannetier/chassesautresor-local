<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/**
 * Notify the organizer when a player submits an answer for manual review.
 */
final class ManualAnswerNotificationService
{
    public function notify(int $userId, int $riddleId, string $answer, string $attemptUid): bool
    {
        return (new AttemptAlertService())->notifyManualSubmission($userId, $riddleId, $answer, $attemptUid);
    }
}
