<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintDeletionLifecycleHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintDeletionLifecycleHookHandler.php';

final class HintDeletionLifecycleHookHandlerTest extends TestCase {
    public function testRegistersPermanentDeletionHooks(): void {
        $hooks = [];
        HintDeletionLifecycleHookHandler::register(
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
            ['before_delete_post', [HintDeletionLifecycleHookHandler::class, 'capture'], 10, 1],
            ['deleted_post', [HintDeletionLifecycleHookHandler::class, 'reorder'], 10, 1],
        ], $hooks);
    }
}
