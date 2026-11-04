<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Register and dispatch the protected solution-file route.
 *
 * Riddle images are static files. This handler does not serve them.
 */
final class ProtectedAssetRouteHandler {
    public static function register(callable $addAction, callable $addFilter): void {
        $addAction('init', [self::class, 'registerRoutes'], 1);
        $addFilter('query_vars', [self::class, 'addQueryVariables']);
        $addAction('template_redirect', [self::class, 'dispatch']);
    }

    public static function registerRoutes(): void {
        add_rewrite_rule('^voir-fichier/?$', 'index.php?voir_fichier=1', 'top');
    }

    public static function addQueryVariables(array $variables): array {
        $variables[] = 'voir_fichier';

        return array_values(array_unique($variables));
    }

    public static function dispatch(): void {
        if ((int) get_query_var('voir_fichier') === 1) {
            require __DIR__ . '/protected-solution-file.php';
            exit;
        }
    }
}
