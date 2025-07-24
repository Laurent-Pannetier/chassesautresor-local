<?php
defined( 'ABSPATH' ) || exit;
get_header(); // ✅ Ajoute l'en-tête du site
// Vérifier si l'utilisateur est admin
if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}

// Récupération des infos utilisateur
$current_user = wp_get_current_user();
$logout_url = wc_get_account_endpoint_url('customer-logout'); // Lien déconnexion

?>
<div id="primary" class="content-area primary ">
    
    <main id="main" class="site-main">
        <header class="entry-header">
            <h1 class="entry-title" itemprop="headline">Inscriptions</h1>
        </header>
        <div class="dashboard-container">
            <?php get_template_part('template-parts/myaccount/navigation'); ?>
            <?php get_template_part('template-parts/myaccount/important-messages'); ?>
        
            <!-- 📌 Contenu Principal -->
            <div class="dashboard-content">
                <div class="woocommerce-account-content">
                    <?php woocommerce_account_content(); ?> <!-- ✅ Affiche le contenu dynamique -->
                </div>
            </div>
        
            <?php get_template_part('template-parts/myaccount/dashboard-admin', null, ['taux_conversion' => get_taux_conversion_actuel()]); ?>
        
        </div>
    </main>
</div>
<?php
get_footer(); // ✅ Ajoute le pied de page du site
?>