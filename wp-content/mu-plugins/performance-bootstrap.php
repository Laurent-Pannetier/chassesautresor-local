<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class Chassesautresor_Performance_Bootstrap
{
    private const CACHE_PLUGIN = 'litespeed-cache/litespeed-cache.php';

    private const OPTIMIZATION_OPTION = 'chassesautresor_last_db_optimization';

    private const OPTIMIZATION_INTERVAL_DAYS = 7;

    public static function boot(): void
    {
        add_action('muplugins_loaded', [self::class, 'ensureCachePluginActive'], 5);
        add_action('admin_init', [self::class, 'optimizeDatabase'], 20);
    }

    public static function ensureCachePluginActive(): void
    {
        if (!defined('WP_PLUGIN_DIR')) {
            return;
        }

        $pluginPath = WP_PLUGIN_DIR . '/' . self::CACHE_PLUGIN;

        if (!file_exists($pluginPath)) {
            return;
        }

        if (!function_exists('activate_plugin')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (function_exists('is_plugin_active') && is_plugin_active(self::CACHE_PLUGIN)) {
            return;
        }

        $result = activate_plugin(self::CACHE_PLUGIN);

        if (is_wp_error($result)) {
            add_action('admin_notices', static function () use ($result): void {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    esc_html($result->get_error_message())
                );
            });
        }
    }

    public static function optimizeDatabase(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $lastOptimization = (int) get_site_option(self::OPTIMIZATION_OPTION, 0);
        $interval = self::getOptimizationInterval();

        if ($lastOptimization > 0 && (time() - $lastOptimization) < $interval) {
            return;
        }

        global $wpdb;

        $tables = $wpdb->tables('all', true);

        if (empty($tables)) {
            update_site_option(self::OPTIMIZATION_OPTION, time());
            return;
        }

        $failedTables = [];

        foreach ($tables as $table) {
            $sanitizedTable = sprintf('`%s`', str_replace('`', '', esc_sql($table)));
            $result = $wpdb->query("OPTIMIZE TABLE {$sanitizedTable}");

            if (false === $result) {
                $failedTables[] = $table;
            }
        }

        update_site_option(self::OPTIMIZATION_OPTION, time());

        add_action('admin_notices', static function () use ($failedTables): void {
            if (empty($failedTables)) {
                printf(
                    '<div class="notice notice-success"><p>%s</p></div>',
                    esc_html__(
                        'La base de données a été optimisée avec succès.',
                        'chassesautresor-com'
                    )
                );

                return;
            }

            $failedList = implode(
                ', ',
                array_map(
                    static function (string $table): string {
                        return sanitize_text_field($table);
                    },
                    $failedTables
                )
            );

            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html(
                    sprintf(
                        /* translators: %s: database table names. */
                        __(
                            'La base de données a été optimisée mais certaines tables n\'ont pas pu être traitées : %s.',
                            'chassesautresor-com'
                        ),
                        $failedList
                    )
                )
            );
        });
    }

    private static function getOptimizationInterval(): int
    {
        if (defined('DAY_IN_SECONDS')) {
            return (int) DAY_IN_SECONDS * self::OPTIMIZATION_INTERVAL_DAYS;
        }

        return self::OPTIMIZATION_INTERVAL_DAYS * 24 * 60 * 60;
    }
}

Chassesautresor_Performance_Bootstrap::boot();
