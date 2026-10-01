<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

use Closure;

final class HuntModerationRequestHandler
{
    private static ?Closure $executor = null;

    public static function register(callable $addAction): void
    {
        $addAction('admin_post_traiter_validation_chasse', [self::class, 'handle']);
    }

    public static function configure(callable $executor): void
    {
        self::$executor = Closure::fromCallable($executor);
    }

    public static function handle(): void
    {
        if (self::$executor === null) {
            wp_die(__('Service de modération indisponible.', 'chassesautresor-com'));
        }

        (self::$executor)();
    }
}
