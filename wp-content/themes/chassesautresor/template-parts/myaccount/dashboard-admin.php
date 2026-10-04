<?php
/**
 * Administrator account home shell.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$hunt_id = function_exists('cat_get_managed_hunt_id_for_user')
    ? cat_get_managed_hunt_id_for_user()
    : 0;
$can_reset = function_exists('cat_is_demo_mode')
    ? cat_is_demo_mode()
    : current_user_can('administrator');

myaccount_render_dashboard_section(
    __('Pilotage', 'chassesautresor-com'),
    __('Accès rapide et cycle de vie de la chasse.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    if (function_exists('cat_render_hunt_quick_edit_card')) {
        echo cat_render_hunt_quick_edit_card($hunt_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if (function_exists('cat_render_hunt_lifecycle_switch')) {
        echo cat_render_hunt_lifecycle_switch($hunt_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
    if ($can_reset || current_user_can('administrator')) :
        ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <i class="fas fa-undo" aria-hidden="true"></i>
                <h3><?php esc_html_e('Reset stats', 'chassesautresor-com'); ?></h3>
            </div>
            <div class="dashboard-card-content">
                <button type="button" class="btn-danger" data-reset-stats>
                    <?php esc_html_e('Effacer', 'chassesautresor-com'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
myaccount_render_dashboard_section(
    __('Statistiques', 'chassesautresor-com'),
    __('Indicateurs par énigme pour suivre la participation.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Statistiques des énigmes', 'chassesautresor-com'),
        __('Participants, étapes, réussites et classements seront affichés ici.', 'chassesautresor-com')
    );
    ?>
</div>

<?php
myaccount_render_dashboard_section(
    __('Outils', 'chassesautresor-com'),
    __('Outils actifs en haut de zone ; le reste reste disponible mais inactif pour l’instant.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Protection globale', 'chassesautresor-com'),
        __('Le contrôle de protection du site sera déplacé ici depuis Outils.', 'chassesautresor-com')
    );
    ?>
</div>
<div class="dashboard-grid dashboard-grid--inactive" aria-disabled="true">
    <?php
    myaccount_render_dashboard_placeholder(
        __('Points', 'chassesautresor-com'),
        __('Inactif pour le moment. Réactivable plus tard via Expérience du site.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('Taux de conversion', 'chassesautresor-com'),
        __('Inactif pour le moment.', 'chassesautresor-com')
    );
    myaccount_render_dashboard_placeholder(
        __('ACF', 'chassesautresor-com'),
        __('Inactif pour le moment.', 'chassesautresor-com')
    );
    ?>
</div>
