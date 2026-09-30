<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCacheSaveHookHandler;
use ChassesAuTresor\Core\Content\HintScheduler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintScheduler.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCacheSaveHookHandler.php';

final class HintCacheSaveHookHandlerTest extends TestCase {
    public function testRegistersCacheRefreshHooks(): void {
        $hooks = [];
        HintCacheSaveHookHandler::register(
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
            ['acf/save_post', [HintCacheSaveHookHandler::class, 'handle'], 30, 1],
            [HintScheduler::PROCESS_HOOK, [HintCacheSaveHookHandler::class, 'handle'], 10, 1],
            ['chassesautresor_hint_cache_refresh_requested', [HintCacheSaveHookHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
