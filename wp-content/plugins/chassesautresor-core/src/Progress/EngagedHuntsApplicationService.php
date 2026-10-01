<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Content\HuntAccessService;
use ChassesAuTresor\Core\Relationships\RelationshipService;

/** Load and paginate hunts engaged by a user without theme callbacks. */
final class EngagedHuntsApplicationService
{
    private HuntEngagementService $engagements;

    public function __construct(HuntEngagementService $engagements)
    {
        $this->engagements = $engagements;
    }

    public function getVisibleHuntIds(int $userId): array
    {
        return array_values(array_filter(
            $this->engagements->findHuntIdsForUser($userId),
            fn (int $huntId): bool => $this->canView($huntId, $userId)
        ));
    }

    public function paginate(array $huntIds, int $page, int $perPage): array
    {
        $perPage = max(1, $perPage);
        $totalItems = count($huntIds);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = max(1, min($page, $totalPages));

        return [
            'ids' => array_slice($huntIds, ($page - 1) * $perPage, $perPage),
            'page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
        ];
    }

    private function canView(int $huntId, int $userId): bool
    {
        $publicationStatus = (string) get_post_status($huntId);
        $isPending = $publicationStatus === 'pending';
        $isAdministrator = $isPending && user_can($userId, 'manage_options');

        return (new HuntAccessService())->canView(
            $publicationStatus,
            (string) get_field('chasse_cache_statut_validation', $huntId),
            $isAdministrator,
            $isPending && !$isAdministrator && $this->isAssociatedOrganizer($huntId, $userId)
        );
    }

    private function isAssociatedOrganizer(int $huntId, int $userId): bool
    {
        $relationships = new RelationshipService();
        $organizerId = $relationships->normalizeId(get_field('chasse_cache_organisateur', $huntId));
        $users = $organizerId !== null ? (array) get_field('utilisateurs_associes', $organizerId) : [];

        return in_array($userId, $relationships->normalizeIds($users), true);
    }
}
