<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

use ChassesAuTresor\Core\Content\SolutionAccessService;
use ChassesAuTresor\Core\Content\SolutionQueryService;
use ChassesAuTresor\Core\Relationships\RelationshipService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Resolve protected solution files and their access policy without theme adapters. */
final class ProtectedSolutionAssetService
{
    public function findSolution(int $targetId, string $targetType): ?object
    {
        $query = (new SolutionQueryService())->getActiveSolutionQueryArgs($targetId, $targetType);
        if ($query === []) {
            return null;
        }

        $solutions = get_posts($query);
        return isset($solutions[0]) && is_object($solutions[0]) ? $solutions[0] : null;
    }

    public function canView(int $targetId, string $targetType, int $userId): bool
    {
        if ($targetId <= 0 || $userId <= 0) {
            return false;
        }

        if ($targetType === 'chasse') {
            return $this->canViewHunt($targetId, $userId);
        }

        return $targetType === 'enigme' && $this->canViewRiddle($targetId, $userId);
    }

    public function findFilePath(object $solution): ?string
    {
        $solutionId = isset($solution->ID) ? (int) $solution->ID : 0;
        $attachmentId = $solutionId > 0 ? (int) get_field('solution_fichier', $solutionId, false) : 0;
        $path = $attachmentId > 0 ? get_attached_file($attachmentId) : false;

        return is_string($path) && $path !== '' ? $path : null;
    }

    private function canViewHunt(int $huntId, int $userId): bool
    {
        global $wpdb;

        $administrator = user_can($userId, 'manage_options');
        $organizer = !$administrator && $this->isAssociatedOrganizer($userId, $huntId);
        $engaged = !$administrator && !$organizer
            && CoreServiceFactory::huntEngagement($wpdb)->isEngaged($userId, $huntId);

        return (new SolutionAccessService())->canViewHuntSolution(true, $administrator, $organizer, $engaged);
    }

    private function canViewRiddle(int $riddleId, int $userId): bool
    {
        global $wpdb;

        $administrator = user_can($userId, 'manage_options');
        $relationships = new RelationshipService();
        $huntId = $relationships->normalizeId(get_field('enigme_chasse_associee', $riddleId));
        if ($administrator || $huntId === null) {
            return (new SolutionAccessService())->canViewRiddleSolution(
                $administrator,
                false,
                false,
                false,
                false,
                ''
            );
        }

        $huntFinished = get_field('chasse_cache_statut', $huntId) === 'termine';
        $riddleEngaged = $huntFinished
            && CoreServiceFactory::riddleEngagement($wpdb)->isEngaged($userId, $riddleId);
        $organizer = !$riddleEngaged && $this->isAssociatedOrganizer($userId, $huntId);
        $status = $riddleEngaged || $organizer
            ? ''
            : (string) (CoreServiceFactory::huntProgress($wpdb)->getRiddleStatus($userId, $riddleId) ?? '');

        return (new SolutionAccessService())->canViewRiddleSolution(
            false,
            true,
            $huntFinished,
            $riddleEngaged,
            $organizer,
            $status
        );
    }

    private function isAssociatedOrganizer(int $userId, int $huntId): bool
    {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        if ($organizerId === null) {
            return false;
        }

        $userIds = $relationships->normalizeIds((array) get_field('utilisateurs_associes', $organizerId));
        return in_array($userId, $userIds, true);
    }
}
