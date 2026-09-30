<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintCardAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintCardAjaxHandler.php';

final class HintCardAjaxHandlerTest extends TestCase {
    public function testRegistersHintCardEndpoint(): void {
        $hooks = [];
        HintCardAjaxHandler::register(
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
            ['wp_ajax_chasse_lister_indices', [HintCardAjaxHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
