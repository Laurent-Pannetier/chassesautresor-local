<?php
defined( 'ABSPATH' ) || exit;

// Récupération des infos utilisateur
$current_user = wp_get_current_user();
$logout_url = wc_get_account_endpoint_url('customer-logout'); // Lien déconnexion
$user_id = get_current_user_id();
$organisateur_id = get_organisateur_from_user($user_id);

// récupération CPT Organisateur & data
if ($organisateur_id) {
    $organisateur_titre = get_the_title($organisateur_id);
    $organisateur_logo = get_the_post_thumbnail_url($organisateur_id, 'medium'); // Récupérer l'image mise en avant
} else {
    $organisateur_titre = "Organisateur";
    $organisateur_logo = get_stylesheet_directory_uri() . "/assets/images/default-logo.png"; // Image par défaut si pas de logo
}

// compte le nb de chasses reliées au CPT Organisateur
$nombre_chasses = 0;
if ($organisateur_id) {
    $chasses = get_chasses_de_organisateur($organisateur_id);
    $nombre_chasses = $chasses->found_posts ?? 0;
    $liste_chasses_organisateur = generer_liste_chasses_hierarchique($organisateur_id);
} else {
    $liste_chasses_organisateur = '';
}

// récupération stats du joueur
if ($user_id) {
    //ob_start(); // Capture l'affichage des stats joueur
    //afficher_stats_utilisateur($user_id);
    //$stats_output = ob_get_clean(); // Récupère le contenu affiché et le stocke
}

// récupération des commandes
$commandes_output = afficher_commandes_utilisateur($user_id, 3);

// controle d'accès au Convertisseur
$statut_conversion = verifier_acces_conversion($user_id);
$conversion_autorisee = ($statut_conversion === true);

// tableau demande de conversion en attente
ob_start(); // Commencer la capture de sortie
afficher_tableau_paiements_organisateur($user_id, 'en_attente');
$tableau_contenu = ob_get_clean(); // Récupérer la sortie et l'effacer du buffer

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
                    <i class="fas fa-home"></i> <span>Accueil</span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('orders') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>">
                    <i class="fas fa-box"></i> <span>Commandes</span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('edit-address') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-address')); ?>">
                    <i class="fas fa-map-marker-alt"></i> <span>Adresses</span>
                </a>
            </li>
            <li class="<?php echo is_wc_endpoint_url('edit-account') ? 'active' : ''; ?>">
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('edit-account')); ?>">
                    <i class="fas fa-cog"></i> <span>Paramètres</span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(wc_logout_url()); ?>">
                    <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- 📌 Contenu Principal -->
    <div class="dashboard-content">
        <div class="woocommerce-account-content">
            <!-- Demande paiement en attente -->
            <?php if (!empty(trim($tableau_contenu))) : ?>
                <h3>Demande de conversion en attente</h3>
                <?php echo $tableau_contenu; // Afficher le tableau seulement s'il y a du contenu ?>
            <?php endif; ?>
            
            <!-- Conenu Woocommerce par défaut -->
            <?php if (is_woocommerce_account_page()) {
                woocommerce_account_content();
            } ?>
        </div>
    </div>

    <!-- 📌 Tableau de bord -->
    <?php get_template_part('template-parts/myaccount/dashboard-organisateur', null, [
        'organisateur_id'    => $organisateur_id,
        'organisateur_titre' => $organisateur_titre,
        'nombre_chasses'     => $nombre_chasses,
        'conversion_autorisee' => $conversion_autorisee,
        'statut_conversion'  => $statut_conversion,
        'commandes_output'   => $commandes_output,
    ]); ?>
</div>

