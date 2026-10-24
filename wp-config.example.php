<?php
/**
 * Example WordPress configuration.
 *
 * Copy this file to wp-config.php and replace every placeholder.
 * wp-config.php stays on the machine: it is listed in .gitignore and must not be committed.
 */

define('DB_NAME', 'database_name');
define('DB_USER', 'database_user');
define('DB_PASSWORD', 'database_password');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');

define('AUTH_KEY', 'replace-with-a-unique-phrase');
define('SECURE_AUTH_KEY', 'replace-with-a-unique-phrase');
define('LOGGED_IN_KEY', 'replace-with-a-unique-phrase');
define('NONCE_KEY', 'replace-with-a-unique-phrase');
define('AUTH_SALT', 'replace-with-a-unique-phrase');
define('SECURE_AUTH_SALT', 'replace-with-a-unique-phrase');
define('LOGGED_IN_SALT', 'replace-with-a-unique-phrase');
define('NONCE_SALT', 'replace-with-a-unique-phrase');
define('WP_CACHE_KEY_SALT', 'replace-with-a-unique-phrase');

$table_prefix = 'wp_';

if (!defined('WP_ENVIRONMENT_TYPE')) {
    define('WP_ENVIRONMENT_TYPE', 'production');
}

if (WP_ENVIRONMENT_TYPE === 'development') {
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', false);
    @ini_set('display_errors', '0');
    define('SCRIPT_DEBUG', true);
} else {
    define('WP_DEBUG', false);
    define('WP_DEBUG_LOG', false);
    define('WP_DEBUG_DISPLAY', false);
    @ini_set('display_errors', '0');
    define('SCRIPT_DEBUG', false);
}

define('WP_AUTO_UPDATE_CORE', 'minor');

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

require_once ABSPATH . 'wp-settings.php';
