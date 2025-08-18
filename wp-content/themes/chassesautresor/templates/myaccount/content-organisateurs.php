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
    <?php if (empty($organisateurs_liste)) : ?>
        <p><?php esc_html_e('Aucun organisateur.', 'chassesautresor'); ?></p>
    <?php else : ?>
        <?php
        $etats = [];
        foreach ($organisateurs_liste as $entry) {
            if (!empty($entry['statut'])) {
                $etats[$entry['statut']] = true;
            }
        }
        ?>
        <div class="stats-header" style="display:flex;align-items:center;">
            <span><?php echo count($organisateurs_liste); ?> <?php esc_html_e('organisateur', 'chassesautresor'); ?></span>
            <div class="stats-filtres" style="margin-left:auto;">
                <label for="filtre-etat"><?php esc_html_e('Filtrer par état :', 'chassesautresor'); ?></label>
                <select id="filtre-etat">
                    <option value="tous"><?php esc_html_e('Tous', 'chassesautresor'); ?></option>
                    <?php foreach (array_keys($etats) as $etat) : ?>
                        <option value="<?php echo esc_attr($etat); ?>"><?php echo esc_html($etat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php afficher_tableau_organisateurs_pending($organisateurs_liste); ?>
    <?php endif; ?>
</section>
