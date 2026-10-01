<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

/**
 * Register the organizer contact permalink endpoint.
 */
final class OrganizerContactRouteHandler {
    public static function register(callable $addAction, callable $addFilter): void {
        $addAction('init', [self::class, 'registerEndpoint']);
        $addFilter('query_vars', [self::class, 'addQueryVariable']);
    }

    public static function registerEndpoint(): void {
        add_rewrite_endpoint('contact', EP_PERMALINK);
    }

    public static function addQueryVariable(array $variables): array {
        $variables[] = 'contact';

        return array_values(array_unique($variables));
    }
}
