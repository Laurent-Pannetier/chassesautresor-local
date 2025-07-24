<?php
/**
 * Bloc profil et navigation pour l'espace compte.
 */
defined('ABSPATH') || exit;
$current_user = wp_get_current_user();
?>
<div class="dashboard-profile-wrapper">
    <div class="dashboard-profile">
        <div class="profile-avatar-container">
            <div class="profile-avatar">
                <?php echo get_avatar($current_user->ID, 80); ?>
            </div>
            <label for="upload-avatar" class="upload-avatar-btn"><?php _e('Modifier', 'chassesautresor-com'); ?></label>
            <input type="file" id="upload-avatar" class="upload-avatar-input" accept="image/*" style="display:none;">
            <div class="message-upload-avatar">
                <div class="message-size-file-avatar"><?php _e('❗ Taille maximum : 2 Mo', 'chassesautresor-com'); ?></div>
                <div class="message-format-file-avatar"><?php _e('📌 Formats autorisés : JPG, PNG, GIF', 'chassesautresor-com'); ?></div>
            </div>
        </div>
        <div class="profile-info">
            <h2><?php echo esc_html($current_user->display_name); ?></h2>
            <p><?php echo esc_html($current_user->user_email); ?></p>
        </div>
    </div>
    <div class="user-points">
        <?php echo afficher_points_utilisateur_callback(); ?>
    </div>
</div>
<nav class="dashboard-nav">
    <ul>
        <li class="<?php echo is_account_page() && !is_wc_endpoint_url() ? 'active' : ''; ?>">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>">
                <i class="fas fa-home"></i> <span><?php _e('Accueil', 'chassesautresor-com'); ?></span>
            </a>
        </li>
        <li class="<?php echo is_wc_endpoint_url('orders') ? 'active' : ''; ?>">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                <i class="fas fa-box"></i> <span><?php _e('Commandes', 'chassesautresor-com'); ?></span>
            </a>
        </li>
        <li class="<?php echo is_wc_endpoint_url('edit-address') ? 'active' : ''; ?>">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>">
                <i class="fas fa-map-marker-alt"></i> <span><?php _e('Adresses', 'chassesautresor-com'); ?></span>
            </a>
        </li>
        <li class="<?php echo is_wc_endpoint_url('edit-account') ? 'active' : ''; ?>">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">
                <i class="fas fa-cog"></i> <span><?php _e('Paramètres', 'chassesautresor-com'); ?></span>
            </a>
        </li>
        <li>
            <a href="<?php echo esc_url(wc_logout_url()); ?>">
                <i class="fas fa-sign-out-alt"></i> <span><?php _e('Déconnexion', 'chassesautresor-com'); ?></span>
            </a>
        </li>
    </ul>
</nav>
