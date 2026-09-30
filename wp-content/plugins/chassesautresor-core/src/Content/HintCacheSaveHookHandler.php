<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTime;
use DateTimeZone;

/**
 * Refresh hint relationship, cache and ordering after ACF or scheduler updates.
 */
class HintCacheSaveHookHandler {
    public static function register(callable $addAction): void {
        $addAction('acf/save_post', [self::class, 'handle'], 30, 1);
        $addAction(HintScheduler::PROCESS_HOOK, [self::class, 'handle'], 10, 1);
        $addAction('chassesautresor_hint_cache_refresh_requested', [self::class, 'handle'], 10, 1);
    }

    /** @param int|string $postId */
    public static function handle($postId): void {
        if (!is_numeric($postId) || get_post_type((int) $postId) !== 'indice') {
            return;
        }
        $hintId = (int) $postId;
        if (wp_is_post_revision($hintId) || wp_is_post_autosave($hintId)) {
            return;
        }

        HintRelationshipSaveHookHandler::handle($hintId);
        (new HintCacheUpdater())->update(
            $hintId,
            time(),
            'get_field',
            [self::class, 'parseDate'],
            'get_post_status',
            'get_post',
            'update_field',
            'wp_update_post'
        );
        HintOrderingLifecycleHookHandler::requestForHint($hintId);
    }

    public static function parseDate(string $value): ?DateTime {
        if ($value === '') {
            return null;
        }
        $timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
        if (preg_match('/^\d{9,10}$/', $value) === 1) {
            $date = new DateTime('@' . $value);
            $date->setTimezone($timezone);
            return $date;
        }

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y h:i a',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d\TH:i',
            'Y-m-d',
            'Ymd',
            'YmdHis',
        ];
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value, $timezone);
            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }
}
