<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use DateTime;
use DateTimeInterface;
use DateTimeZone;

/**
 * WordPress AJAX adapter for grouped hunt date mutations.
 */
class HuntDateMutationAjaxHandler {
    public static function register(callable $addAction): void {
        $addAction('wp_ajax_modifier_dates_chasse', [self::class, 'handle'], 10, 1);
    }

    public static function handle(): void {
        check_ajax_referer('hunt_field_management', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error('non_connecte');
        }

        $huntId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if ($huntId <= 0 || get_post_type($huntId) !== 'chasse') {
            wp_send_json_error('post_invalide');
        }

        if (!apply_filters('chassesautresor_can_edit_hunt_dates', false, $huntId)) {
            wp_send_json_error('acces_refuse');
        }

        $mutation = (new HuntDateMutationService())->apply(
            $huntId,
            sanitize_text_field($_POST['date_debut'] ?? ''),
            sanitize_text_field($_POST['date_fin'] ?? ''),
            !empty($_POST['illimitee']),
            !empty($_POST['debut_differee']),
            [self::class, 'parseDate'],
            'update_field',
            'update_post_meta',
            'get_post_meta'
        );

        if ($mutation['error'] !== null) {
            wp_send_json_error($mutation['error']);
        }

        do_action('chassesautresor_hunt_dates_updated', $huntId);
        wp_send_json_success($mutation['data']);
    }

    /**
     * @param array<int, string> $formats
     */
    public static function parseDate(string $value, array $formats): ?DateTimeInterface {
        if ($value === '') {
            return null;
        }

        $timezone = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('UTC');
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value, $timezone);
            if ($date instanceof DateTimeInterface) {
                return $date;
            }
        }

        return null;
    }
}
