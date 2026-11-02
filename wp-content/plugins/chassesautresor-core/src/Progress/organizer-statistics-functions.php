<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\OrganizerStatisticsService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

if (!function_exists('organisateur_statistics_hunt_ids')) {
    /** @return int[] */
    function organisateur_statistics_hunt_ids(int $organizerId): array
    {
        if ($organizerId <= 0 || !function_exists('get_chasses_de_organisateur')) {
            return [];
        }

        $query = get_chasses_de_organisateur($organizerId);
        if (!is_object($query) || empty($query->posts)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', (array) $query->posts)));
    }
}

if (!function_exists('organisateur_statistics_excluded_user_ids')) {
    /** @return int[] */
    function organisateur_statistics_excluded_user_ids(int $organizerId): array
    {
        $excluded = function_exists('get_users') ? get_users(['role' => 'administrator', 'fields' => 'ids']) : [];
        if ($organizerId > 0 && function_exists('get_field')) {
            $excluded = array_merge($excluded, (array) get_field('utilisateurs_associes', $organizerId));
        }

        return array_values(array_unique(array_filter(array_map('intval', $excluded))));
    }
}

if (!function_exists('organisateur_compter_joueurs_uniques')) {
    function organisateur_compter_joueurs_uniques(int $organisateur_id): int
    {
        if (!isset($GLOBALS['wpdb'])) {
            return 0;
        }

        global $wpdb;

        return (new OrganizerStatisticsService())->countUniquePlayers(
            organisateur_statistics_hunt_ids($organisateur_id),
            organisateur_statistics_excluded_user_ids($organisateur_id),
            static fn (array $huntIds, array $excludedIds): int => CoreServiceFactory::huntEngagement($wpdb)
                ->countUniquePlayersForHunts($huntIds, $excludedIds)
        );
    }
}

if (!function_exists('organisateur_compter_points_collectes')) {
    function organisateur_compter_points_collectes(int $organisateur_id): int
    {
        if (!isset($GLOBALS['wpdb'])) {
            return 0;
        }

        global $wpdb;
        $statistics = CoreServiceFactory::huntStatistics($wpdb);

        return (new OrganizerStatisticsService())->sumCollectedPoints(
            organisateur_statistics_hunt_ids($organisateur_id),
            static function (int $huntId) use ($statistics): int {
                $riddleIds = function_exists('recuperer_ids_enigmes_pour_chasse')
                    ? recuperer_ids_enigmes_pour_chasse($huntId)
                    : [];

                return $statistics->sumCollectedPoints($riddleIds, null, null);
            }
        );
    }
}
