<?php
// Template principal pour les pages "Mon Compte".
// Ce fichier délègue l'affichage commun au layout unifié.

defined('ABSPATH') || exit;

if (isset($_GET['notice']) && $_GET['notice'] === 'profil_verification') {
    echo '<div class="woocommerce-message" role="alert">✉️ Un email de vérification vous a été envoyé. Veuillez cliquer sur le lien pour confirmer votre demande.</div>';
}

// S'assure que la variable globale est définie.
$GLOBALS['myaccount_content_template'] = $GLOBALS['myaccount_content_template'] ?? null;

$current_user = wp_get_current_user();
if ($current_user->ID && !is_wc_endpoint_url()) {
    $roles = (array) $current_user->roles;
    if (in_array('administrator', $roles, true)) {
        $GLOBALS['myaccount_content_template'] = get_stylesheet_directory() . '/templates/myaccount/content-dashboard-admin.php';
    } elseif (array_intersect(['organisateur', 'organisateur_creation'], $roles)) {
        $GLOBALS['myaccount_content_template'] = get_stylesheet_directory() . '/templates/myaccount/content-dashboard-organisateur.php';
    }
}

include get_stylesheet_directory() . '/templates/myaccount/layout.php';
