<?php
/**
 * Template Name: Annulation Validation Chasse
 */

defined('ABSPATH') || exit;

require_once get_theme_file_path('inc/chasse-functions.php');
require_once get_theme_file_path('inc/statut-functions.php');
require_once get_theme_file_path('inc/relations-functions.php');
require_once get_theme_file_path('inc/user-functions.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    wp_redirect(home_url());
    exit;
}

$chasse_id = isset($_POST['chasse_id']) ? (int) $_POST['chasse_id'] : 0;
$user_id   = get_current_user_id();

if (!$user_id || !$chasse_id || get_post_type($chasse_id) !== 'chasse') {
    wp_redirect(home_url());
    exit;
}

$nonce_action = 'annulation_validation_chasse_' . $chasse_id;
if (
    !isset($_POST['annulation_validation_chasse_nonce']) ||
    !wp_verify_nonce($_POST['annulation_validation_chasse_nonce'], $nonce_action)
) {
    wp_die( __( 'Vérification de sécurité échouée.', 'chassesautresor-com' ) );
}

if (
    !current_user_can('administrator') &&
    !utilisateur_est_organisateur_associe_a_chasse($user_id, $chasse_id)
) {
    wp_die( __( 'Conditions non remplies.', 'chassesautresor-com' ) );
}

if (empty($_POST['annuler_validation_chasse'])) {
    wp_redirect(home_url());
    exit;
}

forcer_statut_apres_acf($chasse_id, 'a_venir');
update_field('chasse_cache_statut', 'a_venir', $chasse_id);
update_field('chasse_cache_statut_validation', 'correction', $chasse_id);

wp_redirect(add_query_arg('validation_annulee', '1', get_permalink($chasse_id)));
exit;
