<?php
defined('ABSPATH') || exit;
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

    </div>
</div>


