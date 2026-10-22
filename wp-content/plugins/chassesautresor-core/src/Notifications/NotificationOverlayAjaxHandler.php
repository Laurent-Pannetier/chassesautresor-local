<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Notifications;

/** Deliver and acknowledge priority overlays without exposing another user's notifications. */
final class NotificationOverlayAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cat_notification_overlay_next', [self::class, 'next']);
        $addAction('wp_ajax_cat_notification_overlay_heartbeat', [self::class, 'heartbeat']);
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
                'claimToken' => (string) $notification['claim_token'],
            ],
        ]);
    }

    public static function heartbeat(): void
    {
        self::guard();
        $renewed = cat_notification_repository()->renewOverlayClaim(
            self::notificationId(),
            get_current_user_id(),
            self::claimToken()
        );
        if (!$renewed) {
            wp_send_json_error(['message' => __('Réclamation expirée.', 'chassesautresor-com')], 409);
        }

        wp_send_json_success();
    }

    public static function seen(): void
    {
        self::guard();
        $seen = cat_notification_repository()->markOverlaySeen(
            self::notificationId(),
            get_current_user_id(),
            self::claimToken()
        );
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

    private static function notificationId(): int
    {
        return isset($_POST['notification_id'])
            ? absint(wp_unslash((string) $_POST['notification_id']))
            : 0;
    }

    private static function claimToken(): string
    {
        return isset($_POST['claim_token'])
            ? sanitize_key(wp_unslash((string) $_POST['claim_token']))
            : '';
    }

    /** @param array<string, mixed> $notification */
    private static function imageUrl(array $notification): string
    {
        if ((string) ($notification['source_type'] ?? '') !== 'trophy') {
            return '';
        }

        $trophyId = (int) ($notification['source_id'] ?? 0);
        $image = function_exists('get_field') ? get_field('trophee_image', $trophyId) : 0;
        if (is_array($image)) {
            $image = $image['ID'] ?? $image['id'] ?? 0;
        }
        $imageId = (int) $image;
        if ($imageId <= 0 && function_exists('get_post_thumbnail_id')) {
            $imageId = (int) get_post_thumbnail_id($trophyId);
        }
        if ($imageId <= 0 || !function_exists('wp_get_attachment_image_url')) {
            return '';
        }

        $url = wp_get_attachment_image_url($imageId, 'medium');

        return is_string($url) ? $url : '';
    }
}
