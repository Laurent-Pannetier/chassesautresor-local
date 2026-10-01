<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Register save and cron lifecycle for temporary riddle image protection.
 */
class RiddleImageProtectionLifecycle {
    public const CRON_HOOK = 'tache_purge_htaccess_enigmes';

    public static function register(callable $addAction, callable $addFilter): void {
        $addFilter('acf/upload_prefilter/name=enigme_visuel_image', [self::class, 'beginUpload'], 10, 3);
        $addFilter('acf/upload_file/name=enigme_visuel_image', [self::class, 'finishUpload'], 10, 1);
        $addAction('acf/save_post', [self::class, 'protectAfterSave'], 20, 1);
        $addAction('acf/save_post', [self::class, 'restoreAfterSave'], 99, 1);
        $addAction('wp', [self::class, 'schedule'], 10, 1);
        $addAction(self::CRON_HOOK, [self::class, 'purge'], 10, 1);
        $addFilter('cron_schedules', [self::class, 'addSchedule'], 10, 1);
    }

    public static function beginUpload($errors, $file, $field) {
        add_filter('upload_dir', [self::class, 'filterUploadDirectory']);
        return $errors;
    }

    public static function finishUpload($file) {
        remove_filter('upload_dir', [self::class, 'filterUploadDirectory']);
        return $file;
    }

    public static function filterUploadDirectory(array $directories): array {
        $riddleId = isset($_REQUEST['post_id']) ? (int) $_REQUEST['post_id'] : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            return $directories;
        }

        $subdirectory = '/_enigmes/enigme-' . $riddleId;
        $directories['subdir'] = $subdirectory;
        $directories['path'] = $directories['basedir'] . $subdirectory;
        $directories['url'] = $directories['baseurl'] . $subdirectory;
        return $directories;
    }

    public static function protectAfterSave($postId): void {
        $riddleId = is_numeric($postId) ? (int) $postId : 0;
        if ($riddleId <= 0 || get_post_type($riddleId) !== 'enigme') {
            return;
        }

        $images = get_field('enigme_visuel_image', $riddleId, false);
        if (is_array($images) && $images !== []) {
            (new RiddleImageProtectionService())->protect($riddleId, false);
        }
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
