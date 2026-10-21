<?php

defined('ABSPATH') || exit;

/**
 * Audience of the single-hunt homepage.
 *
 * Priority: administrator, organizer linked to the hunt, engaged player, presentation.
 */
function cat_single_hunt_home_audience(): string
{
    static $audience = null;
    if (is_string($audience)) {
        return $audience;
    }

    $audience = 'presentation';
    if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
        return $audience;
    }
    if (!is_user_logged_in()) {
        return $audience;
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    if (in_array('administrator', $roles, true)) {
        $audience = 'admin';

        return $audience;
    }

    $userId = (int) $user->ID;
    $huntId = function_exists('cat_get_primary_hunt_id') ? (int) cat_get_primary_hunt_id() : 0;
    $managesHunt = $huntId > 0
        && function_exists('utilisateur_est_organisateur_associe_a_chasse')
        && utilisateur_est_organisateur_associe_a_chasse($userId, $huntId);

    $organizerRoles = ['organisateur', 'organisateur_creation'];
    if (defined('ROLE_ORGANISATEUR')) {
        $organizerRoles[] = ROLE_ORGANISATEUR;
    }
    if (defined('ROLE_ORGANISATEUR_CREATION')) {
        $organizerRoles[] = ROLE_ORGANISATEUR_CREATION;
    }

    if ($managesHunt && array_intersect($organizerRoles, $roles)) {
        $audience = 'organisateur';

        return $audience;
    }

    if (
        $huntId > 0
        && function_exists('utilisateur_est_engage_dans_chasse')
        && utilisateur_est_engage_dans_chasse($userId, $huntId)
    ) {
        $audience = 'joueur';

        return $audience;
    }

    return $audience;
}

/**
 * Whether the current front page renders a role cockpit instead of the presentation.
 */
function cat_single_hunt_front_is_cockpit(): bool
{
    if (!function_exists('is_front_page') || !is_front_page()) {
        return false;
    }

    return cat_single_hunt_home_audience() !== 'presentation';
}

/**
 * Single line shown over the hunt visual. Uses only the start date.
 */
function cat_primary_hunt_start_line(int $huntId): string
{
    $fallback = __('Date de départ bientôt annoncée', 'chassesautresor-com');
    if ($huntId <= 0) {
        return $fallback;
    }

    $raw = '';
    if (function_exists('preparer_infos_affichage_chasse')) {
        $info = preparer_infos_affichage_chasse($huntId, (int) get_current_user_id());
        $raw = (string) ($info['champs']['date_debut'] ?? '');
    } elseif (function_exists('get_field')) {
        $raw = (string) get_field('chasse_infos_date_debut', $huntId);
    }

    if ($raw === '') {
        return $fallback;
    }

    $formatted = function_exists('formater_date') ? formater_date($raw) : $raw;
    if ($formatted === '') {
        return $fallback;
    }

    return sprintf(
        /* translators: %s: formatted hunt start date. */
        __('Départ le %s', 'chassesautresor-com'),
        $formatted
    );
}

/**
 * Anchor used by enigmas links once the hunt page redirects home.
 */
function cat_single_hunt_enigmas_url(): string
{
    return home_url('/#enigmes');
}

/**
 * Enigmas loop and hunt solutions at the top of engaged, organizer and admin homes.
 */
function cat_render_single_hunt_home_enigmas(): void
{
    if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
        return;
    }

    $huntId = function_exists('cat_get_primary_hunt_id') ? (int) cat_get_primary_hunt_id() : 0;
    if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
        return;
    }

    $userId = (int) get_current_user_id();
    $infos = function_exists('preparer_infos_affichage_chasse')
        ? preparer_infos_affichage_chasse($huntId, $userId)
        : [];
    $isOrganizer = function_exists('utilisateur_est_organisateur_associe_a_chasse')
        && utilisateur_est_organisateur_associe_a_chasse($userId, $huntId);
    $status = (string) ($infos['statut'] ?? '');
    $showHelp = $isOrganizer
        && $status === 'revision'
        && function_exists('chasse_has_validatable_enigme')
        && !chasse_has_validatable_enigme($huntId);

    // Keeps the existing enigmas loop on its previous canvas, away from the shell palette.
    echo '<div class="home-enigmas-preserve">';
    echo '<section id="enigmes" class="home-enigmas" aria-labelledby="home-enigmas-title">';
    echo '<h2 id="home-enigmas-title" class="screen-reader-text">';
    echo esc_html__('Les énigmes', 'chassesautresor-com');
    echo '</h2>';
    echo '<div id="liste-enigmes" class="chasse-enigmes-liste">';
    get_template_part(
        'template-parts/enigme/chasse-partial-boucle-enigmes',
        null,
        [
            'chasse_id' => $huntId,
            'est_orga_associe' => $isOrganizer,
            'infos_chasse' => $infos,
            'show_help_icon' => $showHelp,
        ]
    );
    echo '</div>';
    if (function_exists('render_chasse_solutions')) {
        render_chasse_solutions($huntId, $userId);
    }
    echo '</section>';
    echo '</div>';
}

