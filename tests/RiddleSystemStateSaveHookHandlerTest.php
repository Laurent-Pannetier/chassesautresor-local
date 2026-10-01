<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleSystemStateSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/RiddleSystemStateSaveHookHandler.php';

final class RiddleSystemStateSaveHookHandlerTest extends TestCase {
    public function testRegistersAcfLifecycleOnce(): void {
        $hooks = [];
        RiddleSystemStateSaveHookHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([[
            'acf/save_post',
            [RiddleSystemStateSaveHookHandler::class, 'handle'],
            20,
            1,
        ]], $hooks);
    }
}
