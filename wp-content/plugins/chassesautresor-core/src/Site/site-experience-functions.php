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

if (!function_exists('cat_is_demo_mode')) {
    function cat_is_demo_mode(): bool
    {
        return (new SiteExperienceService())->isDemoMode(cat_get_site_experience_settings());
    }
}

if (!function_exists('cat_is_platform_mode')) {
    function cat_is_platform_mode(): bool
    {
        return (new SiteExperienceService())->isPlatformMode(cat_get_site_experience_settings());
    }
}

if (!function_exists('cat_is_points_ui_enabled')) {
    function cat_is_points_ui_enabled(): bool
    {
        return (new SiteExperienceService())->isPointsUiEnabled(cat_get_site_experience_settings());
    }
}

if (!function_exists('cat_get_primary_hunt_id')) {
    function cat_get_primary_hunt_id(): int
    {
        $huntId = (new SiteExperienceService())->getPrimaryHuntId(cat_get_site_experience_settings());

        if ($huntId > 0 && get_post_type($huntId) === 'chasse') {
            return $huntId;
        }

        $postStatuses = cat_is_demo_mode()
            ? ['publish', 'pending', 'draft', 'private']
            : ['publish'];
        $query = [
            'post_type' => 'chasse',
            'post_status' => $postStatuses,
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
            'no_found_rows' => true,
        ];

        if (!cat_is_demo_mode()) {
            $query['meta_key'] = 'chasse_cache_statut_validation';
            $query['meta_value'] = 'valide';
        }

        $huntIds = get_posts($query);

        return isset($huntIds[0]) ? (int) $huntIds[0] : 0;
    }
}
