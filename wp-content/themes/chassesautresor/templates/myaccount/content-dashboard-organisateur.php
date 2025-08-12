<?php
/**
 * Dashboard content for organizer roles.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$organizer_id = get_organisateur_from_user($user_id);

if ($organizer_id) {
    $organizer_title = get_the_title($organizer_id);
} else {
    $organizer_title = __('Organisateur', 'chassesautresor');
}

$chasse_count = 0;
if ($organizer_id) {
    $chasses_query = get_chasses_de_organisateur($organizer_id);
    $chasse_count  = $chasses_query->found_posts ?? 0;
}

$orders_output      = afficher_commandes_utilisateur($user_id, 3);
$conversion_status  = verifier_acces_conversion($user_id);
$conversion_allowed = ($conversion_status === true);

ob_start();
afficher_tableau_paiements_organisateur($user_id, 'en_attente');
$pending_table = trim(ob_get_clean());

if (function_exists('woocommerce_account_content')) {
    woocommerce_account_content();
}

if ($pending_table !== '') {
    echo '<div class="mb-6">';
    echo '<h2 class="text-lg font-semibold mb-2">' . esc_html__('Demande de conversion en attente', 'chassesautresor') . '</h2>';
    echo $pending_table; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo '</div>';
}

$args = array(
    'organizer_id'       => $organizer_id,
    'organizer_title'    => $organizer_title,
    'chasse_count'       => $chasse_count,
    'orders_output'      => $orders_output,
    'conversion_allowed' => $conversion_allowed,
    'conversion_status'  => $conversion_status,
);

get_template_part('template-parts/myaccount/dashboard-organisateur', null, $args);