/**
 * Short demo line on cockpits. The presentation page keeps its own banner.
 */
function cat_render_cockpit_demo_notice(): void
{
    if (!function_exists('cat_is_demo_mode') || !cat_is_demo_mode()) {
        return;
    }

    echo '<p class="myaccount-demo-notice" role="status">';
    esc_html_e(
        'Mode démo : cette chasse est présentée avant sa publication officielle.',
        'chassesautresor-com'
    );
    echo '</p>';
}

/**
 * Public hunt pages return home. Edition stays on the chasse URL.
 */
function cat_redirect_public_single_hunt(): void
{
    if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
        return;
    }
    if (!is_singular('chasse')) {
        return;
    }

    $edition = isset($_GET['edition']) ? (string) wp_unslash($_GET['edition']) : '';
    if ($edition !== '') {
        return;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    wp_safe_redirect(home_url('/'));
    exit;
}
add_action('template_redirect', 'cat_redirect_public_single_hunt', 0);

/**
 * The former account-details endpoint now lives on the account root.
 */
function cat_redirect_edit_account_to_profile(): void
{
    if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
        return;
    }
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('edit-account')) {
        return;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }

    $target = function_exists('wc_get_account_endpoint_url')
        ? wc_get_account_endpoint_url('dashboard')
        : home_url('/mon-compte/');
    wp_safe_redirect($target);
    exit;
}
add_action('template_redirect', 'cat_redirect_edit_account_to_profile', 20);

/**
 * Send a default account landing to the role homepage.
 */
function cat_single_hunt_prefer_home_over_account(string $redirect): string
{
    if (!function_exists('cat_is_single_hunt_mode') || !cat_is_single_hunt_mode()) {
        return $redirect;
    }
    if (!function_exists('wc_get_page_permalink')) {
        return $redirect;
    }

    $account = (string) wc_get_page_permalink('myaccount');
    if ($account === '') {
        return $redirect;
    }

    $redirectPath = untrailingslashit((string) wp_parse_url($redirect, PHP_URL_PATH));
    $accountPath = untrailingslashit((string) wp_parse_url($account, PHP_URL_PATH));
    if ($redirectPath !== '' && $redirectPath === $accountPath) {
        return home_url('/');
    }

    return $redirect;
}

/**
 * @param string $redirect Destination proposed by WooCommerce.
 * @param mixed  $user     Logged-in user.
 */
function cat_single_hunt_woocommerce_login_redirect($redirect, $user): string
{
    unset($user);

    return cat_single_hunt_prefer_home_over_account(is_string($redirect) ? $redirect : '');
}
add_filter('woocommerce_login_redirect', 'cat_single_hunt_woocommerce_login_redirect', 30, 2);

/**
 * @param string $redirectTo Requested redirect.
 * @param string $requested  Original redirect_to.
 * @param mixed  $user       Logged-in user.
 */
function cat_single_hunt_wp_login_redirect($redirectTo, $requested, $user): string
{
    unset($requested, $user);
    $target = is_string($redirectTo) ? $redirectTo : '';

    return cat_single_hunt_prefer_home_over_account($target);
}
add_filter('login_redirect', 'cat_single_hunt_wp_login_redirect', 30, 3);

/**
 * Drop the marketing hero when the homepage is a cockpit.
 *
 * @param array<int, string> $classes Body classes.
 * @return array<int, string>
 */
function cat_single_hunt_cockpit_body_class(array $classes): array
{
    if (!cat_single_hunt_front_is_cockpit()) {
        return $classes;
    }

    $classes[] = 'cat-single-hunt-cockpit';
    $classes = array_values(array_diff($classes, ['has-hero']));

    return $classes;
}
add_filter('body_class', 'cat_single_hunt_cockpit_body_class', 20);
