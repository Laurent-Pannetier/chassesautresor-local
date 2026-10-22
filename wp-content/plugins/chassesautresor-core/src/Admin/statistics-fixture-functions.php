<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\StatisticsFixtureAccess;
use ChassesAuTresor\Core\Admin\StatisticsFixtureGateway;
use ChassesAuTresor\Core\Site\SiteExperienceService;

if (!function_exists('cat_user_can_seed_statistics')) {
    function cat_user_can_seed_statistics(int $huntId = 0): bool
    {
        if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
            return false;
        }

        $settings = function_exists('cat_get_site_experience_settings')
            ? cat_get_site_experience_settings()
            : [];
        $demo = (new SiteExperienceService())->isDemoMode($settings);
        if ($huntId <= 0 && function_exists('cat_get_managed_hunt_id_for_user')) {
            $huntId = (int) cat_get_managed_hunt_id_for_user();
        }

        $administrator = function_exists('current_user_can') && current_user_can('administrator');
        $organizer = !$administrator
            && function_exists('utilisateur_est_organisateur_associe_a_chasse')
            && utilisateur_est_organisateur_associe_a_chasse((int) get_current_user_id(), $huntId);

        return (new StatisticsFixtureAccess())->canManage($demo, $administrator, $organizer, $huntId);
    }
}

if (!function_exists('cat_count_statistics_fixtures')) {
    function cat_count_statistics_fixtures(): int
    {
        return count(StatisticsFixtureGateway::findUserIds());
    }
}
