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


// Récupérer la liste des organisateurs en attente de validation
$organisateurs_liste = recuperer_organisateurs_pending();

?>
<div id="primary" class="content-area primary ">
    
    <main id="main" class="site-main">
        <header class="entry-header">
            <h1 class="entry-title" itemprop="headline">Organisateurs</h1>
        </header>
        <div class="dashboard-container">
            <?php get_template_part('template-parts/myaccount/navigation'); ?>
            <?php get_template_part('template-parts/myaccount/important-messages'); ?>
        
            <!-- 📌 Contenu Principal -->
            <div class="dashboard-content">
                <div class="woocommerce-account-content">
                    <?php 
                    
                    // Vérifier s'il y a des résultats avant d'afficher le tableau
if (!empty($organisateurs_liste)) :
?>
    <h3>Organisateurs en attente</h3>
    <span><?php echo count($organisateurs_liste); ?> résultat(s) trouvé(s)</span>
    <table class="table-organisateurs">
        <thead>
            <tr>
                <th>Organisateur</th>
                <th>Chasse</th>
                <th data-col="etat">État</th>
                <th>Utilisateur</th>
                <th>Créé le</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($organisateurs_liste as $entry) : ?>
                <tr class="<?php echo $entry['validation'] === 'en_attente' ? 'champ-attention' : ''; ?>">
                    <td class="<?php echo $entry['organisateur_complet'] ? 'carte-complete' : 'carte-incomplete'; ?>">
                        <a href="<?php echo esc_url($entry['organisateur_permalink']); ?>" target="_blank">
                            <?php echo esc_html($entry['organisateur_titre']); ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($entry['chasse_id']) : ?>
                            <?php
                                $titre_chasse = $entry['chasse_titre'];
                                if ($entry['nb_enigmes']) {
                                    $titre_chasse .= ' (' . intval($entry['nb_enigmes']) . ')';
                                }
                            ?>
                            <a class="<?php echo $entry['chasse_complet'] ? 'carte-complete' : 'carte-incomplete'; ?>" href="<?php echo esc_url($entry['chasse_permalink']); ?>" target="_blank">
                                <?php echo esc_html($titre_chasse); ?>
                            </a>
                        <?php else : ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td data-col="etat"><?php echo esc_html($entry['validation']); ?></td>
                    <td>
                        <?php if ($entry['user_id']) : ?>
                            <a href="<?php echo esc_url($entry['user_link']); ?>" target="_blank">
                                <?php echo esc_html($entry['user_name']); ?>
                            </a>
                        <?php else : ?>-
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html(date_i18n('d/m/y', strtotime($entry['date_creation']))); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; // Fin de la condition ?>
   
                    
                </div>
            </div>
        
            <?php get_template_part('template-parts/myaccount/dashboard-admin', null, ['taux_conversion' => get_taux_conversion_actuel()]); ?>
        
        </div>
    </main>
</div>
<?php
get_footer(); // ✅ Ajoute le pied de page du site
?>