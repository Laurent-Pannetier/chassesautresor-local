<?php
/**
 * Plugin Name: Site Password Protection
 * Description: Protects the site with a global password.
 */

function ca_site_password_protection(): void
{
    if (PHP_SAPI === 'cli' || (defined('WP_INSTALLING') && WP_INSTALLING)) {
        return;
    }

    if (is_user_logged_in()) {
        return;
    }

    $field    = 'ca_site_password';
    $password = 'rosebud';

    if (
        isset($_COOKIE[$field])
        && strcasecmp($_COOKIE[$field], $password) === 0
    ) {
        return;
    }

    if (
        isset($_POST[$field])
        && strcasecmp($_POST[$field], $password) === 0
    ) {
        setcookie($field, $password, time() + DAY_IN_SECONDS, '/');
        return;
    }

    status_header(401);
    header('Content-Type: text/html; charset=utf-8');

    echo '<form method="post">'
        . '<p><label>'
        . esc_html__('Password:', 'chassesautresor-com')
        . ' <input type="password" name="' . esc_attr($field) . '"></label></p>'
        . '<p><input type="submit" value="' . esc_attr__('Submit', 'chassesautresor-com') . '"></p>'
        . '</form>';

    exit;
}

add_action('init', 'ca_site_password_protection');
