<?php
// simple bootstrap
declare(strict_types=1);

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/fixtures/');
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'ChassesAuTresor\\Core\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/'
        . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

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
