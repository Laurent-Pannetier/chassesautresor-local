<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Teams\TeamActor;
use ChassesAuTresor\Core\Teams\TeamInviteCode;
use ChassesAuTresor\Core\Teams\TeamRepository;
use ChassesAuTresor\Core\Teams\TeamService;
use ChassesAuTresor\Core\Teams\TeamSettings;

if (!function_exists('cat_team_service')) {
    function cat_team_service(): TeamService
    {
        global $wpdb;

        return new TeamService(new TeamRepository($wpdb), new TeamSettings(), new TeamInviteCode());
    }
}

if (!function_exists('cat_team_actor')) {
    function cat_team_actor(): TeamActor
    {
        $user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
        $id = $user instanceof WP_User ? (int) $user->ID : 0;
        $roles = $user instanceof WP_User ? array_map('strval', (array) $user->roles) : [];
        $manager = in_array('administrator', $roles, true) || in_array('organisateur', $roles, true);

        return new TeamActor($id, $manager);
    }
}
