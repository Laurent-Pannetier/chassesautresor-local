<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Relationships;

use Closure;

final class OrganizerConfirmationRouteHandler
{
    private static ?Closure $confirmRequest = null;
    private static ?Closure $clearMessages = null;

    public static function register(callable $addAction): void
    {
        $addAction('init', [self::class, 'registerRoute']);
        $addAction('template_redirect', [self::class, 'handle']);
    }

    public static function configure(callable $confirmRequest, callable $clearMessages): void
    {
        self::$confirmRequest = Closure::fromCallable($confirmRequest);
        self::$clearMessages = Closure::fromCallable($clearMessages);
    }

    public static function registerRoute(): void
    {
        add_rewrite_rule('^confirmation-organisateur/?$', 'index.php?confirmation_organisateur=1', 'top');
        add_rewrite_tag('%confirmation_organisateur%', '1');
    }

    public static function handle(): void
    {
        if (get_query_var('confirmation_organisateur') !== '1' && !is_page('confirmation-organisateur')) {
            return;
        }

        $userId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
        $organizerId = self::$confirmRequest !== null ? (int) (self::$confirmRequest)($userId, $token) : 0;

        if (self::$clearMessages !== null) {
            (self::$clearMessages)($userId);
        }

        if ($organizerId > 0) {
            wp_set_current_user($userId);
            wp_set_auth_cookie($userId);
            wp_safe_redirect(add_query_arg('confirmation', '1', get_permalink($organizerId)));
            exit;
        }

        wp_safe_redirect(home_url('/devenir-organisateur'));
        exit;
    }
}
