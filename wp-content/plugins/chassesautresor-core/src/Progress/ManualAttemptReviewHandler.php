<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class ManualAttemptReviewHandler
{
    public static function process(string $uid, string $result): bool
    {
        global $wpdb;

        $attempts = CoreServiceFactory::riddleAttempts($wpdb);
        $attempt = $attempts->findByUid($uid);
        if ($attempt === null) {
            return false;
        }
        $userId = (int) ($attempt->user_id ?? 0);
        $riddleId = (int) ($attempt->enigme_id ?? 0);
        $lock = new RiddleSubmissionLock($wpdb);
        if (!$lock->acquire($userId, $riddleId)) {
            return false;
        }

        try {
            return (new ManualAttemptReviewService(
                $attempts,
                CoreServiceFactory::huntProgress($wpdb),
                CoreServiceFactory::accountMessages($wpdb),
                $wpdb,
                CoreServiceFactory::riddleRetry($wpdb)
            ))->process(
                $uid,
                $result,
                (int) get_current_user_id(),
                current_user_can('manage_options')
            );
        } finally {
            $lock->release($userId, $riddleId);
        }
    }
}
