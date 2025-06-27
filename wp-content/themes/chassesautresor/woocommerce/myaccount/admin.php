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

<!-- 📌 Conteneur Profil + Points -->
<div class="dashboard-container">
   <!-- 📌 Conteneur Profil + Points -->
    <div class="dashboard-profile-wrapper">
        <!-- 📌 Profil Utilisateur -->
        <div class="dashboard-profile">
            <div class="profile-avatar-container">
                <div class="profile-avatar">
                    <?php echo get_avatar($current_user->ID, 80); ?>
                </div>
            
                <!-- 📌 Bouton pour ouvrir le téléversement -->
                <label for="upload-avatar" class="upload-avatar-btn">Modifier</label>
                <input type="file" id="upload-avatar" class="upload-avatar-input" accept="image/*" style="display: none;">
                
                <!-- 📌 Conteneur des messages d’upload -->
                <div class="message-upload-avatar">
                    <div class="message-size-file-avatar">❗ Taille maximum : 2 Mo</div>
                    <div class="message-format-file-avatar">📌 Formats autorisés : JPG, PNG, GIF</div>
                </div>
            </div>
            <div class="profile-info">
                <h2><?php echo esc_html($current_user->display_name); ?></h2>
                <p><?php echo esc_html($current_user->user_email); ?></p>
            </div>
        </div>
    </div>
        
    <!-- 📌 Barre de navigation Desktop -->
   <nav class="dashboard-nav">
        <ul>
            <li>
            <a class=" active" href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>"><i class="fas fa-home"></i> <span>Tableau de bord</span></a>
            </li>
    
            <li>
                <a href="<?php echo esc_url('/mon-compte/organisateurs/'); ?>"><i class="fas fa-users"></i> <span>Organisateurs</span></a>
            </li>
    
            <li>
                <a href="<?php echo esc_url('/mon-compte/statistiques/'); ?>"><i class="fas fa-chart-line"></i> <span>Statistiques</span></a>
            </li>
    
            <li>
                <a href="<?php echo esc_url('/mon-compte/outils/'); ?>"><i class="fas fa-wrench"></i> <span>Outils</span></a>
            </li>
        
            <!-- Mon Compte (inchangé) -->
            <li class="menu-deroulant">
                <a href="#" id="menu-account-toggle"><i class="fas fa-user-circle"></i> <span>Mon compte</span> <span class="dropdown-indicator">▼</span></a>
                <ul class="submenu">
                    <li><a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">Mes Commandes</a></li>
                    <li><a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>">Mes Adresses</a></li>
                    <li><a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">Paramètres</a></li>
                    <li><a href="<?php echo esc_url(wc_logout_url()); ?>">Déconnexion</a></li>
                </ul>
            </li>
        </ul>
    </nav>

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

