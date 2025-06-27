<?php
defined('ABSPATH') || exit;
$organisateur_id    = $args['organisateur_id'] ?? null;
$organisateur_titre = $args['organisateur_titre'] ?? '';
$nombre_chasses     = $args['nombre_chasses'] ?? 0;
$conversion_autorisee = $args['conversion_autorisee'] ?? false;
$statut_conversion  = $args['statut_conversion'] ?? '';
$commandes_output   = $args['commandes_output'] ?? '';
$trophees_output    = $args['trophees_output'] ?? '';
?>
<div class="section-separator">
    <hr class="separator-line">
    <span class="separator-text">MON ESPACE</span>
    <hr class="separator-line">
</div>

<div class="dashboard-section">
    <h3 class="dashboard-section-title">Contenu</h3>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-landmark"></i>
                <h3>Organisateur</h3>
            </div>
            <div class="stats-content">
                <?php if ($organisateur_id) : ?>
                    <p><a href="<?php echo esc_url(get_permalink($organisateur_id)); ?>"><?php echo esc_html($organisateur_titre); ?></a></p>
                    <p>Nb de chasses : <?php echo intval($nombre_chasses); ?></p>
                    <p>Nb de joueurs : xx</p>
                    <p>Depuis le <?php echo esc_html(date_i18n('d/m/Y', strtotime(get_post_field('post_date', $organisateur_id)))); ?></p>
                <?php else : ?>
                    <p><?php echo esc_html($organisateur_titre); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3 class="dashboard-section-title">Chasse</h3>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-map"></i>
                <h3>Chasses</h3>
            </div>
            <div class="stats-content">
                <?php
                if ($organisateur_id) {
                    $query_total    = get_chasses_de_organisateur($organisateur_id);
                    $total_chasses  = $query_total->found_posts;
                    $recent_chasses = array_slice($query_total->posts, 0, 5);
                    if ($total_chasses) {
                        echo '<table class="stats-table"><thead><tr><th>Titre</th><th>Énigmes</th><th>Joueurs</th></tr></thead><tbody>';
                        foreach ($recent_chasses as $post) {
                            $cid        = $post->ID;
                            $nb_enigmes = count(recuperer_enigmes_associees($cid));
                            echo '<tr>';
                            echo '<td><a href="' . esc_url(get_permalink($cid)) . '">' . esc_html(get_the_title($cid)) . '</a></td>';
                            echo '<td>' . intval($nb_enigmes) . '</td>';
                            echo '<td>xx</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        if ($total_chasses > 5) {
                            echo '<p>' . intval($total_chasses) . ' chasses au total</p>';
                        }
                    } else {
                        $peut_ajouter = utilisateur_peut_ajouter_chasse($organisateur_id);
                        if ($peut_ajouter) {
                            get_template_part('template-parts/chasse/chasse-partial-ajout-chasse', null, [
                                'organisateur_id' => $organisateur_id,
                                'has_chasses'     => false,
                            ]);
                        } else {
                            echo '<p><a href="' . esc_url(get_permalink($organisateur_id)) . '">Complétez votre profil organisateur</a></p>';
                        }
                    }
                } else {
                    echo '<p>Aucune chasse trouvée.</p>';
                }
                ?>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-question-circle"></i>
                <h3>Enigmes</h3>
            </div>
            <div class="stats-content">
                <p>Placeholder 1</p>
                <p>Placeholder 2</p>
                <p>tentatives : xx</p>
            </div>
        </div>

        <?php if (!empty($commandes_output)) : ?>
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="dashboard-card">
                <div class="dashboard-card-header">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Mes Commandes</h3>
                </div>
                <div class="stats-content">
                    <?php echo $commandes_output; ?>
                </div>
            </a>
        <?php endif; ?>

        <?php if (!empty($trophees_output)) : ?>
            <a href="#" class="dashboard-card no-click">
                <div class="dashboard-card-header">
                    <i class="fas fa-trophy"></i>
                    <h3>Mes Trophées</h3>
                </div>
                <div class="trophees-content">
                    <?php echo $trophees_output; ?>
                </div>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-section">
    <h3 class="dashboard-section-title">Revenus</h3>
    <div class="dashboard-grid">
        <div class="dashboard-card points-card">
            <div class="dashboard-card-header">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <h3>Convertisseur</h3>
            </div>
            <div class="stats-content">
                <?php echo do_shortcode('[demande_paiement]'); ?>
                <?php if (!$conversion_autorisee) : ?>
                    <div class="overlay-taux">
                        <p class="message-bloque"><?php echo wp_kses_post($statut_conversion); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-wallet"></i>
                <h3>Mes revenus</h3>
            </div>
            <div class="stats-content">
                <p>À venir</p>
            </div>
        </div>
    </div>
</div>

