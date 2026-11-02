<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Users;

/** Own the WooCommerce account endpoint that lists a player's attempts. */
final class AccountTentativesEndpoint
{
    public const VERSION = 'tentatives-1';

    public static function register(callable $addAction, callable $addFilter): void
    {
        $addAction('init', [self::class, 'registerEndpoint']);
        $addAction('init', [self::class, 'maybeFlush'], 99);
        $addFilter('woocommerce_get_query_vars', [self::class, 'registerQueryVar']);
    }

    public static function registerEndpoint(): void
    {
        add_rewrite_endpoint('tentatives', EP_ROOT | EP_PAGES);
    }

    public static function maybeFlush(): void
    {
        if (get_option('ca_myaccount_endpoints_version') === self::VERSION) {
            return;
        }

        flush_rewrite_rules(false);
        update_option('ca_myaccount_endpoints_version', self::VERSION);
    }

    /**
     * @param array<string, string> $vars
     * @return array<string, string>
     */
    public static function registerQueryVar(array $vars): array
    {
        $vars['tentatives'] = 'tentatives';

        return $vars;
    }
}
