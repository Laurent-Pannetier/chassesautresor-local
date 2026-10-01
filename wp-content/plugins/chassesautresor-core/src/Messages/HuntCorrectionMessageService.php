<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Remove hunt correction messages for every organizer concerned by the hunt. */
final class HuntCorrectionMessageService
{
    public function clear(int $huntId): void
    {
        global $wpdb;

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $userIds = [(int) get_current_user_id()];

        if ($organizerId !== null) {
            $userIds = array_merge(
                $userIds,
                $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId))
            );
            $userIds[] = (int) get_post_field('post_author', $organizerId);
        }

        $messages = CoreServiceFactory::accountMessages($wpdb);
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $messages->removePersistent($userId, 'correction_chasse_' . $huntId);
            $messages->removePersistent($userId, 'correction_info_chasse_' . $huntId);
        }
    }
}
