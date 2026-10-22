<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

use ChassesAuTresor\Core\Site\SiteExperienceService;

/** Let a signed-in demo player restart, without touching anyone else. */
final class PlayerProgressResetAjaxHandler
{
    public static function register(callable $addAction): void
    {
        $addAction('wp_ajax_cta_reset_my_progress', [self::class, 'handle']);
    }

    public static function handle(): void
    {
        $userId = (int) get_current_user_id();
        $settings = function_exists('cat_get_site_experience_settings')
            ? cat_get_site_experience_settings()
            : [];
        $demo = (new SiteExperienceService())->isDemoMode(is_array($settings) ? $settings : []);
        if ($userId <= 0 || !$demo) {
            wp_send_json_error(__('Non autorisé', 'chassesautresor-com'));
            return;
        }

        check_ajax_referer('cta_reset_my_progress', 'nonce');
        global $wpdb;
        $result = (new PlayerProgressResetService($wpdb))->reset($userId);
        if ($result['error'] !== '') {
            wp_send_json_error(__('La réinitialisation a échoué.', 'chassesautresor-com'));
            return;
        }

        wp_send_json_success(['deleted' => $result['deleted']]);
    }
}
