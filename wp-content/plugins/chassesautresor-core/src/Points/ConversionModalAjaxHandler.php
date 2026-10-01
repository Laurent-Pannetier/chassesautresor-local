<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Points;

use ChassesAuTresor\Core\Relationships\OrganizerRepository;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** AJAX transport for the organizer conversion modal. */
class ConversionModalAjaxHandler {
    /** @var callable|null */
    private static $renderer;

    public static function register(callable $addAction): void {
        $addAction('wp_ajax_conversion_modal_content', [self::class, 'handle']);
    }

    public static function configure(callable $renderer): void {
        self::$renderer = $renderer;
    }

    public static function handle(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Unauthorized', 'chassesautresor-com')], 403);
        }

        global $wpdb;
        $userId = (int) get_current_user_id();
        $points = CoreServiceFactory::points($wpdb);
        $organizers = new OrganizerRepository($wpdb);
        $access = (new ConversionAccessService(
            CoreServiceFactory::conversion($wpdb),
            $points,
            $organizers
        ))->resolve($userId);
        $html = is_callable(self::$renderer)
            ? (string) call_user_func(self::$renderer, $access)
            : (new ConversionModalRenderer())->render(
                $access,
                $organizers->findIdForUser($userId) ?? 0,
                (int) apply_filters('points_conversion_min', 500),
                $points->getBalance($userId),
                (new ConversionSettingsService())->getRate()
            );
        wp_send_json_success([
            'html' => $html,
            'access' => $access === true,
        ]);
    }
}
