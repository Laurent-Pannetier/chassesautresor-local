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

    <?php if (!empty($organisateurs_liste)) : ?>
        <h2 class="text-lg font-semibold mb-2"><?php esc_html_e('Organisateurs en attente', 'chassesautresor'); ?></h2>
        <span><?php echo count($organisateurs_liste); ?> <?php esc_html_e('résultat(s) trouvé(s)', 'chassesautresor'); ?></span>
        <table class="table-organisateurs mt-4">
            <thead>
                <tr>
                    <th><?php esc_html_e('Organisateur', 'chassesautresor'); ?></th>
                    <th><?php esc_html_e('Chasse', 'chassesautresor'); ?></th>
                    <th><?php esc_html_e('État', 'chassesautresor'); ?></th>
                    <th><?php esc_html_e('Utilisateur', 'chassesautresor'); ?></th>
                    <th><?php esc_html_e('Créé le', 'chassesautresor'); ?></th>
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
                        <td><?php echo esc_html($entry['validation']); ?></td>
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
    <?php else : ?>
        <p><?php esc_html_e('Aucun organisateur en attente.', 'chassesautresor'); ?></p>
    <?php endif; ?>

    <h2 class="text-lg font-semibold mt-8 mb-2"><?php esc_html_e('Demandes de paiement', 'chassesautresor'); ?></h2>
    <?php afficher_tableau_paiements_admin(); ?>
</section>
