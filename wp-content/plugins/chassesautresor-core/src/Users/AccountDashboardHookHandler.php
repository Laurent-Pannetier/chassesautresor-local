<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

use ChassesAuTresor\Core\Progress\EngagedHuntsRenderer;
use ChassesAuTresor\Core\Progress\UserAttemptsRenderer;
use ChassesAuTresor\Core\Progress\UserAttemptsViewService;
use ChassesAuTresor\Core\Support\CoreServiceFactory;

/** Own account dashboard hooks and provide portable fallbacks for third-party themes. */
final class AccountDashboardHookHandler {
    public static function register(callable $addAction): void {
        $addAction('woocommerce_account_dashboard', [self::class, 'renderEngagedHunts'], 10);
        $addAction('woocommerce_account_dashboard', [self::class, 'renderAttempts'], 20);
    }

    public static function renderEngagedHunts(): void {
        if (function_exists('ca_render_dashboard_engaged_hunts')) {
            ca_render_dashboard_engaged_hunts();
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        if ((int) $user->ID <= 0 || empty(array_intersect(['subscriber', 'customer'], $roles))) {
            return;
        }
        if (current_user_can('administrator')) {
            return;
        }

        $page = max(1, (int) ($_GET[ca_get_engaged_hunts_page_param()] ?? 1));
        $ids = ca_get_user_engaged_hunt_ids((int) $user->ID);
        $pagination = ca_prepare_engaged_hunts_pagination($ids, $page, 6);
        echo '<section class="chassesautresor-account-hunts"><h2>'
            . esc_html__('Vos chasses en cours', 'chassesautresor-com') . '</h2>'
            . (new EngagedHuntsRenderer())->render($pagination) . '</section>';
    }

    public static function renderAttempts(): void {
        if (function_exists('ca_render_dashboard_tentatives')) {
            ca_render_dashboard_tentatives();
            return;
        }
        $userId = is_user_logged_in() ? (int) get_current_user_id() : 0;
        if ($userId <= 0) {
            return;
        }

        global $wpdb;
        $statistics = CoreServiceFactory::userAttemptStatistics($wpdb);
        $view = (new UserAttemptsViewService($statistics))->build($userId);
        $renderer = new UserAttemptsRenderer();
        echo '<section class="chassesautresor-account-attempts"><h2>'
            . esc_html__('Tentatives', 'chassesautresor-com') . '</h2><table><thead><tr><th>'
            . esc_html__('Date', 'chassesautresor-com') . '</th><th>'
            . esc_html__('Chasse', 'chassesautresor-com') . '</th><th>'
            . esc_html__('Énigme', 'chassesautresor-com') . '</th><th>'
            . esc_html__('Proposition', 'chassesautresor-com') . '</th><th>'
            . esc_html__('Résultat', 'chassesautresor-com') . '</th></tr></thead><tbody>'
            . $renderer->rows($view) . '</tbody></table>' . $renderer->pager($view) . '</section>';
    }
}
