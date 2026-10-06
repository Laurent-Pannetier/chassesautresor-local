<?php

defined('ABSPATH') || exit;

/**
 * Site footer helpers driven by the public experience mode.
 */

/**
 * Returns the active public experience mode for the footer.
 *
 * @return string platform|single_hunt|demo
 */
function cta_get_footer_experience_mode(): string
{
    if (function_exists('cat_is_demo_mode') && cat_is_demo_mode()) {
        return 'demo';
    }

    if (function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode()) {
        return 'single_hunt';
    }

    if (function_exists('cat_is_platform_mode') && cat_is_platform_mode()) {
        return 'platform';
    }

    // Fallback when the plugin helpers are unavailable: prefer the compact public chrome.
    return 'single_hunt';
}

/**
 * Resolves a published page URL by path, or returns an empty string.
 */
function cta_get_footer_page_url(string $path): string
{
    $path = trim($path, '/');
    if ($path === '') {
        return home_url('/');
    }

    $page = get_page_by_path($path);
    if ($page instanceof WP_Post && $page->post_status === 'publish') {
        $permalink = get_permalink($page);

        return $permalink ? (string) $permalink : '';
    }

    return '';
}

/**
 * Builds a footer link entry when the URL is usable.
 *
 * @return array{label:string,url:string,class?:string}|null
 */
function cta_build_footer_link(string $label, string $url, string $class = ''): ?array
{
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    $link = [
        'label' => $label,
        'url'   => $url,
    ];

    if ($class !== '') {
        $link['class'] = $class;
    }

    return $link;
}

/**
 * Returns the brand block copy for the current experience mode.
 *
 * @return array{title:string,tagline:string,home_url:string,badge:string}
 */
function cta_get_footer_brand_data(string $mode): array
{
    $homeUrl = home_url('/');
    $siteName = (string) get_bloginfo('name');

    if ($mode === 'platform') {
        return [
            'title'    => $siteName !== '' ? $siteName : __('Chasses au Trésor', 'chassesautresor-com'),
            'tagline'  => __(
                'La plateforme pour découvrir et créer des chasses au trésor en ligne.',
                'chassesautresor-com'
            ),
            'home_url' => $homeUrl,
            'badge'    => '',
        ];
    }

    $huntId = function_exists('cat_get_primary_hunt_id') ? cat_get_primary_hunt_id() : 0;
    $huntTitle = $huntId > 0 ? (string) get_the_title($huntId) : '';
    $title = $huntTitle !== '' ? $huntTitle : (
        $siteName !== '' ? $siteName : __('Chasses au Trésor', 'chassesautresor-com')
    );

    $tagline = $mode === 'demo'
        ? __('Aperçu d’une aventure en cours de préparation.', 'chassesautresor-com')
        : __('Une aventure à énigmes à vivre à votre rythme.', 'chassesautresor-com');

    return [
        'title'    => $title,
        'tagline'  => $tagline,
        'home_url' => $homeUrl,
        'badge'    => $mode === 'demo' ? __('Aperçu', 'chassesautresor-com') : '',
    ];
}

/**
 * Returns the navigation columns for the current experience mode.
 *
 * @return array<int, array{title:string,links:array<int, array{label:string,url:string,class?:string}>}>
 */
function cta_get_footer_nav_columns(string $mode): array
{
    $columns = $mode === 'platform'
        ? cta_get_footer_platform_columns()
        : cta_get_footer_single_hunt_columns($mode);

    /**
     * Filters the footer navigation columns for the active experience mode.
     *
     * @param array  $columns Footer columns.
     * @param string $mode    Active experience mode.
     */
    return apply_filters('cta_footer_nav_columns', $columns, $mode);
}

/**
 * Platform-mode footer columns.
 *
 * @return array<int, array{title:string,links:array<int, array{label:string,url:string,class?:string}>}>
 */
function cta_get_footer_platform_columns(): array
{
    $explore = array_values(array_filter([
        cta_build_footer_link(__('Accueil', 'chassesautresor-com'), home_url('/')),
        cta_build_footer_link(
            __('Chasses', 'chassesautresor-com'),
            home_url('/#liste-chasses')
        ),
        cta_build_footer_link(
            __('Boutique', 'chassesautresor-com'),
            cta_get_footer_page_url('boutique') ?: home_url('/boutique/')
        ),
    ]));

    $create = [];
    if (function_exists('cat_are_organizer_applications_open') && cat_are_organizer_applications_open()) {
        $organizerUrl = cta_get_footer_page_url('devenir-organisateur');
        if ($organizerUrl === '') {
            $organizerUrl = home_url('/devenir-organisateur/');
        }

        $create[] = cta_build_footer_link(
            __('Devenir organisateur', 'chassesautresor-com'),
            $organizerUrl,
            'cat-site-footer__link--accent'
        );
    }

    $create[] = cta_build_footer_link(
        is_user_logged_in()
            ? __('Mon compte', 'chassesautresor-com')
            : __('Se connecter', 'chassesautresor-com'),
        is_user_logged_in() ? home_url('/mon-compte/') : wp_login_url()
    );
    $create = array_values(array_filter($create));

    return array_values(array_filter([
        [
            'title' => __('Explorer', 'chassesautresor-com'),
            'links' => $explore,
        ],
        [
            'title' => __('Participer', 'chassesautresor-com'),
            'links' => $create,
        ],
    ], static fn(array $column): bool => !empty($column['links'])));
}

