<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintOrderingLifecycleHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintOrderingLifecycleHookHandler.php';

final class HintOrderingLifecycleHookHandlerTest extends TestCase {
    public function testRegistersOrderingLifecycleHooks(): void {
        $hooks = [];
        HintOrderingLifecycleHookHandler::register(
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
            ['save_post_indice', [HintOrderingLifecycleHookHandler::class, 'handleSaved'], 20, 1],
            ['trashed_post', [HintOrderingLifecycleHookHandler::class, 'requestForHint'], 10, 1],
        ], $hooks);
    }
}
