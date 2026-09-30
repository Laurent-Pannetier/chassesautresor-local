<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HuntInitializationHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HuntInitializationHookHandler.php';

final class HuntInitializationHookHandlerTest extends TestCase {
    public function testRegistersHuntSaveHook(): void {
        $hooks = [];

        HuntInitializationHookHandler::register(
            static function (
                string $hook,
                array $callback,
                int $priority,
                int $acceptedArgs
            ) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'save_post_chasse',
            [HuntInitializationHookHandler::class, 'handle'],
            20,
            2,
        ]], $hooks);
    }
}
