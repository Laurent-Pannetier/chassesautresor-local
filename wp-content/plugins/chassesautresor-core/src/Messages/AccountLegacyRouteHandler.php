<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Messages;

/**
 * Redirect legacy account section URLs to the canonical account dashboard.
 */
final class AccountLegacyRouteHandler {
    private const SECTIONS = ['organisateurs', 'statistiques', 'outils'];

    public static function register(callable $addAction, callable $addFilter): void {
        $addAction('init', [self::class, 'registerRoutes']);
        $addFilter('query_vars', [self::class, 'addQueryVariables']);
        $addFilter('template_include', [self::class, 'redirectLegacyRoute']);
    }

    public static function registerRoutes(): void {
        add_rewrite_rule('^mon-compte/statistiques/?$', 'index.php?mon_compte_statistiques=1', 'top');
        add_rewrite_rule('^mon-compte/outils/?$', 'index.php?mon_compte_outils=1', 'top');
    }

    public static function addQueryVariables(array $variables): array {
        $variables[] = 'mon_compte_statistiques';
        $variables[] = 'mon_compte_outils';

        return array_values(array_unique($variables));
    }

    public static function redirectLegacyRoute(string $template): string {
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url()) {
            return $template;
        }

        $path = self::requestPath();
        $rawSection = isset($_GET['section']) ? (string) $_GET['section'] : '';
        $section = function_exists('sanitize_key') ? sanitize_key(wp_unslash($rawSection)) : $rawSection;
        $isAdministrator = function_exists('current_user_can') && current_user_can('administrator');
        $target = self::legacyTarget($path, $section, $isAdministrator);
        if ($target === null) {
            return $template;
        }

        self::redirect($target);

        return $template;
    }

    /**
     * Destination of a legacy account URL.
     *
     * Only real /mon-compte/ sections are legacy. The public /outils/ page
     * stays in place and shows the shell tools.
     */
    public static function legacyTarget(string $path, string $section, bool $isAdministrator): ?string {
        if ($path === 'mon-compte/chasses' || ($path === 'mon-compte' && $section === 'chasses')) {
            return '/mon-compte/';
        }

        if ($path === 'mon-compte/outils' || ($path === 'mon-compte' && $section === 'outils')) {
            return '/outils/';
        }

        $prefix = 'mon-compte/';
        if (strpos($path, $prefix) !== 0) {
            return null;
        }

        $legacySection = substr($path, strlen($prefix));
        if (!in_array($legacySection, self::SECTIONS, true) || $legacySection === 'outils') {
            return null;
        }

        $target = '/mon-compte/';
        if ($isAdministrator) {
            $target .= '?section=' . $legacySection;
        }

        return $target;
    }

    private static function requestPath(): string {
        $request = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
        $parsed = $request !== '' ? wp_parse_url($request) : [];

        return isset($parsed['path']) ? trim($parsed['path'], '/') : '';
    }

    private static function redirect(string $path): void {
        wp_safe_redirect(home_url($path));
        exit;
    }
}
