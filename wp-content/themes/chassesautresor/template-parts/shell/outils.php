<?php
/**
 * Tools moved off the home pages for organizers and administrators.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$is_admin = function_exists('cta_shell_user_is_administrator') && cta_shell_user_is_administrator();
$hunt_id = function_exists('cat_get_managed_hunt_id_for_user')
    ? (int) cat_get_managed_hunt_id_for_user()
    : 0;
$can_reset = $is_admin || (
    function_exists('cat_is_demo_mode')
    && cat_is_demo_mode()
    && function_exists('is_user_logged_in')
    && is_user_logged_in()
);

if (!$is_admin) {
    myaccount_render_dashboard_section(
        __('Pilotage de la chasse', 'chassesautresor-com'),
        __('Éditez vos entités et pilotez le cycle de vie depuis cet accueil.', 'chassesautresor-com')
    );
}
?>
<div class="dashboard-grid">
    <?php
    if (function_exists('cat_render_hunt_lifecycle_switch')) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo cat_render_hunt_lifecycle_switch($hunt_id);
    }
    if ($can_reset) :
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
if (!$is_admin) {
    return;
}

$protection = '';
if (class_exists(ChassesAuTresor\Core\Messages\AccountToolsRenderer::class)) {
    $tools_renderer = new ChassesAuTresor\Core\Messages\AccountToolsRenderer();
    $protection = $tools_renderer->renderProtectionCard();
}
if ($protection !== '') :
    ?>
    <div class="dashboard-grid">
        <?php echo $protection; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
<?php endif; ?>
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
