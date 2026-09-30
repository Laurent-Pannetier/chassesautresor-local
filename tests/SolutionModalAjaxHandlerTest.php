<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionModalAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionModalAjaxHandler.php';

final class SolutionModalAjaxHandlerTest extends TestCase {
    public function testRegistersModalEndpoints(): void {
        $hooks = [];
        SolutionModalAjaxHandler::register(
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
            ['wp_ajax_creer_solution_modal', [SolutionModalAjaxHandler::class, 'create'], 10, 1],
            ['wp_ajax_modifier_solution_modal', [SolutionModalAjaxHandler::class, 'update'], 10, 1],
        ], $hooks);
    }
}
