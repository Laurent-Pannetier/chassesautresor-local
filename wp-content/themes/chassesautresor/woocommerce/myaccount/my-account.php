<?php
// Template principal pour les pages "Mon Compte".
// Ce fichier délègue l'affichage commun au layout unifié.

defined('ABSPATH') || exit;

if (isset($_GET['notice']) && $_GET['notice'] === 'profil_verification') {
    echo '<div class="woocommerce-message" role="alert">✉️ Un email de vérification vous a été envoyé. Veuillez cliquer sur le lien pour confirmer votre demande.</div>';
}

// S'assure que la variable globale est définie.
$GLOBALS['myaccount_content_template'] = $GLOBALS['myaccount_content_template'] ?? null;

include get_stylesheet_directory() . '/templates/myaccount/layout.php';
