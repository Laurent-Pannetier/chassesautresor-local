<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationRepository;
use ChassesAuTresor\Core\Notifications\NotificationService;

if (!function_exists('cat_notification_repository')) {
    function cat_notification_repository(): NotificationRepository
    {
        global $wpdb;

        return new NotificationRepository($wpdb);
    }
}

if (!function_exists('cat_notification_service')) {
    function cat_notification_service(): NotificationService
    {
        return new NotificationService(cat_notification_repository());
    }
}

if (!function_exists('cat_notification_unread_count')) {
    function cat_notification_unread_count(int $userId): int
    {
        return $userId > 0 ? cat_notification_repository()->unreadCount($userId) : 0;
    }
}

if (!function_exists('cat_user_notifications')) {
    /** @return array<int, array<string, mixed>> */
    function cat_user_notifications(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $userId > 0
            ? cat_notification_repository()->listForUser($userId, $limit, $offset)
            : [];
    }
}

if (!function_exists('cat_user_notification_count')) {
    function cat_user_notification_count(int $userId): int
    {
        return $userId > 0 ? cat_notification_repository()->countForUser($userId) : 0;
    }
}
