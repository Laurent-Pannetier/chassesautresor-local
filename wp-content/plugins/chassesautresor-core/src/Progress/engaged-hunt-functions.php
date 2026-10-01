<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Return the query parameter used by engaged-hunt pagination. */
function ca_get_engaged_hunts_page_param(): string
{
    return 'engaged-page';
}

/** @return int[] */
function ca_get_user_engaged_hunt_ids(int $userId): array
{
    global $wpdb;

    $huntIds = CoreServiceFactory::huntEngagement($wpdb)->findHuntIdsForUser($userId);

    return array_values(array_filter(
        $huntIds,
        static fn (int $huntId): bool => chasse_est_visible_pour_utilisateur($huntId, $userId)
    ));
}

/**
 * @param int[] $huntIds
 * @return array{ids:int[],page:int,total_pages:int,total_items:int}
 */
function ca_prepare_engaged_hunts_pagination(array $huntIds, int $page, int $perPage): array
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
