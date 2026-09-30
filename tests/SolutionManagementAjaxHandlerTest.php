<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\SolutionManagementAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionManagementAjaxHandler.php';

final class SolutionManagementAjaxHandlerTest extends TestCase {
    public function testRegistersManagementEndpoints(): void {
        $hooks = [];
        SolutionManagementAjaxHandler::register(
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
            ['wp_ajax_solutions_lister_table', [SolutionManagementAjaxHandler::class, 'listTable'], 10, 1],
            ['wp_ajax_chasse_solution_status', [SolutionManagementAjaxHandler::class, 'getHuntStatus'], 10, 1],
        ], $hooks);
    }
}
