<?php
defined('ABSPATH') || exit;
$taux_conversion = $args['taux_conversion'] ?? get_taux_conversion_actuel();
?>
<div class="dashboard-section">
    <h3 class="dashboard-section-title">Chasse</h3>
    <div class="dashboard-grid">
        <?php
        $creations = array_filter(
            recuperer_organisateurs_pending(),
            function ($entry) {
                return !empty($entry['chasse_id']) && $entry['validation'] === 'en_attente';
            }
        );
        if (!empty($creations)) : ?>
        <div class="dashboard-card creation-card">
            <div class="dashboard-card-header">
                <i class="fas fa-user-plus"></i>
                <h3>à valider</h3>
            </div>
            <div class="stats-content">
                <ul>
                    <?php foreach ($creations as $entry) : ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($entry['chasse_id'])); ?>">
                                <?php echo esc_html($entry['chasse_titre']); ?>
                            </a>
                            (<?php echo esc_html($entry['organisateur_titre']); ?>)
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $chasses_creation = new WP_Query([
            'post_type'      => 'chasse',
            'post_status'    => 'pending',
            'meta_query'     => [
                [
                    'key'     => 'chasse_cache_statut_validation',
                    'value'   => ['creation', 'correction'],
                    'compare' => 'IN'
                ]
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => 5,
            'fields'        => 'ids'
        ]);
        if ($chasses_creation->have_posts()) : ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-hammer"></i>
                <h3>en édition</h3>
            </div>
            <div class="stats-content">
                <ul>
                    <?php foreach ($chasses_creation->posts as $cid) : ?>
                        <li><a href="<?php echo esc_url(get_permalink($cid)); ?>"><?php echo esc_html(get_the_title($cid)); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-section">
    <h3 class="dashboard-section-title">Revenus</h3>
    <div class="dashboard-grid">
        <?php if (current_user_can('administrator')) : ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-coins"></i>
                <h3>Gestion Points</h3>
            </div>
            <div class="stats-content">
                <form method="POST" class="form-gestion-points">
                    <?php wp_nonce_field('gestion_points_action', 'gestion_points_nonce'); ?>
                    <div class="gestion-points-ligne">
                        <label for="utilisateur-points"></label>
                        <input type="text" id="utilisateur-points" name="utilisateur" placeholder="Rechercher un utilisateur..." required>
                        <label for="type-modification"></label>
                        <select id="type-modification" name="type_modification" required>
                            <option value="ajouter">➕</option>
                            <option value="retirer">➖</option>
                        </select>
                    </div>
                    <div class="gestion-points-ligne">
                        <label for="nombre-points"></label>
                        <input type="number" id="nombre-points" name="nombre_points" placeholder="nb de points" min="1" required>
                        <button type="submit" name="modifier_points" class="bouton-secondaire">✅</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-euro-sign"></i>
                <h3>Taux Conversion</h3>
            </div>
            <div class="stats-content">
                <p>1 000 points = <strong><?php echo esc_html($taux_conversion); ?> €</strong>
                    <span class="conversion-info">
                        <i class="fas fa-info-circle" id="open-taux-modal"></i>
                    </span>
                </p>
                <?php if (current_user_can('administrator')) : ?>
                    <div class="overlay-taux">
                        <button class="bouton-secondaire" id="modifier-taux">Modifier</button>
                    </div>
                    <form method="POST" class="form-taux-conversion" id="form-taux-conversion" style="display: none;">
                        <?php wp_nonce_field('modifier_taux_conversion_action', 'modifier_taux_conversion_nonce'); ?>
                        <label for="nouveau-taux">Définir un nouveau taux :</label>
                        <input type="number" name="nouveau_taux" id="nouveau-taux" step="0.01" min="0" value="<?php echo esc_attr($taux_conversion); ?>" required>
                        <button type="submit" name="enregistrer_taux" class="bouton-secondaire">Mettre à jour</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-chart-pie"></i>
                <h3>Points dépensés</h3>
            </div>
            <div class="stats-content">
                <p>Points totaux xx</p>
                <p>30 derniers jours xx</p>
            </div>
        </div>
    </div>
</div>

<div class="dashboard-section">
    <h3 class="dashboard-section-title">Développement</h3>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-tools"></i>
                <h3>ACF</h3>
            </div>
            <div class="stats-content">
                <button id="afficher-champs-acf" class="bouton-secondaire">Afficher les champs ACF</button>
                <div id="acf-fields-container" style="display:none;margin-top:10px;">
                    <textarea id="acf-fields-output" style="width:100%;height:300px;" readonly></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

