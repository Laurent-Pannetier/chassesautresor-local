<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintRiddleOptionsAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRiddleOptionsAjaxHandler.php';

final class HintRiddleOptionsAjaxHandlerTest extends TestCase {
    public function testRegistersRiddleOptionsEndpoint(): void {
        $hooks = [];
        HintRiddleOptionsAjaxHandler::register(
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
            ['wp_ajax_chasse_lister_enigmes', [HintRiddleOptionsAjaxHandler::class, 'handle'], 10, 1],
        ], $hooks);
    }
}
