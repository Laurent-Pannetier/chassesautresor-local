<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintRelationshipSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRelationshipSaveHookHandler.php';

final class HintRelationshipSaveHookHandlerTest extends TestCase {
    public function testRegistersAcfSaveHook(): void {
        $hooks = [];
        HintRelationshipSaveHookHandler::register(
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
            ['acf/save_post', [HintRelationshipSaveHookHandler::class, 'handle'], 20, 1],
        ], $hooks);
    }
}
