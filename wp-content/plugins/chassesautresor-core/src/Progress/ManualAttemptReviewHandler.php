<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Support\CoreServiceFactory;

final class ManualAttemptReviewHandler
{
    public static function process(string $uid, string $result): bool
    {
        global $wpdb;

        return (new ManualAttemptReviewService(
            CoreServiceFactory::riddleAttempts($wpdb),
            CoreServiceFactory::huntProgress($wpdb),
            CoreServiceFactory::accountMessages($wpdb)
        ))->process(
            $uid,
            $result,
            (int) get_current_user_id(),
            current_user_can('manage_options')
        );
    }
}
