<?php
/**
 * Progression page: statistics moved off the home pages.
 *
 * Players keep the team slots. Organizers and administrators keep the
 * per-riddle indicators. The figures stay in the Core renderer.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

$logged_in = function_exists('is_user_logged_in') && is_user_logged_in();
if (!$logged_in) {
    echo '<p class="app-placeholder__note">';
    esc_html_e('Connectez-vous pour consulter votre progression.', 'chassesautresor-com');
    echo '</p>';
    return;
}

$is_player = function_exists('myaccount_user_is_player') && myaccount_user_is_player();
if ($is_player) {
    if (function_exists('cta_shell_render_team_stats')) {
        cta_shell_render_team_stats();
    }
    return;
}

$hunt_id = function_exists('cat_get_managed_hunt_id_for_user')
    ? (int) cat_get_managed_hunt_id_for_user()
    : 0;
$is_admin = function_exists('cta_shell_user_is_administrator') && cta_shell_user_is_administrator();
$intro = $is_admin
    ? __('Indicateurs par énigme pour suivre la participation.', 'chassesautresor-com')
    : __('Vue détaillée de la participation à votre chasse.', 'chassesautresor-com');
?>
<div class="myaccount-layout">
    <div class="myaccount-main">
        <p class="dashboard-section-intro"><?php echo esc_html($intro); ?></p>
        <div class="dashboard-grid dashboard-grid--wide">
            <?php
            $stats_renderer = new ChassesAuTresor\Core\Messages\AccountHuntRiddleStatisticsRenderer();
            echo $stats_renderer->render($hunt_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
    </div>
</div>
