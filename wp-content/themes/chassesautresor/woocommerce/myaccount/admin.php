<?php
defined( 'ABSPATH' ) || exit;

// Vérifier si l'utilisateur est admin
if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}
// Récupération des infos utilisateur
$current_user = wp_get_current_user();
$logout_url = wc_get_account_endpoint_url('customer-logout'); // Lien déconnexion
$current_user_id = get_current_user_id();
$commandes_output = afficher_commandes_utilisateur($current_user_id, 3);
$taux_conversion = get_taux_conversion_actuel();


?>

<?php get_template_part('template-parts/myaccount/navigation'); ?>
<?php get_template_part('template-parts/myaccount/important-messages'); ?>

    <!-- 📌 Contenu Principal -->
    <div class="dashboard-content">
        <h2>📌 Gestion des Paiements</h2>
        <?php afficher_tableau_paiements_admin(); ?>
        <div class="woocommerce-account-content">
            <?php if (is_woocommerce_account_page()) {
                woocommerce_account_content();
            } ?>
        </div>
    </div>

    <!-- 📌 Tableau de bord -->
    <?php get_template_part('template-parts/myaccount/dashboard-admin', null, ['taux_conversion' => $taux_conversion]); ?>
<?php
if (is_page('mon-compte') && current_user_can('administrator')) {
    echo '<script>console.log("✅ gestion-points.js chargé !");</script>';
}
?>

</div>
<?php get_template_part('template-parts/modals/modal-conversion-historique'); ?>

