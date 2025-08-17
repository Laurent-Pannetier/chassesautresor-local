<?php
defined('ABSPATH') || exit;
?>
<div class="dashboard-section">
    <h3 class="dashboard-section-title"><?php esc_html_e('Chasse', 'chassesautresor-com'); ?></h3>
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
                  <h3><?php esc_html_e('à valider', 'chassesautresor-com'); ?></h3>
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
                  <h3><?php esc_html_e('en édition', 'chassesautresor-com'); ?></h3>
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


