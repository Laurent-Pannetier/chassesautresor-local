<?php
/**
 * Dashboard content for organizer roles.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (function_exists('charger_script_conversion')) {
    charger_script_conversion(true);
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$organizer_id = get_organisateur_from_user($user_id);

if ($organizer_id) {
    $organizer_title = get_the_title($organizer_id);
} else {
    $organizer_title = __('Organisateur', 'chassesautresor');
}

$orders_output = afficher_commandes_utilisateur($user_id, 3);

ob_start();
afficher_tableau_paiements_organisateur($user_id, 'en_attente');
$pending_table = trim(ob_get_clean());

if (function_exists('woocommerce_account_content')) {
    woocommerce_account_content();
}

if (isset($_GET['paiement_envoye']) && $_GET['paiement_envoye'] === '1') {
    echo '<div class="notice notice-success">' .
        esc_html__('Votre demande de conversion a bien été envoyée.', 'chassesautresor-com') .
        '</div>';
}

if ($pending_table !== '') {
    echo '<div class="mb-6">';
    echo '<h2 class="text-lg font-semibold mb-2">' .
        esc_html__('Demande de conversion en attente', 'chassesautresor') .
        '</h2>';
    echo $pending_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

$args = array(
    'organizer_id'    => $organizer_id,
    'organizer_title' => $organizer_title,
    'orders_output'   => $orders_output,
);

get_template_part('template-parts/myaccount/dashboard-organisateur', null, $args);

get_template_part('template-parts/modals/modal-conversion');
