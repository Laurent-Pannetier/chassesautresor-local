<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Register and maintain the route used to create solutions.
 */
class SolutionRouteRegistrar
{
    private const FLUSHED_OPTION = 'creer_solution_rewrite_flushed';

    public static function register(): void
    {
        add_rewrite_rule('^creer-solution/?$', 'index.php?creer_solution=1', 'top');
        add_rewrite_tag('%creer_solution%', '1');
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
