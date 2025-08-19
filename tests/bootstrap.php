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
