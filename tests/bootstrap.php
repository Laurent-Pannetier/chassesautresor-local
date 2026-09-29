<?php
// simple bootstrap
declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

if (!function_exists('add_action')) {
    function add_action(...$args): void {}
}

if (!function_exists('add_filter')) {
    function add_filter(...$args): void {}
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(...$args): void {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(...$args): void {}
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/OrganizerRoleService.php';
