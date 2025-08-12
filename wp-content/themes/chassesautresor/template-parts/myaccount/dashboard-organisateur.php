<?php
/**
 * Cards displayed on the dashboard for organizer roles.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$organizer_id    = $args['organizer_id'] ?? null;
$organizer_title = $args['organizer_title'] ?? '';
$orders_output   = $args['orders_output'] ?? '';
?>
<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <i class="fas fa-landmark"></i>
            <?php if ($organizer_id) : ?>
                <a href="<?php echo esc_url(get_permalink($organizer_id)); ?>"><?php echo esc_html($organizer_title); ?></a>
            <?php else : ?>
                <span><?php echo esc_html($organizer_title); ?></span>
            <?php endif; ?>
        </div>
        <div class="dashboard-card-content">
            <?php if ($organizer_id) : ?>
                <?php
                $query          = get_chasses_de_organisateur($organizer_id);
                $recent_chasses = $query && $query->have_posts() ? array_slice($query->posts, 0, 3) : array();
                if ($recent_chasses) {
                    echo '<ul>';
                    foreach ($recent_chasses as $post) {
                        $validation = get_field('chasse_cache_statut_validation', $post->ID);
                        $label      = ucfirst(str_replace('_', ' ', $validation));
                        echo '<li><a href="' . esc_url(get_permalink($post->ID)) . '">' . esc_html(get_the_title($post->ID)) . '</a> (' . esc_html($label) . ')</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>' . esc_html__('Aucune chasse trouvée.', 'chassesautresor') . '</p>';
                }
                ?>
            <?php else : ?>
                <p><?php esc_html_e('Aucun organisateur associé.', 'chassesautresor'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <div class="dashboard-card-header">
            <i class="fas fa-map"></i>
            <h4><?php esc_html_e('Chasses', 'chassesautresor'); ?></h4>
        </div>
        <div class="dashboard-card-content">
                <?php
                if ($organizer_id) {
                    $query_total    = get_chasses_de_organisateur($organizer_id);
                    $total_chasses  = $query_total->found_posts;
                    $recent_chasses = array_slice($query_total->posts, 0, 5);
                    if ($total_chasses) {
                        echo '<table class="stats-table"><thead><tr><th>' . esc_html__('Titre', 'chassesautresor') . '</th><th>' . esc_html__('Énigmes', 'chassesautresor') . '</th><th>' . esc_html__('Joueurs', 'chassesautresor') . '</th></tr></thead><tbody>';
                        foreach ($recent_chasses as $post) {
                            $cid     = $post->ID;
                            $enigmes = count(recuperer_enigmes_associees($cid));
                            echo '<tr>';
                            echo '<td><a href="' . esc_url(get_permalink($cid)) . '">' . esc_html(get_the_title($cid)) . '</a></td>';
                            echo '<td>' . intval($enigmes) . '</td>';
                            echo '<td>xx</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        if ($total_chasses > 5) {
                            echo '<p>' . sprintf(esc_html__('%d chasses au total', 'chassesautresor'), intval($total_chasses)) . '</p>';
                        }
                    } else {
                        $can_add = utilisateur_peut_ajouter_chasse($organizer_id);
                        if ($can_add) {
                            get_template_part('template-parts/chasse/chasse-partial-ajout-chasse', null, array(
                                'organisateur_id' => $organizer_id,
                                'has_chasses'     => false,
                            ));
                        } else {
                            echo '<p><a href="' . esc_url(get_permalink($organizer_id)) . '">' . esc_html__('Complétez votre profil organisateur', 'chassesautresor') . '</a></p>';
                        }
                    }
                } else {
                    echo '<p>' . esc_html__('Aucune chasse trouvée.', 'chassesautresor') . '</p>';
                }
                ?>
            </div>
        </div>

        <?php if (!empty($orders_output)) : ?>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-shopping-cart"></i>
                <h4><?php esc_html_e('Mes Commandes', 'chassesautresor'); ?></h4>
            </div>
            <div class="dashboard-card-content">
                <?php echo $orders_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

