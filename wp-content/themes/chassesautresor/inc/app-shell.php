<?php
/**
 * Front-end shell: top bar, sidebar, day and night themes, placeholder pages.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

/**
 * Active visual theme. Night is the default.
 */
function cta_shell_theme(): string
{
    $theme = 'night';
    if (!isset($_COOKIE['cta_theme'])) {
        return $theme;
    }

    $raw = (string) $_COOKIE['cta_theme'];
    if (function_exists('wp_unslash')) {
        $raw = (string) wp_unslash($raw);
    }
    if (function_exists('sanitize_key')) {
        $raw = sanitize_key($raw);
    }

    return $raw === 'day' ? 'day' : 'night';
}

/**
 * Shared inline icons. The trophy markup is the one reused by the top bar.
 */
function cta_shell_icon(string $name): string
{
    $icons = [
        'home' => '<path fill="currentColor" d="M12 3.2 3 11h2v9h5.5v-5.5h3V20H19v-9h2L12 3.2z"/>',
        'progression' => '<path fill="currentColor" d="M4 13h3.2v7H4v-7zm6.4-4h3.2v11h-3.2V9zM16.8 5H20v15h-3.2V5z"/>',
        'rules' => '<path fill="currentColor" d="M7 2.5h7.2L19 7.3V21.5H7V2.5zm7 1.6V8h3.9'
            . 'L14 4.1zM9.2 12h6.2v1.6H9.2V12zm0 3.4h6.2v1.6H9.2v-1.6z"/>',
        'trophy' => '<path fill="currentColor" d="M8 3h8v2h3.1c.5 0 .9.4.9.9v2.2A4.1 4.1 0 0 1'
            . ' 16.2 12 5.8 5.8 0 0 1 13 16.6V19h3v2H8v-2h3v-2.4A5.8 5.8 0 0 1 7.8 12'
            . ' 4.1 4.1 0 0 1 4 8.1V5.9c0-.5.4-.9.9-.9H8V3zm0 4H6.1v1.1A2.1 2.1 0 0 0'
            . ' 8 10.1V7zm8 0v3.1a2.1 2.1 0 0 0 1.9-2V7H16z"/>',
        'profile' => '<path fill="currentColor" d="M12 12.2a4 4 0 1 0-4-4 4 4 0 0 0 4 4zm0 2c-3.4'
            . ' 0-8 1.7-8 5v1.3h16V19.2c0-3.3-4.6-5-8-5z"/>',
        'points' => '<path fill="currentColor" d="M12 2.5a9.5 9.5 0 1 0 9.5 9.5A9.5 9.5 0 0 0 12'
            . ' 2.5zm0 4.1 1.5 3.1 3.4.5-2.5 2.4.6 3.4L12 14.4 8.9 16l.6-3.4-2.4-2.4'
            . ' 3.4-.5L12 6.6z"/>',
        'notifications' => '<path fill="currentColor" d="M12 22a2.3 2.3 0 0 0 2.2-1.8H9.8A2.3 2.3'
            . ' 0 0 0 12 22zm6.5-6.2V11a6.5 6.5 0 0 0-5-6.3V3.5a1.5 1.5 0 0 0-3 0v1.2'
            . 'a6.5 6.5 0 0 0-5 6.3v4.8L4 18v1.2h16V18l-1.5-2.2z"/>',
        'logout' => '',
        'sun' => '<circle cx="12" cy="12" r="3.2" fill="currentColor"/>'
            . '<path fill="currentColor" d="M11.1 2h1.8v2.6h-1.8V2zm0 17.4h1.8V22h-1.8v-2.6z'
            . 'M2 11.1h2.6v1.8H2v-1.8zm17.4 0H22v1.8h-2.6v-1.8zM4.4 5.6l1.3-1.3'
            . ' 1.8 1.8-1.3 1.3-1.8-1.8zm12.1 12.1 1.3-1.3 1.8 1.8-1.3 1.3-1.8-1.8z'
            . 'M4.4 18.4l1.8-1.8 1.3 1.3-1.8 1.8-1.3-1.3zm12.1-12.1 1.8-1.8'
            . ' 1.3 1.3-1.8 1.8-1.3-1.3z"/>',
        'moon' => '<path fill="currentColor" d="M14.2 2.4a8.2 8.2 0 1 0 7.1 12.2A6.7 6.7 0 0 1 14.2 2.4z"/>',
        'stat' => '',
    ];

    if ($name === 'stat') {
        return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
            . '<circle cx="12" cy="12" r="7.25" stroke="currentColor" stroke-width="1.5"/>'
            . '</svg>';
    }

    if ($name === 'logout') {
        return '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false">'
            . '<path d="M10 7V5.6A1.6 1.6 0 0 0 8.4 4H5.6A1.6 1.6 0 0 0 4 5.6v12.8A1.6 1.6 0 0 0 5.6 20h2.8'
            . 'A1.6 1.6 0 0 0 10 18.4V17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>'
            . '<path d="M13.5 12H20m0 0-2.4-2.4M20 12l-2.4 2.4" stroke="currentColor" stroke-width="1.6"'
            . ' stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }

    if (!isset($icons[$name])) {
        return '';
    }

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
}

/**
 * @return array<string, string>
 */
function cta_shell_page_titles(): array
{
    return [
        'progression'    => __('Progression', 'chassesautresor-com'),
        'reglement'      => __('Règlement', 'chassesautresor-com'),
        'trophees'       => __('Trophées', 'chassesautresor-com'),
        'points'         => __('Points', 'chassesautresor-com'),
        'notifications'  => __('Notifications', 'chassesautresor-com'),
    ];
}

/**
 * Placeholder routes that are not backed by an existing published page.
 */
function cta_shell_is_virtual_request(): bool
{
    if (!function_exists('cta_shell_request_slug')) {
        return false;
    }

    $slug = cta_shell_request_slug();
    $titles = cta_shell_page_titles();
    if (!isset($titles[$slug])) {
        return false;
    }

    if (in_array($slug, ['progression', 'reglement'], true) && function_exists('cta_shell_published_page_url')) {
        if (cta_shell_published_page_url($slug) !== '') {
            return false;
        }
    }

    return true;
}

/**
 * Opens the shell around the theme markup.
 */
function cta_shell_open(): void
{
    if (!empty($GLOBALS['cta_shell_opened'])) {
        return;
    }

    $GLOBALS['cta_shell_opened'] = true;
    $theme = cta_shell_theme();

    echo '<div class="app-shell" data-theme="' . esc_attr($theme) . '">';
    get_template_part('template-parts/shell/topbar');
    echo '<div class="app-frame">';
    echo '<div class="app-nav-backdrop" data-app-nav-close hidden></div>';
    get_template_part('template-parts/shell/sidebar');
    echo '<div class="app-main">';
}

/**
 * Closes the shell opened by cta_shell_open().
 */
function cta_shell_close(): void
{
    if (empty($GLOBALS['cta_shell_opened'])) {
        return;
    }

    echo '</div></div></div>';
    $GLOBALS['cta_shell_opened'] = false;
}

/**
 * Hunt visual used as the home header. No overlay copy is added.
 */
function cta_shell_hunt_image_url(int $huntId): string
{
    if ($huntId <= 0) {
        return '';
    }

    $image = '';
    if (function_exists('get_field')) {
        $imageData = get_field('chasse_principale_image', $huntId);
        if (is_array($imageData)) {
            if (!empty($imageData['url'])) {
                $image = (string) $imageData['url'];
            } elseif (!empty($imageData['ID']) && function_exists('wp_get_attachment_image_url')) {
                $image = (string) wp_get_attachment_image_url((int) $imageData['ID'], 'full');
            }
        } elseif (!empty($imageData) && function_exists('wp_get_attachment_image_url')) {
            $image = (string) wp_get_attachment_image_url((int) $imageData, 'full');
        }
    }

    if ($image === '' && function_exists('get_the_post_thumbnail_url')) {
        $thumb = get_the_post_thumbnail_url($huntId, 'full');
        $image = is_string($thumb) ? $thumb : '';
    }

    if ($image !== '' && function_exists('imagify_get_webp_url')) {
        $webp = imagify_get_webp_url($image);
        if (is_string($webp) && $webp !== '') {
            $image = $webp;
        }
    }

    return $image;
}

/**
 * Renders the hunt image header. An empty frame is used when no image exists.
 */
function cta_shell_render_hunt_banner(): void
{
    $huntId = function_exists('cat_get_primary_hunt_id') ? (int) cat_get_primary_hunt_id() : 0;
    $url = cta_shell_hunt_image_url($huntId);
    $alt = '';
    if ($huntId > 0 && function_exists('get_the_title')) {
        $alt = (string) get_the_title($huntId);
    }

    echo '<section class="hunt-banner" aria-label="' . esc_attr__('Image de la chasse', 'chassesautresor-com') . '">';
    if ($url !== '') {
        echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '">';
    } else {
        echo '<p class="hunt-banner__empty">';
        echo esc_html__('Emplacement de l’image de la chasse', 'chassesautresor-com');
        echo '</p>';
    }
    echo '</section>';
}

/**
 * Team statistic slots. No indicator, value or calculation is invented.
 */
function cta_shell_render_team_stats(): void
{
    $section = __('Statistiques de l’équipe', 'chassesautresor-com');
    $label = __('Emplacement', 'chassesautresor-com');
    $caption = __('Non défini', 'chassesautresor-com');

    echo '<section class="team-stats" aria-label="' . esc_attr($section) . '">';
    echo '<h2 class="screen-reader-text">' . esc_html($section) . '</h2>';
    echo '<div class="team-stats__grid">';
    for ($index = 0; $index < 4; $index++) {
        echo '<article class="team-stat">';
        echo '<div class="team-stat__head">';
        echo '<p class="team-stat__label">' . esc_html($label) . '</p>';
        echo '<span class="team-stat__icon" aria-hidden="true">' . cta_shell_icon('stat') . '</span>';
        echo '</div>';
        echo '<p class="team-stat__value"><span aria-hidden="true">—</span>';
        echo '<span class="screen-reader-text">' . esc_html($caption) . '</span></p>';
        echo '<p class="team-stat__caption">' . esc_html($caption) . '</p>';
        echo '</article>';
    }
    echo '</div></section>';
}

/**
 * @param array<int, string> $classes Body classes.
 * @return array<int, string>
 */
function cta_shell_body_class(array $classes): array
{
    $classes[] = 'app-shell-active';

    return $classes;
}
add_filter('body_class', 'cta_shell_body_class');

/**
 * Applies the stored theme before the first paint.
 */
function cta_shell_theme_boot(): void
{
    echo "<script>(function(){try{var m=document.cookie.match(/(?:^|; )cta_theme=([^;]*)/);"
        . "var t=m&&decodeURIComponent(m[1])==='day'?'day':'night';"
        . "document.documentElement.setAttribute('data-theme',t);"
        . "}catch(e){document.documentElement.setAttribute('data-theme','night');}})();</script>\n";
}
add_action('wp_head', 'cta_shell_theme_boot', 0);

/**
 * Enqueues the shell behaviour.
 */
function cta_shell_enqueue(): void
{
    $path = get_stylesheet_directory() . '/assets/js/app-shell.js';
    if (!file_exists($path)) {
        return;
    }

    wp_enqueue_script(
        'cta-app-shell',
        get_stylesheet_directory_uri() . '/assets/js/app-shell.js',
        [],
        (string) filemtime($path),
        true
    );
}
add_action('wp_enqueue_scripts', 'cta_shell_enqueue');

/**
 * @param bool     $preempt  Whether to short-circuit 404 handling.
 * @param WP_Query $wp_query Current query.
 */
function cta_shell_pre_handle_404($preempt, $wp_query)
{
    if (!cta_shell_is_virtual_request()) {
        return $preempt;
    }

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
    }

    return true;
}
add_filter('pre_handle_404', 'cta_shell_pre_handle_404', 10, 2);

