<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Site\SiteExperienceService;

if (!function_exists('cat_get_site_experience_settings')) {
    /**
     * @return array<string, mixed>
     */
    function cat_get_site_experience_settings(): array
    {
        $settings = get_option(SiteExperienceService::OPTION_NAME, []);

        return is_array($settings) ? $settings : [];
    }
}

if (!function_exists('cat_is_single_hunt_mode')) {
    function cat_is_single_hunt_mode(): bool
    {
        return (new SiteExperienceService())->isSingleHuntMode(cat_get_site_experience_settings());
    }
}

if (!function_exists('cat_are_organizer_applications_open')) {
    function cat_are_organizer_applications_open(): bool
    {
        return (new SiteExperienceService())->areOrganizerApplicationsOpen(cat_get_site_experience_settings());
    }
}

if (!function_exists('cat_get_primary_hunt_id')) {
    function cat_get_primary_hunt_id(): int
    {
        $huntId = (new SiteExperienceService())->getPrimaryHuntId(cat_get_site_experience_settings());

        if ($huntId > 0 && get_post_type($huntId) === 'chasse') {
            return $huntId;
        }

        $huntIds = get_posts([
            'post_type' => 'chasse',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => 'chasse_cache_statut_validation',
            'meta_value' => 'valide',
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);

        return isset($huntIds[0]) ? (int) $huntIds[0] : 0;
    }
}
