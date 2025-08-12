<?php
/**
 * Dynamic content for the "Statistiques" section.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!current_user_can('administrator')) {
    wp_redirect(home_url('/mon-compte/'));
    exit;
}
?>
<section>
    <h1 class="mb-4 text-xl font-semibold"><?php esc_html_e('Statistiques', 'chassesautresor'); ?></h1>
    <p><?php esc_html_e('Contenu des statistiques utilisateur.', 'chassesautresor'); ?></p>
</section>
