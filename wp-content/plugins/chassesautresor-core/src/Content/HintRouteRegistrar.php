<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Register and maintain the route used to create hints.
 */
class HintRouteRegistrar
{
    private const FLUSHED_OPTION = 'creer_indice_rewrite_flushed';

    public static function register(): void
    {
        add_rewrite_rule('^creer-indice/?$', 'index.php?creer_indice=1', 'top');
        add_rewrite_tag('%creer_indice%', '1');
    }

    public static function flush(): void
    {
        self::register();
        flush_rewrite_rules();
        update_option(self::FLUSHED_OPTION, 1);
    }

    public static function maybeFlush(): void
    {
        if (!get_option(self::FLUSHED_OPTION)) {
            self::flush();
        }
    }
}
