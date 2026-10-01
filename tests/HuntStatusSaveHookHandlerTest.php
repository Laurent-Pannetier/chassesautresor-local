<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntStatusSaveHookHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntStatusSaveHookHandler.php';

final class HuntStatusSaveHookHandlerTest extends TestCase {
    public function testRegistersFunctionalAndPublicationStatusLifecyclesOnce(): void {
        $hooks = [];
        HuntStatusSaveHookHandler::register(
            static function ($hook, $callback, $priority, $acceptedArgs) use (&$hooks): void {
                $hooks[] = [$hook, $callback, $priority, $acceptedArgs];
            }
        );

        $this->assertSame([
            ['acf/save_post', [HuntStatusSaveHookHandler::class, 'refresh'], 20, 1],
            ['acf/save_post', [HuntStatusSaveHookHandler::class, 'synchronizePublication'], 99, 1],
        ], $hooks);
    }
}
