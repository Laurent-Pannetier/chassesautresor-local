<?php
// Functions related to customer account customizations.

defined('ABSPATH') || exit;

/**
 * Register custom My Account endpoints.
 */
function cta_register_account_endpoints(): void
{
    add_rewrite_endpoint('chasses', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('points', EP_ROOT | EP_PAGES);
}
add_action('init', 'cta_register_account_endpoints');

/**
 * Adjust My Account menu items for subscribers.
 *
 * @param array $items Existing menu items.
 * @return array
 */
function cta_account_menu_items(array $items): array
{
    if (!current_user_can('subscriber')) {
        return $items;
    }

    $new_items = [
        'dashboard'        => __('Accueil', 'chassesautresor-com'),
        'chasses'          => __('Mes chasses', 'chassesautresor-com'),
        'edit-account'     => __('Profil', 'chassesautresor-com'),
        'points'           => __('Points', 'chassesautresor-com'),
        'customer-logout'  => isset($items['customer-logout']) ? $items['customer-logout'] : __('Déconnexion', 'chassesautresor-com'),
    ];

    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'cta_account_menu_items');

/**
 * Render placeholder content for the "Mes chasses" endpoint.
 */
function cta_account_chasses_content(): void
{
    wc_get_template('myaccount/chasses.php');
}
add_action('woocommerce_account_chasses_endpoint', 'cta_account_chasses_content');

/**
 * Render content for the "Points" endpoint.
 */
function cta_account_points_content(): void
{
    wc_get_template('myaccount/points.php');
}
add_action('woocommerce_account_points_endpoint', 'cta_account_points_content');

/**
 * Display addresses forms below the profile form for subscribers.
 */
function cta_profile_addresses_block(): void
{
    if (!current_user_can('subscriber')) {
        return;
    }

    echo '<div class="cta-account-addresses">';
    echo '<h3>' . esc_html__('Adresses', 'chassesautresor-com') . '</h3>';
    woocommerce_account_edit_address('billing');
    woocommerce_account_edit_address('shipping');
    echo '</div>';
}
add_action('woocommerce_account_edit-account_endpoint', 'cta_profile_addresses_block', 20);
