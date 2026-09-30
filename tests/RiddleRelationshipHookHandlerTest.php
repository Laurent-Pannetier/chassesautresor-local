<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleRelationshipHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRelationshipHookHandler.php';

final class RiddleRelationshipHookHandlerTest extends TestCase {
    public function testRegistersRelationshipLifecycleHooks(): void {
        $hooks = [];

        RiddleRelationshipHookHandler::register(
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
            [
                'acf/save_post',
                [RiddleRelationshipHookHandler::class, 'handleSave'],
                20,
                1,
            ],
            [
                'before_delete_post',
                [RiddleRelationshipHookHandler::class, 'handleBeforeDelete'],
                10,
                1,
            ],
        ], $hooks);
    }
}
