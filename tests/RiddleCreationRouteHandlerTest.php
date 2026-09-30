<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleCreationRouteHandler;
use ChassesAuTresor\Core\Content\RiddleRouteRegistrar;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleCreationRouteHandler.php';

final class RiddleCreationRouteHandlerTest extends TestCase {
    public function testRegistersCreationRouteHooks(): void {
        $hooks = [];

        RiddleCreationRouteHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            ['init', [RiddleRouteRegistrar::class, 'register'], 10, 1],
            ['init', [RiddleRouteRegistrar::class, 'maybeFlush'], 20, 1],
            ['template_redirect', [RiddleCreationRouteHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
