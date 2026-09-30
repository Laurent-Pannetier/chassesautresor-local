<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintTableAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintTableAjaxHandler.php';

final class HintTableAjaxHandlerTest extends TestCase {
    public function testRegistersHintTableEndpoint(): void {
        $hooks = [];
        HintTableAjaxHandler::register(
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
            ['wp_ajax_indices_lister_table', [HintTableAjaxHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
