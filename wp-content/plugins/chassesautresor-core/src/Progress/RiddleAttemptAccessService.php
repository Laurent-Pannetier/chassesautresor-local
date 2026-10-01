<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\ContentPanelAccessService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Resolve access to riddle attempts without relying on theme callbacks. */
final class RiddleAttemptAccessService
{
    public function canModifyRiddle(int $userId, int $riddleId): bool
    {
        return $userId > 0
            && $riddleId > 0
            && (current_user_can('manage_options') || $this->isOrganizerForRiddle($userId, $riddleId));
    }

    public function canViewAttempt(object $attempt, int $userId): bool
    {
        return (new RiddleAttemptAccessPolicy())->canView(
            $userId,
            (int) ($attempt->user_id ?? 0),
            current_user_can('manage_options'),
            $this->isOrganizerForRiddle($userId, (int) ($attempt->enigme_id ?? 0))
        );
    }

    public function canViewRiddlePanel(int $userId, int $riddleId): bool
    {
        $isAdministrator = $userId > 0 && current_user_can('manage_options');
        $canModify = $this->canModifyRiddle($userId, $riddleId);

        return (new ContentPanelAccessService())->canView(
            $userId > 0,
            $isAdministrator,
            !$isAdministrator && $this->isOrganizerForRiddle($userId, $riddleId),
            $canModify,
            $riddleId > 0 ? (string) get_post_type($riddleId) : '',
            $riddleId > 0 ? (string) get_post_status($riddleId) : '',
            '',
            $riddleId > 0 ? (string) get_field('enigme_cache_etat_systeme', $riddleId) : ''
        );
    }

    private function isOrganizerForRiddle(int $userId, int $riddleId): bool
    {
        if ($userId <= 0 || $riddleId <= 0) {
            return false;
        }

        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        $organizerId = $huntId !== null
            ? $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId))
            : null;
        if ($organizerId === null) {
            return false;
        }

        $userIds = $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));

        return in_array($userId, $userIds, true);
    }
}
