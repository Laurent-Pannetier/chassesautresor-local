<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Clear legacy eligibility notices now that lifecycle lives on Mon compte. */
final class HuntValidationMessageHookHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('template_redirect', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (!is_user_logged_in() || !is_singular(['chasse', 'enigme'])) {
            return;
        }

        $postId = (int) get_queried_object_id();
        $huntId = self::resolveHuntId($postId);
        if ($huntId <= 0) {
            return;
        }

        global $wpdb;
        $userId = (int) get_current_user_id();
        $key = 'correction_info_chasse_' . $huntId;
        CoreServiceFactory::accountMessages($wpdb)->removePersistent($userId, $key);
    }

    private static function resolveHuntId(int $postId): int
    {
        if (get_post_type($postId) === 'chasse') {
            return $postId;
        }

        return (int) (new RelationshipService())->normalizeId(
            get_field('enigme_chasse_associee', $postId)
        );
    }
}
