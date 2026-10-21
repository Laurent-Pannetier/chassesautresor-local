<?php
/**
 * Administrator account home shell.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (
    function_exists('cat_is_single_hunt_mode')
    && cat_is_single_hunt_mode()
    && function_exists('cat_render_single_hunt_home_enigmas')
) {
    cat_render_single_hunt_home_enigmas();
}

$hunt_id = function_exists('cat_get_managed_hunt_id_for_user')
    ? cat_get_managed_hunt_id_for_user()
    : 0;

if (function_exists('cat_render_hunt_moderation_queue_card')) {
    $moderation_queue = cat_render_hunt_moderation_queue_card();
    if ($moderation_queue !== '') {
        echo '<div class="dashboard-grid">';
        echo $moderation_queue; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';
    }
}

myaccount_render_dashboard_section(
    __('Statistiques', 'chassesautresor-com'),
    __('Indicateurs par énigme pour suivre la participation.', 'chassesautresor-com')
);
?>
<div class="dashboard-grid dashboard-grid--wide">
    <?php
    $stats_renderer = new ChassesAuTresor\Core\Messages\AccountHuntRiddleStatisticsRenderer();
    echo $stats_renderer->render($hunt_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    ?>
</div>

