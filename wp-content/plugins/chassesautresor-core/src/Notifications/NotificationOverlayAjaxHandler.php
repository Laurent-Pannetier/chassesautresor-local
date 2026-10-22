<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Deliver and acknowledge priority overlays without exposing another user's notifications. */
final class NotificationOverlayAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cat_notification_overlay_next', [self::class, 'next']);
        $addAction('wp_ajax_cat_notification_overlay_seen', [self::class, 'seen']);
    }

    public static function next(): void
    {
        self::guard();
        $notification = cat_notification_repository()->nextOverlay(get_current_user_id());
        if ($notification === null) {
            wp_send_json_success(['notification' => null]);
        }

        wp_send_json_success([
            'notification' => [
                'id' => (int) $notification['id'],
                'type' => (string) $notification['type'],
                'sourceType' => (string) $notification['source_type'],
                'title' => (string) $notification['title'],
                'body' => (string) $notification['body'],
                'imageUrl' => self::imageUrl($notification),
            ],
        ]);
    }

    public static function seen(): void
    {
        self::guard();
        $notificationId = isset($_POST['notification_id'])
            ? absint(wp_unslash((string) $_POST['notification_id']))
            : 0;
        $seen = cat_notification_repository()->markOverlaySeen($notificationId, get_current_user_id());
        if (!$seen) {
            wp_send_json_error(['message' => __('Notification introuvable.', 'chassesautresor-com')], 404);
        }

        wp_send_json_success();
    }

    private static function guard(): void
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Accès refusé.', 'chassesautresor-com')], 403);
        }
        check_ajax_referer('cat_notifications', 'nonce');
    }

    /** @param array<string, mixed> $notification */
    private static function imageUrl(array $notification): string
    {
        if (
            (string) ($notification['source_type'] ?? '') !== 'trophy'
            || !function_exists('get_the_post_thumbnail_url')
        ) {
            return '';
        }

        $url = get_the_post_thumbnail_url((int) ($notification['source_id'] ?? 0), 'medium');

        return is_string($url) ? $url : '';
    }
}
