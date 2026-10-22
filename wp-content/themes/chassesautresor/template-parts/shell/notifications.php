<?php
/**
 * Authenticated notification center.
 *
 * @package chassesautresor
 */

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
    $login = wp_login_url(home_url('/notifications/'));
    echo '<p class="notifications-center__empty">';
    echo esc_html__('Connectez-vous pour consulter vos notifications.', 'chassesautresor-com');
    echo ' <a href="' . esc_url($login) . '">' . esc_html__('Connexion', 'chassesautresor-com') . '</a>';
    echo '</p>';
    return;
}

if (
    !function_exists('cat_user_notifications')
    || !class_exists(ChassesAuTresor\Core\Notifications\NotificationsPageRenderer::class)
) {
    echo '<p class="notifications-center__empty">';
    echo esc_html__('Les notifications ne sont pas disponibles pour le moment.', 'chassesautresor-com');
    echo '</p>';
    return;
}

echo (new ChassesAuTresor\Core\Notifications\NotificationsPageRenderer())->render(
    cat_user_notifications(get_current_user_id())
); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
