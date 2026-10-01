<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Content\CompletionCacheManager;
use ChassesAuTresor\Core\Content\HuntValidationAccessResolver;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Maintain the account message advertising hunt validation when eligible. */
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

        (new CompletionCacheManager())->ensureFresh($huntId);

        global $wpdb;
        $userId = (int) get_current_user_id();
        $key = 'correction_info_chasse_' . $huntId;
        $messages = CoreServiceFactory::accountMessages($wpdb);

        if (!(new HuntValidationAccessResolver())->canRequest($huntId, $userId)) {
            $messages->removePersistent($userId, $key);
            return;
        }

        $existing = $messages->findPersistent($userId, $key);
        if (
            $existing !== null
            && isset($existing['chasse_scope'])
            && array_key_exists('include_enigmes', $existing)
        ) {
            return;
        }

        $message = sprintf(
            /* translators: %1$s and %2$s are opening and closing anchor tags. */
            __('Votre chasse est éligible à une %1$sdemande de validation%2$s.', 'chassesautresor-com'),
            '<a href="' . esc_url(get_permalink($huntId) . '#cta-validation-chasse') . '">',
            '</a>'
        );
        $messages->addPersistent($userId, $key, [
            'text' => $message,
            'type' => 'info',
            'dismissible' => false,
            'chasse_scope' => $huntId,
            'include_enigmes' => true,
        ]);
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
