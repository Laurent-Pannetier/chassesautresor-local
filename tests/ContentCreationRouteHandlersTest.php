<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCreationRouteHandler;
use ChassesAuTresor\Core\Content\HintRouteRegistrar;
use ChassesAuTresor\Core\Content\SolutionCreationRouteHandler;
use ChassesAuTresor\Core\Content\SolutionRouteRegistrar;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCreationRouteHandler.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionCreationRouteHandler.php';

final class ContentCreationRouteHandlersTest extends TestCase {
    /** @dataProvider handlerProvider */
    public function testRegistersEachCreationLifecycle(string $handler, string $registrar): void {
        $hooks = [];

        $handler::register(
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
            ['init', [$registrar, 'register'], 10, 1],
            ['init', [$registrar, 'maybeFlush'], 20, 1],
            ['template_redirect', [$handler, 'handle'], 10, 1],
        ], $hooks);
    }

    /** @return array<string, array{0:string,1:string}> */
    public function handlerProvider(): array {
        return [
            'hint' => [HintCreationRouteHandler::class, HintRouteRegistrar::class],
            'solution' => [SolutionCreationRouteHandler::class, SolutionRouteRegistrar::class],
        ];
    }
}
