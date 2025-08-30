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

    $field          = 'ca_site_password';
    $password       = 'rosebud';
    $attempt_field  = 'ca_site_password_attempts';
    $max_attempts   = 10;
    $attempts       = isset($_COOKIE[$attempt_field]) ? (int) $_COOKIE[$attempt_field] : 0;
    $error_message  = '';

    if (
        isset($_COOKIE[$field])
        && strcasecmp($_COOKIE[$field], $password) === 0
    ) {
        return;
    }

    if (
        isset($_POST[$field])
        && is_string($_POST[$field])
    ) {
        if (strcasecmp($_POST[$field], $password) === 0) {
            setcookie($field, $password, time() + DAY_IN_SECONDS, '/');
            setcookie($attempt_field, '', time() - DAY_IN_SECONDS, '/');

            return;
        }

        $attempts++;
        setcookie($attempt_field, (string) $attempts, time() + DAY_IN_SECONDS, '/');
        $error_message = esc_html__('Incorrect password.', 'chassesautresor-com');
    }

    if ($attempts >= $max_attempts) {
        status_header(403);
        header('Content-Type: text/html; charset=utf-8');

        echo '<p>' . esc_html__('Too many attempts. Please try again later.', 'chassesautresor-com') . '</p>';
        exit;
    }

    status_header(401);
    header('Content-Type: text/html; charset=utf-8');

    $svg_url    = esc_url(home_url('/assets/svg/pirate-skull.svg'));
    $style_url  = esc_url(get_stylesheet_directory_uri() . '/dist/style.css');

    $styles = '.ca-site-password-wrapper{'
        . 'display:flex;'
        . 'min-height:100vh;'
        . 'align-items:center;'
        . 'justify-content:center;'
        . 'background:var(--color-background);'
        . 'color:var(--color-text-primary);'
        . 'font-family:var(--font-main);'
        . '}'
        . '.ca-site-password-form{'
        . 'text-align:center;'
        . 'display:flex;'
        . 'flex-direction:column;'
        . 'align-items:center;'
        . 'gap:var(--space-md);'
        . 'font-size:var(--space-lg);'
        . '}'
        . '.ca-site-password-form label{'
        . 'display:flex;'
        . 'flex-direction:column;'
        . 'gap:var(--space-xs);'
        . '}'
        . '.ca-site-password-form input[type="password"]{'
        . 'padding:var(--space-sm) var(--space-md);'
        . 'border:1px solid var(--color-grey-dark);'
        . 'border-radius:4px;'
        . 'background:var(--color-background);'
        . 'color:var(--color-text-primary);'
        . '}'
        . '.ca-site-password-form button{'
        . 'background:var(--color-background-button);'
        . 'color:var(--color-white);'
        . 'padding:var(--space-sm) var(--space-md);'
        . 'border:none;'
        . 'border-radius:4px;'
        . 'cursor:pointer;'
        . '}'
        . '.ca-site-password-form button:hover{background:var(--color-background-button-hover);}'
        . '.ca-site-password-error{color:var(--color-error);}'
        . '.ca-site-password-logo{width:150px;display:block;margin:0 auto var(--space-md);}';

    echo '<!doctype html><html><head><meta charset="utf-8"><title>'
        . esc_html__('Protected Site', 'chassesautresor-com')
        . '</title><link rel="stylesheet" href="'
        . $style_url
        . '"><style>'
        . $styles
        . '</style></head><body><div class="ca-site-password-wrapper">'
        . '<form class="ca-site-password-form" method="post">'
        . '<img class="ca-site-password-logo" src="'
        . $svg_url
        . '" alt="'
        . esc_attr__('Pirate skull', 'chassesautresor-com')
        . '"><label>'
        . esc_html__('Password:', 'chassesautresor-com')
        . ' <input type="password" name="'
        . esc_attr($field)
        . '"></label><button type="submit">'
        . esc_html__('Submit', 'chassesautresor-com')
        . '</button>'
        . ($error_message ? '<p class="ca-site-password-error">' . $error_message . '</p>' : '')
        . '</form></div></body></html>';

    exit;
}

add_action('init', 'ca_site_password_protection');
