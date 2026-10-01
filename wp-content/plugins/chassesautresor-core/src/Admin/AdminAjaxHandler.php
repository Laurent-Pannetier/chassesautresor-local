<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

use Closure;

final class AdminAjaxHandler
{
    /** @var array<string, Closure> */
    private static array $callbacks = [];

    public static function register(callable $addAction): void
    {
        $actions = [
            'rechercher_utilisateur' => 'searchUsers',
            'lister_historique_paiements' => 'listPayments',
            'update_conversion_status' => 'updateConversionStatus',
            'recuperer_details_acf' => 'inspectAcf',
            'cta_reset_stats' => 'resetStatistics',
            'cta_toggle_site_protection' => 'toggleSiteProtection',
        ];

        foreach ($actions as $action => $method) {
            $addAction('wp_ajax_' . $action, [self::class, $method]);
        }
    }

    /** @param array<string, callable> $callbacks */
    public static function configure(array $callbacks): void
    {
        self::$callbacks = [];
        foreach ($callbacks as $operation => $callback) {
            self::$callbacks[$operation] = Closure::fromCallable($callback);
        }
    }

    public static function searchUsers(): void
    {
        self::dispatch('search_users');
    }

    public static function listPayments(): void
    {
        self::dispatch('list_payments');
    }

    public static function updateConversionStatus(): void
    {
        self::dispatch('update_conversion_status');
    }

    public static function inspectAcf(): void
    {
        self::dispatch('inspect_acf', __('Non autorisé', 'chassesautresor-com'));
    }

    public static function resetStatistics(): void
    {
        self::dispatch('reset_statistics', __('Non autorisé', 'chassesautresor-com'));
    }

    public static function toggleSiteProtection(): void
    {
        self::dispatch('toggle_site_protection', __('Non autorisé', 'chassesautresor-com'));
    }

    private static function dispatch(string $operation, $forbiddenMessage = null): void
    {
        if (!current_user_can('administrator')) {
            $forbiddenMessage === null
                ? wp_send_json_error()
                : wp_send_json_error($forbiddenMessage);
            return;
        }

        if (!isset(self::$callbacks[$operation])) {
            wp_send_json_error([
                'message' => __('Service indisponible.', 'chassesautresor-com'),
            ]);
            return;
        }

        (self::$callbacks[$operation])();
    }
}
