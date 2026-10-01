<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Resolve access to content attached to a hunt or riddle without theme policy filters. */
final class RelatedContentAccessResolver
{
    public function canPerform(string $action, string $targetType, int $targetId): bool
    {
        $authenticated = is_user_logged_in();
        $validTarget = $authenticated && get_post_type($targetId) === $targetType;
        if (!$validTarget || !in_array($action, ['create', 'edit', 'delete'], true)) {
            return false;
        }
        if (current_user_can('manage_options')) {
            return true;
        }

        $huntId = $targetType === 'chasse'
            ? $targetId
            : (int) (new RelationshipService())->normalizeId(get_field('enigme_chasse_associee', $targetId));
        $needsHuntPermission = $targetType === 'enigme' && in_array($action, ['create', 'edit'], true);

        return (new RelatedContentActionService())->canPerform(
            $authenticated,
            $action,
            $targetType,
            $validTarget,
            false,
            $validTarget && $this->isAssociatedOrganizer(get_current_user_id(), $huntId),
            $validTarget ? (string) get_post_status($targetId) : '',
            $targetType === 'chasse' && $validTarget
                ? (string) get_field('chasse_cache_statut_validation', $targetId)
                : '',
            $targetType === 'enigme' && $huntId > 0,
            $needsHuntPermission && $huntId > 0
                ? $this->canPerform($action, 'chasse', $huntId)
                : false
        );
    }

    private function isAssociatedOrganizer(int $userId, int $huntId): bool
    {
        if ($userId <= 0 || $huntId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $userIds = $organizerId === null
            ? []
            : $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
        return in_array($userId, $userIds, true);
    }
}
