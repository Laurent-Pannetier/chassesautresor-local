<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntCreationRouteHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntCreationRouteHandler.php';

final class HuntCreationRouteHandlerTest extends TestCase {
    public function testRegistersCreationRouteHooks(): void {
        $hooks = [];

        HuntCreationRouteHandler::register(
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
            ['init', [HuntCreationRouteHandler::class, 'registerRoute'], 10, 1],
            ['template_redirect', [HuntCreationRouteHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
