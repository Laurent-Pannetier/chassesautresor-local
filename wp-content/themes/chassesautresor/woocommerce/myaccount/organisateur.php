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

<?php get_template_part('template-parts/myaccount/navigation'); ?>
<?php get_template_part('template-parts/myaccount/important-messages'); ?>

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

