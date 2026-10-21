<?php
/**
 * Organizer account home shell.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (function_exists('cat_is_single_hunt_mode') && cat_is_single_hunt_mode()) {
    if (function_exists('cta_shell_render_hunt_banner')) {
        cta_shell_render_hunt_banner();
    }
    if (function_exists('cat_render_single_hunt_home_enigmas')) {
        cat_render_single_hunt_home_enigmas();
    }
}

$hunt_id = function_exists('cat_get_managed_hunt_id_for_user')
    ? cat_get_managed_hunt_id_for_user()
    : 0;

myaccount_render_dashboard_section(
    __('Statistiques', 'chassesautresor-com'),
    __('Vue détaillée de la participation à votre chasse.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid dashboard-grid--wide">
    <?php
    $stats_renderer = new ChassesAuTresor\Core\Messages\AccountHuntRiddleStatisticsRenderer();
    echo $stats_renderer->render($hunt_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>
