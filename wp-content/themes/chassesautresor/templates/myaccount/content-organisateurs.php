<?php
/**
 * Dynamic content for the "Organisateurs" section.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}

$organisateurs_liste = recuperer_organisateurs_pending();
?>
<section>
    <h1 class="mb-4 text-xl font-semibold"><?php esc_html_e('Organisateurs', 'chassesautresor'); ?></h1>
    <h2 class="text-lg font-semibold mb-2"><?php esc_html_e('Organisateurs en attente', 'chassesautresor'); ?></h2>
    <?php if (empty($organisateurs_liste)) : ?>
        <p><?php esc_html_e('Aucun organisateur en attente.', 'chassesautresor'); ?></p>
    <?php else : ?>
        <span><?php echo count($organisateurs_liste); ?> <?php esc_html_e('résultat(s) trouvé(s)', 'chassesautresor'); ?></span>
        <?php afficher_tableau_organisateurs_pending(); ?>
    <?php endif; ?>

    <h2 class="text-lg font-semibold mt-8 mb-2"><?php esc_html_e('Demandes de paiement', 'chassesautresor'); ?></h2>
    <?php afficher_tableau_paiements_admin(); ?>
</section>
