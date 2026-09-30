<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\HintModalAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/HintModalAjaxHandler.php';

final class HintModalAjaxHandlerTest extends TestCase {
    public function testRegistersModalEndpoints(): void {
        $hooks = [];

        HintModalAjaxHandler::register(
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
            ['wp_ajax_creer_indice_modal', [HintModalAjaxHandler::class, 'create'], 10, 1],
            ['wp_ajax_modifier_indice_modal', [HintModalAjaxHandler::class, 'update'], 10, 1],
        ], $hooks);
    }
}
