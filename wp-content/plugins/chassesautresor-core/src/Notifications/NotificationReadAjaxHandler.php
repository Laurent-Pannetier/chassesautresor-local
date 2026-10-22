<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Secure actions used by the notification center to update read state. */
final class NotificationReadAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cat_notification_mark_read', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Accès refusé.', 'chassesautresor-com')], 403);
        }
        check_ajax_referer('cat_notifications', 'nonce');

        $userId = get_current_user_id();
        $all = isset($_POST['all']) && sanitize_text_field(wp_unslash((string) $_POST['all'])) === '1';
        $repository = cat_notification_repository();
        if ($all) {
            $repository->markAllRead($userId);
            wp_send_json_success(['unread' => 0]);
        }

        $notificationId = isset($_POST['notification_id'])
            ? absint(wp_unslash((string) $_POST['notification_id']))
            : 0;
        if (!$repository->markRead($notificationId, $userId)) {
            wp_send_json_error(['message' => __('Notification introuvable.', 'chassesautresor-com')], 404);
        }

        wp_send_json_success(['unread' => $repository->unreadCount($userId)]);
    }
}
