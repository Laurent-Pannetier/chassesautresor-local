<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Register save and cron lifecycle for temporary riddle image protection.
 */
class RiddleImageProtectionLifecycle {
    public const CRON_HOOK = 'tache_purge_htaccess_enigmes';

    public static function register(callable $addAction, callable $addFilter): void {
        $addAction('acf/save_post', [self::class, 'restoreAfterSave'], 99, 1);
        $addAction('wp', [self::class, 'schedule'], 10, 1);
        $addAction(self::CRON_HOOK, [self::class, 'purge'], 10, 1);
        $addFilter('cron_schedules', [self::class, 'addSchedule'], 10, 1);
    }

    public static function restoreAfterSave($postId): void {
        $riddleId = is_numeric($postId) ? (int) $postId : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            return;
        }
        do_action('chassesautresor_reinject_riddle_image_protection', $riddleId);
        (new RiddleImageProtectionService())->restore($riddleId, false);
    }

    public static function schedule(): void {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'every_5_minutes', self::CRON_HOOK);
        }
    }

    public static function purge(): void {
        (new RiddleImageProtectionService())->purgeExpired();
    }

    public static function addSchedule(array $schedules): array {
        $schedules['every_5_minutes'] = [
            'interval' => 300,
            'display' => __('Toutes les 5 minutes', 'chassesautresor-com'),
        ];
        return $schedules;
    }
}