/**
 * Single-hunt / demo footer columns.
 *
 * @return array<int, array{title:string,links:array<int, array{label:string,url:string,class?:string}>}>
 */
function cta_get_footer_single_hunt_columns(string $mode): array
{
    $enigmesUrl = function_exists('cta_get_single_hunt_enigmes_nav_url')
        ? cta_get_single_hunt_enigmes_nav_url()
        : home_url('/');

    $adventure = array_values(array_filter([
        cta_build_footer_link(__('Accueil', 'chassesautresor-com'), home_url('/')),
        cta_build_footer_link(__('Énigmes', 'chassesautresor-com'), $enigmesUrl),
    ]));

    if ($mode !== 'demo' && function_exists('cat_is_points_ui_enabled') && cat_is_points_ui_enabled()) {
        $boutiqueUrl = cta_get_footer_page_url('boutique');
        if ($boutiqueUrl !== '') {
            $adventure[] = cta_build_footer_link(__('Boutique', 'chassesautresor-com'), $boutiqueUrl);
        }
    }

    $account = array_values(array_filter([
        cta_build_footer_link(
            is_user_logged_in()
                ? __('Mon compte', 'chassesautresor-com')
                : __('Se connecter', 'chassesautresor-com'),
            is_user_logged_in() ? home_url('/mon-compte/') : wp_login_url()
        ),
    ]));

    return array_values(array_filter([
        [
            'title' => __('Aventure', 'chassesautresor-com'),
            'links' => $adventure,
        ],
        [
            'title' => __('Compte', 'chassesautresor-com'),
            'links' => $account,
        ],
    ], static fn(array $column): bool => !empty($column['links'])));
}

/**
 * Shared informational / legal links.
 *
 * @return array<int, array{label:string,url:string,class?:string}>
 */
function cta_get_footer_info_links(): array
{
    $links = [];

    $contactUrl = cta_get_footer_page_url('contact');
    if ($contactUrl !== '') {
        $links[] = cta_build_footer_link(__('Contact', 'chassesautresor-com'), $contactUrl);
    }

    $mentionsUrl = cta_get_footer_page_url('mentions-legales');
    if ($mentionsUrl === '') {
        $mentionsUrl = home_url('/mentions-legales/');
    }
    $links[] = cta_build_footer_link(__('Mentions légales', 'chassesautresor-com'), $mentionsUrl);

    $privacyUrl = function_exists('get_privacy_policy_url') ? (string) get_privacy_policy_url() : '';
    if ($privacyUrl !== '') {
        $links[] = cta_build_footer_link(
            __('Confidentialité', 'chassesautresor-com'),
            $privacyUrl
        );
    }

    return array_values(array_filter($links));
}

/**
 * Returns the compact legal bar links.
 *
 * @return array<int, array{label:string,url:string,class?:string}>
 */
function cta_get_footer_legal_links(): array
{
    return cta_get_footer_info_links();
}

/**
 * Aggregates the data consumed by the footer template.
 *
 * @return array{
 *     mode:string,
 *     brand:array{title:string,tagline:string,home_url:string,badge:string},
 *     columns:array<int, array{title:string,links:array<int, array{label:string,url:string,class?:string}>}>,
 *     legal_links:array<int, array{label:string,url:string,class?:string}>,
 *     copyright:string
 * }
 */
function cta_get_site_footer_data(): array
{
    $mode = cta_get_footer_experience_mode();
    $year = (string) wp_date('Y');
    $siteName = (string) get_bloginfo('name');
    if ($siteName === '') {
        $siteName = __('Chasses au Trésor', 'chassesautresor-com');
    }

    $data = [
        'mode'        => $mode,
        'brand'       => cta_get_footer_brand_data($mode),
        'columns'     => cta_get_footer_nav_columns($mode),
        'legal_links' => cta_get_footer_legal_links(),
        'copyright'   => sprintf(
            /* translators: 1: current year, 2: site name. */
            __('© %1$s %2$s', 'chassesautresor-com'),
            $year,
            $siteName
        ),
    ];

    /**
     * Filters the complete site footer payload.
     *
     * @param array $data Footer data.
     */
    return apply_filters('cta_site_footer_data', $data);
}

/**
 * Renders the experience-aware site footer.
 */
function cta_render_site_footer(): void
{
    $footer = cta_get_site_footer_data();
    get_template_part('template-parts/footer/site-footer', null, ['footer' => $footer]);
}