/**
 * @param string|false $redirect Canonical redirect target.
 * @return string|false
 */
function cta_shell_redirect_canonical($redirect)
{
    if (cta_shell_is_virtual_request()) {
        return false;
    }

    return $redirect;
}
add_filter('redirect_canonical', 'cta_shell_redirect_canonical');

/**
 * Serves the placeholder template for virtual shell pages.
 */
function cta_shell_template(string $template): string
{
    if (!cta_shell_is_virtual_request()) {
        return $template;
    }

    status_header(200);
    $GLOBALS['cta_shell_page'] = cta_shell_request_slug();
    $placeholder = get_stylesheet_directory() . '/templates/shell-placeholder.php';

    return file_exists($placeholder) ? $placeholder : $template;
}
add_filter('template_include', 'cta_shell_template');

/**
 * @param array<string, string> $parts Document title parts.
 * @return array<string, string>
 */
function cta_shell_document_title(array $parts): array
{
    if (!cta_shell_is_virtual_request() || !function_exists('cta_shell_request_slug')) {
        return $parts;
    }

    $titles = cta_shell_page_titles();
    $slug = cta_shell_request_slug();
    if (isset($titles[$slug])) {
        $parts['title'] = $titles[$slug];
    }

    return $parts;
}
add_filter('document_title_parts', 'cta_shell_document_title');

/**
 * Logout lives in the top bar, not in the profile menu.
 *
 * @param array<string, string> $items Account menu items.
 * @return array<string, string>
 */
function cta_shell_remove_account_logout(array $items): array
{
    unset($items['customer-logout']);

    return $items;
}
add_filter('woocommerce_account_menu_items', 'cta_shell_remove_account_logout');
