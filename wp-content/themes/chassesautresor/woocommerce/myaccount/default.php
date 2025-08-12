<?php
defined( 'ABSPATH' ) || exit;

// Récupération des infos utilisateur
$current_user = wp_get_current_user();
$logout_url = wc_get_account_endpoint_url('customer-logout'); // Lien déconnexion
$points_balance = function_exists('get_user_points') ? get_user_points($current_user->ID) : 0;
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
            <li class="<?php echo is_account_page() && !is_wc_endpoint_url() ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">
                    <i class="fas fa-home"></i> <span><?php esc_html_e('Accueil', 'chassesautresor-com'); ?></span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('chasses') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('chasses')); ?>">
                    <i class="fas fa-map"></i> <span><?php esc_html_e('Mes chasses', 'chassesautresor-com'); ?></span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('edit-account') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">
                    <i class="fas fa-user"></i> <span><?php esc_html_e('Profil', 'chassesautresor-com'); ?></span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('points') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('points')); ?>">
                    <i class="fas fa-star"></i>
                    <span>
                        <?php esc_html_e('Points', 'chassesautresor-com'); ?>
                        <?php if ($points_balance) : ?>
                            <span class="points-badge"><?php echo esc_html($points_balance); ?></span>
                        <?php endif; ?>
                    </span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(wc_logout_url()); ?>">
                    <i class="fas fa-sign-out-alt"></i> <span><?php esc_html_e('Déconnexion', 'chassesautresor-com'); ?></span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- 📌 Contenu Principal -->
    <div class="dashboard-content">
        <div class="woocommerce-account-content">
            <?php if (is_woocommerce_account_page()) {
                woocommerce_account_content();
            } ?>
        </div>
    </div>
</div>

