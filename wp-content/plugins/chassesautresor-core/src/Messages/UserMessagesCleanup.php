<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/**
 * Schedule and execute cleanup of expired user messages.
 */
class UserMessagesCleanup
{
    public const HOOK = 'chassesautresor_core_purge_expired_messages';

    /**
     * Schedule the cleanup task once per hour.
     */
    public static function schedule(): void
    {
        if (wp_next_scheduled(self::HOOK) !== false) {
            return;
        }

        wp_schedule_event(time(), 'hourly', self::HOOK);
    }

    /**
     * Remove the cleanup task when the plugin is deactivated.
     */
    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    /**
     * Delete messages whose expiration date has passed.
     */
    public static function run(): void
    {
        global $wpdb;

        (new UserMessageRepository($wpdb))->purgeExpired();
    }
}
